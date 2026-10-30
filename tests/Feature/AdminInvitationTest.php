<?php

namespace Tests\Feature;

use App\Mail\StaffInvitationMail;
use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AdminInvitationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->seed(DepartmentSeeder::class);

        $this->department = Department::where('code', 'ENG')->firstOrFail();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);
    }

    public function test_admin_can_issue_one_invitation_directly(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invitations.store'), [
                'department_id' => $this->department->id,
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.delacruz@example.com',
            ])
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('status');

        $invitation = InvitationCode::sole();

        $this->assertSame($this->department->id, $invitation->department_id);
        $this->assertSame($this->admin->id, $invitation->created_by);
        $this->assertSame(1, $invitation->max_uses);
        $this->assertSame(0, $invitation->used_count);
        $this->assertTrue($invitation->is_active);
        $this->assertSame('juan.delacruz@example.com', $invitation->invitee_email);
        $this->assertSame('Juan Dela Cruz', $invitation->invitee_name);
        $this->assertNotNull($invitation->emailed_at);
        $this->assertTrue(
            $invitation->expires_at->between(now()->addHours(23), now()->addHours(25)),
            'Invitation should expire roughly 24 hours out.',
        );

        Mail::assertSent(StaffInvitationMail::class, function (StaffInvitationMail $mail) {
            return $mail->hasTo('juan.delacruz@example.com');
        });
    }

    public function test_solo_invite_rejects_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'already.joined@example.com']);

        $this->actingAs($this->admin)
            ->from(route('admin.invitations.index'))
            ->post(route('admin.invitations.store'), [
                'department_id' => $this->department->id,
                'email' => 'already.joined@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, InvitationCode::count());
    }

    public function test_solo_invite_rejects_a_second_open_invitation_for_the_same_email(): void
    {
        $this->createInvitation(['invitee_email' => 'pending@example.com']);

        $this->actingAs($this->admin)
            ->from(route('admin.invitations.index'))
            ->post(route('admin.invitations.store'), [
                'department_id' => $this->department->id,
                'email' => 'pending@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, InvitationCode::count());
    }

    public function test_solo_and_bulk_invites_are_separate_forms(): void
    {
        $content = $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Invite one person', $content);
        $this->assertStringContainsString('Invite several people at once', $content);

        // The solo form posts department_id; the bulk form posts recipients.
        // Neither form may carry the other form's fields.
        $this->assertMatchesRegularExpression(
            '/<form[^>]*action="[^"]*invitations"[^>]*>(?:(?!<\/form>).)*name="department_id"/s',
            $content,
            'The single-invite form should own the department_id field.',
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<form[^>]*action="[^"]*invitations"[^>]*>(?:(?!<\/form>).)*name="recipients"/s',
            $content,
            'The single-invite form must not contain the bulk recipients field.',
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<form[^>]*action="[^"]*invitations\/preview"[^>]*>(?:(?!<\/form>).)*name="department_id"/s',
            $content,
            'The bulk form must not contain the solo department_id field.',
        );
    }

    public function test_admin_can_generate_a_single_use_invitation_for_a_department(): void
    {
        $response = $this->previewAndSend("Juan Dela Cruz <juan.delacruz@example.com>");

        $response->assertRedirect(route('admin.invitations.index'));
        $response->assertSessionHas('status');

        $invitation = InvitationCode::sole();

        $this->assertSame($this->department->id, $invitation->department_id);
        $this->assertSame($this->admin->id, $invitation->created_by);
        $this->assertSame(1, $invitation->max_uses);
        $this->assertSame(0, $invitation->used_count);
        $this->assertTrue($invitation->is_active);
        $this->assertSame('juan.delacruz@example.com', $invitation->invitee_email);
        $this->assertSame('Juan Dela Cruz', $invitation->invitee_name);
        $this->assertTrue(
            $invitation->expires_at->between(now()->addHours(23), now()->addHours(25)),
            'Invitation should expire roughly 24 hours out.',
        );
    }

    public function test_generating_an_invitation_emails_the_link_to_the_invitee(): void
    {
        $this->previewAndSend('Juan Dela Cruz <juan.delacruz@example.com>');

        $invitation = InvitationCode::sole();

        Mail::assertSent(StaffInvitationMail::class, function (StaffInvitationMail $mail) use ($invitation) {
            return $mail->hasTo('juan.delacruz@example.com')
                && $mail->invitation->is($invitation)
                && str_contains($mail->url, route('invitations.show', $invitation->code));
        });

        $this->assertNotNull($invitation->fresh()->emailed_at);
    }

    public function test_a_single_department_can_be_applied_to_every_line(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => "one@example.com\ntwo@example.com\nthree@example.com",
                'default_department_id' => $this->department->id,
            ])
            ->assertRedirect(route('admin.invitations.index'));

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'))
            ->assertRedirect(route('admin.invitations.index'));

        $this->assertSame(3, InvitationCode::count());
        $this->assertSame(
            [$this->department->id],
            InvitationCode::pluck('department_id')->unique()->all(),
        );
    }

    public function test_a_mixed_list_uses_the_department_named_on_each_line(): void
    {
        $gso = Department::where('code', 'GSO')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => "eng.person@example.com | {$this->department->name}\ngso.person@example.com | {$gso->code}",
                'default_department_id' => null,
            ]);

        $this->actingAs($this->admin)->post(route('admin.invitations.send'));

        $this->assertSame(
            [$this->department->id, $gso->id],
            InvitationCode::orderBy('id')->pluck('department_id')->all(),
        );
    }

    public function test_bulk_invites_every_ready_row_and_skips_the_rest(): void
    {
        User::factory()->create(['email' => 'already.joined@example.com']);

        $this->createInvitation(['invitee_email' => 'pending.invite@example.com']);

        $list = implode("\n", [
            'ready.one@example.com',
            'ready.two@example.com',
            'already.joined@example.com',
            'pending.invite@example.com',
            'repeated@example.com',
            'repeated@example.com',
            'not-an-email',
            'unknown@example.com | Nonexistent Department',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => $list,
                'default_department_id' => $this->department->id,
            ]);

        $preview = session('admin.invitations.preview');

        $this->assertSame(3, $preview['ready']);
        $this->assertSame(3, $preview['skipped']);
        $this->assertSame(2, $preview['invalid']);

        $byStatus = collect($preview['rows'])->where('status', '!=', 'ready')->values();

        $this->assertSame('Already has an account.', $byStatus[0]['reason']);
        $this->assertSame('Already has an open invitation.', $byStatus[1]['reason']);
        $this->assertStringContainsString('Repeated in this list', $byStatus[2]['reason']);
        $this->assertStringContainsString('No email address found', $byStatus[3]['reason']);
        $this->assertStringContainsString('Unknown department', $byStatus[4]['reason']);

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('status');

        $this->assertSame(4, InvitationCode::count(), 'Three new invites plus the pre-existing one.');
        $this->assertSame(
            ['ready.one@example.com', 'ready.two@example.com', 'repeated@example.com'],
            InvitationCode::whereNotNull('emailed_at')->orderBy('id')->pluck('invitee_email')->all(),
        );

        Mail::assertSentCount(3);
    }

    public function test_blank_lines_and_comments_are_ignored(): void
    {
        $this->previewAndSend(
            "# new hires\nready@example.com\n\n   \nsecond@example.com\n# done"
        );

        $this->assertSame(
            ['ready@example.com', 'second@example.com'],
            InvitationCode::orderBy('id')->pluck('invitee_email')->all(),
        );
    }

    public function test_a_line_without_a_resolvable_department_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.invitations.index'))
            ->post(route('admin.invitations.preview'), [
                'recipients' => 'no.department@example.com',
                'default_department_id' => null,
            ])
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('errors');

        $this->assertSame(0, InvitationCode::count());
    }

    public function test_preview_sends_nothing_until_confirmed(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => 'not.sent.yet@example.com',
                'default_department_id' => $this->department->id,
            ]);

        $this->assertSame(0, InvitationCode::count(), 'Checking a list must not create invitations.');
        Mail::assertNothingSent();
    }

    public function test_confirming_without_a_checked_list_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, InvitationCode::count());
    }

    public function test_a_confirmed_list_cannot_be_sent_twice(): void
    {
        $this->previewAndSend('once@example.com');

        $this->assertSame(1, InvitationCode::count());

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, InvitationCode::count());
        Mail::assertSentCount(1);
    }

    public function test_a_bulk_list_of_one_line_still_works(): void
    {
        $this->previewAndSend('solo@example.com');

        $invitation = InvitationCode::sole();

        $this->assertSame('solo@example.com', $invitation->invitee_email);
        $this->assertNull($invitation->invitee_name);
        Mail::assertSentCount(1);
    }

    public function test_invitation_email_is_delivered_through_brevo_when_it_is_configured(): void
    {
        config()->set('services.brevo.api_key', 'test-key');

        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'invite-1'], 201),
        ]);

        $this->previewAndSend('brevo@example.com');

        $invitation = InvitationCode::sole();

        Http::assertSent(function ($request) use ($invitation) {
            $body = $request->data();

            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && ($body['to'][0]['email'] ?? null) === 'brevo@example.com'
                && str_contains($body['htmlContent'] ?? '', route('invitations.show', $invitation->code))
                && in_array('staff-invitation', $body['tags'] ?? [], true);
        });

        Mail::assertNothingSent();
        $this->assertNotNull($invitation->fresh()->emailed_at);
    }

    public function test_failed_invitation_delivery_keeps_the_code_and_explains_the_fallback(): void
    {
        config()->set('services.brevo.api_key', 'test-key');

        Http::fake([
            'api.brevo.com/*' => Http::response(['message' => 'unauthorized'], 401),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => "broken@example.com\nfine@example.com",
                'default_department_id' => $this->department->id,
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('error');

        $this->assertSame(2, InvitationCode::count(), 'Both codes stay usable for manual sharing.');
        $this->assertSame(0, InvitationCode::whereNotNull('emailed_at')->count());
    }

    public function test_one_failed_send_does_not_stop_the_rest_of_the_batch(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => "first@example.com\nsecond@example.com\nthird@example.com",
                'default_department_id' => $this->department->id,
            ]);

        // Fail only the middle recipient by making its mailer throw.
        Mail::shouldReceive('to')->andReturnUsing(function ($recipient) {
            if ($recipient === 'second@example.com') {
                throw new RuntimeException('SMTP unavailable');
            }

            return new class
            {
                public function send($mailable) {}
            };
        });

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'))
            ->assertRedirect(route('admin.invitations.index'));

        $this->assertSame(3, InvitationCode::count());
        $this->assertSame(
            ['first@example.com', 'third@example.com'],
            InvitationCode::whereNotNull('emailed_at')->orderBy('id')->pluck('invitee_email')->all(),
        );
    }

    public function test_invitation_cannot_be_redeemed_from_a_different_email(): void
    {
        $invitation = $this->createInvitation(['invitee_email' => 'intended.staff@example.com']);

        $response = $this->redeemInvitation($invitation, 'someone.else@example.com');

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'someone.else@example.com']);

        $invitation->refresh();
        $this->assertSame(0, $invitation->used_count);
        $this->assertTrue($invitation->is_active);
    }

    public function test_reserved_invitation_form_prefills_and_locks_the_invitee_email(): void
    {
        $invitation = $this->createInvitation([
            'invitee_name' => 'Juan Dela Cruz',
            'invitee_email' => 'juan.delacruz@example.com',
            'emailed_at' => now(),
        ]);

        $this->get(route('invitations.show', $invitation->code))
            ->assertOk()
            ->assertSee('value="juan.delacruz@example.com"', false)
            ->assertSee('readonly', false)
            ->assertSee('This invitation is reserved for juan.delacruz@example.com');
    }

    public function test_invite_form_heading_uses_an_explicit_lead_class(): void
    {
        $invitation = $this->createInvitation([
            'invitee_email' => 'lead@example.com',
            'emailed_at' => now(),
        ]);

        $content = $this->get(route('invitations.show', $invitation->code))
            ->assertOk()
            ->getContent();

        // The lead paragraph must be class-targeted, not selected with
        // `p:last-child`. The expiry note is also a <p>, and under a
        // :last-child selector it stole the lead's styling, leaving the
        // description unstyled whenever the note rendered.
        $this->assertStringContainsString('class="invitation-panel-lead"', $content);
        $this->assertStringNotContainsString('invitation-overline', $content);
        $this->assertStringNotContainsString('p:last-child', $content);
    }

    public function test_invite_form_locks_its_submit_control_against_double_submits(): void
    {
        $invitation = $this->createInvitation();

        $this->get(route('invitations.show', $invitation->code))
            ->assertOk()
            ->assertSee('data-disable-on-submit', false);
    }

    public function test_valid_invitation_creates_one_department_bound_staff_account(): void
    {
        $invitation = $this->createInvitation();

        $response = $this->post(route('invitations.redeem', $invitation->code), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'contact_number' => '09171234567',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('email.verify'));
        $this->assertAuthenticated();
        $this->get(route('staff.dashboard'))->assertRedirect(route('email.verify'));

        $staff = User::where('email', 'juan.delacruz@example.com')->sole();

        $this->assertSame('staff', $staff->role);
        $this->assertSame($this->department->id, $staff->department_id);
        $this->assertSame($this->admin->id, $staff->created_by);
        $this->assertTrue($staff->is_active);
        $this->assertFalse($staff->hasVerifiedEmail());
        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $staff->id,
            'purpose' => 'registration',
        ]);

        $invitation->refresh();
        $this->assertTrue($invitation->isUsed());
        $this->assertFalse($invitation->is_active);
        $this->assertSame($staff->id, $invitation->redeemed_by);
        $this->assertNotNull($invitation->redeemed_at);
    }

    public function test_invitation_cannot_be_redeemed_more_than_once(): void
    {
        $invitation = $this->createInvitation();

        $this->redeemInvitation($invitation, 'first.staff@example.com');
        Auth::logout();
        $response = $this->redeemInvitation($invitation, 'second.staff@example.com');

        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseMissing('users', ['email' => 'second.staff@example.com']);
    }

    public function test_expired_invitation_cannot_create_an_account(): void
    {
        $invitation = $this->createInvitation(['expires_at' => now()->subMinute()]);

        $response = $this->redeemInvitation($invitation, 'expired.staff@example.com');

        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseMissing('users', ['email' => 'expired.staff@example.com']);
    }

    public function test_revoked_invitation_cannot_create_an_account(): void
    {
        $invitation = $this->createInvitation(['is_active' => false]);

        $response = $this->redeemInvitation($invitation, 'revoked.staff@example.com');

        $response->assertSessionHasErrors('invitation');
        $this->assertDatabaseMissing('users', ['email' => 'revoked.staff@example.com']);
    }

    public function test_non_admin_users_cannot_manage_invitations(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'department_id' => $this->department->id,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.invitations.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_shell_exposes_all_admin_workspaces(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('All complaints')
            ->assertSee('Departments')
            ->assertSee('Staff accounts')
            ->assertSee('Staff invitations')
            ->assertSee('Audit trail');
    }

    public function test_admin_supporting_workspaces_render(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.departments.index'))->assertOk()->assertSee('Add department');
        $this->get(route('admin.invitations.index'))->assertOk()->assertSee('Invite one person');
        $this->get(route('admin.audit.index'))->assertOk()->assertSee('Audit trail');
    }

    public function test_issued_invitations_are_paginated_five_per_page(): void
    {
        foreach (range(1, 12) as $index) {
            $this->createInvitation(['invitee_email' => "queued{$index}@example.com"]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertSee('queued12@example.com')
            ->assertSee('queued8@example.com')
            ->assertDontSee('queued7@example.com')
            ->assertSee('Next')
            ->assertSee('Previous');
    }

    public function test_pagination_navigates_between_pages(): void
    {
        foreach (range(1, 11) as $index) {
            $this->createInvitation(['invitee_email' => "paged{$index}@example.com"]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertSee('paged11@example.com')
            ->assertDontSee('paged6@example.com');

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('paged6@example.com')
            ->assertSee('paged2@example.com')
            ->assertDontSee('paged11@example.com')
            ->assertDontSee('paged1@example.com');

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index', ['page' => 3]))
            ->assertOk()
            ->assertSee('paged1@example.com')
            ->assertDontSee('paged2@example.com');
    }

    public function test_no_pagination_controls_when_the_list_fits_one_page(): void
    {
        $this->createInvitation(['invitee_email' => 'only@example.com']);

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertDontSee('class="admin-pager"', false);
    }

    public function test_pagination_marks_previous_disabled_on_the_first_page(): void
    {
        foreach (range(1, 7) as $index) {
            $this->createInvitation(['invitee_email' => "first{$index}@example.com"]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertSee('class="admin-pager-step is-disabled" aria-disabled="true" aria-label="Previous page"', false);
    }

    public function test_invitation_form_fields_all_carry_the_visible_input_class(): void
    {
        $content = $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->getContent();

        // A class-less control falls back to Tailwind preflight, which zeroes
        // its border and leaves no visible field at all.
        foreach (['department_id', 'name', 'email', 'default_department_id', 'recipients'] as $field) {
            $this->assertMatchesRegularExpression(
                '/<(?:input|select|textarea)[^>]*name="' . $field . '"[^>]*class="[^"]*\badmin-input\b/',
                $content,
                "The {$field} field is missing the admin-input class, so it renders without a border.",
            );
        }
    }

    public function test_checked_list_is_shown_for_review_before_anything_is_sent(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => "good@example.com\nbad-line",
                'default_department_id' => $this->department->id,
            ]);

        $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertSee('Review before sending')
            ->assertSee('good@example.com')
            ->assertSee('Yes, email 1 invitation')
            ->assertDontSee('Yes, email 2 invitations');
    }

    public function test_staff_accounts_page_renders_for_an_administrator(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Staff accounts');
    }

    public function test_valid_invitation_form_is_publicly_available(): void
    {
        $invitation = $this->createInvitation();

        $this->get(route('invitations.show', $invitation->code))
            ->assertOk()
            ->assertSee($this->department->name)
            ->assertSee('Activate staff account')
            ->assertDontSee('readonly', false);
    }

    private function createInvitation(array $attributes = []): InvitationCode
    {
        return InvitationCode::create([
            'code' => Str::random(32),
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'expires_at' => now()->addHours((int) config('invitations.expires_in_hours', 24)),
            'max_uses' => 1,
            'used_count' => 0,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /**
     * Run the two-step bulk flow: check the list, then confirm the send.
     */
    private function previewAndSend(string $recipients, ?int $defaultDepartmentId = null)
    {
        $defaultDepartmentId ??= $this->department->id;

        $this->actingAs($this->admin)
            ->post(route('admin.invitations.preview'), [
                'recipients' => $recipients,
                'default_department_id' => $defaultDepartmentId,
            ]);

        return $this->actingAs($this->admin)
            ->post(route('admin.invitations.send'));
    }

    private function redeemInvitation(InvitationCode $invitation, string $email)
    {
        return $this->post(route('invitations.redeem', $invitation->code), [
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
        ]);
    }
}
