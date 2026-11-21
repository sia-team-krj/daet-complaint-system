<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The landing-page FAQ is municipal copy now.
 *
 * The migration seeds the four questions that were previously translation
 * keys; the super admin owns everything after that. These tests guard the two
 * promises the public page depends on: only published rows appear, and they
 * appear in the admin's order with Filipino falling back to English.
 */
class FaqManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_the_four_landing_page_faqs_are_seeded_in_order(): void
    {
        $this->assertDatabaseCount('faqs', 4);
        $this->assertSame(
            [
                'Do I need an account?',
                'Will my name be public?',
                'How long will it take?',
                'Can I use Filipino?',
            ],
            Faq::query()->ordered()->pluck('question_en')->all(),
        );
        $this->assertSame(4, Faq::query()->active()->count());
    }

    public function test_the_super_admin_page_lists_questions_and_the_create_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.faqs.index'))
            ->assertOk()
            ->assertSee('Frequently asked questions')
            ->assertSee('Do I need an account?')
            ->assertSee('Add a question')
            ->assertSee('Published')
            ->assertSee('Archived')
            ->assertSee('All');
    }

    public function test_only_a_super_admin_can_reach_the_faq_manager(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.faqs.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($staff)
            ->post(route('admin.faqs.store'), [
                'question_en' => 'Should not be saved',
                'answer_en' => 'No.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('faqs', ['question_en' => 'Should not be saved']);
    }

    public function test_a_super_admin_can_add_a_question_with_an_optional_translation(): void
    {
        Faq::query()->delete();

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.store'), [
                '_form' => 'create',
                'status' => 'active',
                'question_en' => 'Can I report anonymously?',
                'question_fil' => 'Puwede bang mag-ulat nang hindi nagpapakilala?',
                'answer_en' => "Yes. Your name is never shown publicly.\nYou still need an account.",
                'answer_fil' => '',
                'sort_order' => '',
            ])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'active']))
            ->assertSessionHasNoErrors();

        $faq = Faq::query()->firstOrFail();

        $this->assertSame('Can I report anonymously?', $faq->question_en);
        $this->assertSame('Puwede bang mag-ulat nang hindi nagpapakilala?', $faq->question_fil);
        $this->assertSame("Yes. Your name is never shown publicly.\nYou still need an account.", $faq->answer_en);
        $this->assertNull($faq->answer_fil);
        $this->assertSame(1, $faq->sort_order);
        $this->assertTrue($faq->is_active);
        $this->assertTrue(DB::table('activity_logs')->where('action', 'faq.created')->exists());
    }

    public function test_a_super_admin_can_edit_a_question_and_keep_or_change_its_order(): void
    {
        Faq::query()->delete();
        $faq = $this->faq(['sort_order' => 5]);

        $this->actingAs($this->admin)
            ->put(route('admin.faqs.update', $faq), [
                '_form' => 'edit-'.$faq->id,
                'status' => 'active',
                'question_en' => 'Updated question',
                'question_fil' => 'Na-update na tanong',
                'answer_en' => 'Updated answer',
                'answer_fil' => '',
                'sort_order' => '2',
            ])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'active']))
            ->assertSessionHasNoErrors();

        $faq->refresh();

        $this->assertSame('Updated question', $faq->question_en);
        $this->assertSame('Na-update na tanong', $faq->question_fil);
        $this->assertSame('Updated answer', $faq->answer_en);
        $this->assertNull($faq->answer_fil);
        $this->assertSame(2, $faq->sort_order);
        $this->assertTrue(DB::table('activity_logs')->where('action', 'faq.updated')->exists());
    }

    public function test_archiving_hides_a_question_and_restoring_publishes_it_again(): void
    {
        Faq::query()->delete();
        $faq = $this->faq(['question_en' => 'Archive me']);

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.toggle', $faq), ['status' => 'active'])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'active']));

        $this->assertFalse($faq->fresh()->is_active);
        $this->assertTrue(DB::table('activity_logs')->where('action', 'faq.archived')->exists());

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Archive me');

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.toggle', $faq), ['status' => 'archived'])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'archived']));

        $this->assertTrue($faq->fresh()->is_active);
        $this->assertTrue(DB::table('activity_logs')->where('action', 'faq.restored')->exists());

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSee('Archive me');
    }

    public function test_moving_a_question_renumbers_the_published_order_and_the_landing_page_follows(): void
    {
        Faq::query()->delete();
        $first = $this->faq(['question_en' => 'First question', 'sort_order' => 1]);
        $second = $this->faq(['question_en' => 'Second question', 'sort_order' => 2]);
        $third = $this->faq(['question_en' => 'Third question', 'sort_order' => 3]);

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.move', [$third, 'up']), ['status' => 'active'])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'active']));

        $this->assertSame(
            ['First question', 'Third question', 'Second question'],
            Faq::query()->active()->ordered()->pluck('question_en')->all(),
        );
        $this->assertSame([1, 2, 3], Faq::query()->active()->ordered()->pluck('sort_order')->all());
        $this->assertTrue(DB::table('activity_logs')->where('action', 'faq.reordered')->exists());

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['First question', 'Third question', 'Second question']);

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.move', [$first, 'down']), ['status' => 'active'])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'active']));

        $this->assertSame(
            ['Third question', 'First question', 'Second question'],
            Faq::query()->active()->ordered()->pluck('question_en')->all(),
        );
    }

    public function test_a_super_admin_can_delete_a_question_permanently_with_an_audit_entry(): void
    {
        Faq::query()->delete();
        $faq = $this->faq(['question_en' => 'Delete me']);

        $this->actingAs($this->admin)
            ->delete(route('admin.faqs.destroy', $faq), ['status' => 'all'])
            ->assertRedirect(route('admin.faqs.index', ['status' => 'all']));

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
        $this->assertTrue(DB::table('activity_logs')->where('action', 'faq.deleted')->exists());
    }

    public function test_english_is_required_and_the_optional_fields_stay_optional(): void
    {
        Faq::query()->delete();

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.store'), [
                '_form' => 'create',
                'status' => 'active',
                'question_en' => '',
                'answer_en' => '',
                'question_fil' => 'Puwede',
                'answer_fil' => 'Oo',
                'sort_order' => 'not-a-number',
            ])
            ->assertSessionHasErrors(['question_en', 'answer_en', 'sort_order']);

        $this->assertDatabaseCount('faqs', 0);

        $this->actingAs($this->admin)
            ->post(route('admin.faqs.store'), [
                '_form' => 'create',
                'status' => 'active',
                'question_en' => 'English only',
                'answer_en' => 'An English answer.',
            ])
            ->assertSessionHasNoErrors();

        $faq = Faq::query()->firstOrFail();
        $this->assertNull($faq->question_fil);
        $this->assertNull($faq->answer_fil);
    }

    public function test_validation_errors_appear_only_in_the_form_that_failed(): void
    {
        Faq::query()->delete();
        $this->faq(['question_en' => 'Existing question', 'sort_order' => 1]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.faqs.index'))
            ->followingRedirects()
            ->post(route('admin.faqs.store'), [
                '_form' => 'create',
                'status' => 'active',
                'question_en' => '',
                'answer_en' => '',
            ]);

        $response->assertOk();

        // One create form, one edit form: the message belongs to the create form
        // only, so it must not be repeated inside the existing row's editor.
        $this->assertSame(1, substr_count($response->getContent(), 'The question (english) field is required.'));
        $this->assertSame(1, substr_count($response->getContent(), 'The answer (english) field is required.'));
    }

    public function test_the_status_filter_separates_published_archived_and_all_questions(): void
    {
        Faq::query()->delete();
        $this->faq(['question_en' => 'Published question', 'sort_order' => 1]);
        $this->faq(['question_en' => 'Archived question', 'sort_order' => 2, 'is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.faqs.index'))
            ->assertOk()
            ->assertSee('Published question')
            ->assertDontSee('Archived question');

        $this->actingAs($this->admin)
            ->get(route('admin.faqs.index', ['status' => 'archived']))
            ->assertOk()
            ->assertSee('Archived question')
            ->assertDontSee('Published question');

        $this->actingAs($this->admin)
            ->get(route('admin.faqs.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Published question')
            ->assertSee('Archived question');
    }

    public function test_the_landing_page_renders_filipino_copy_when_present_and_falls_back_to_english(): void
    {
        Faq::query()->delete();
        $this->faq([
            'question_en' => 'Translated question',
            'question_fil' => 'Isinalin na tanong',
            'answer_en' => 'Translated answer',
            'answer_fil' => 'Isinalin na sagot',
            'sort_order' => 1,
        ]);
        $this->faq([
            'question_en' => 'English fallback question',
            'question_fil' => null,
            'answer_en' => 'English fallback answer',
            'answer_fil' => null,
            'sort_order' => 2,
        ]);

        $this->withSession(['locale' => 'fil'])
            ->get('/')
            ->assertOk()
            ->assertSee('Isinalin na tanong')
            ->assertSee('Isinalin na sagot')
            ->assertSee('English fallback question')
            ->assertSee('English fallback answer');
    }

    public function test_the_landing_page_preserves_plain_text_line_breaks_and_hides_an_empty_faq_block(): void
    {
        Faq::query()->delete();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<div class="lp-faq">', false);

        $this->faq([
            'question_en' => 'Two lines?',
            'answer_en' => "First line.\nSecond line.",
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Two lines?')
            ->assertSee('First line.<br />', false)
            ->assertSee('Second line.');
    }

    public function test_the_admin_navigation_includes_the_faq_manager_on_desktop_and_mobile(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.faqs.index').'"', false)
            ->assertSee(__('nav.faqs'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function faq(array $attributes = []): Faq
    {
        return Faq::create([
            'question_en' => 'Fallback question',
            'question_fil' => null,
            'answer_en' => 'Fallback answer',
            'answer_fil' => null,
            'sort_order' => 10,
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
