<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintCategoryGroup;
use App\Models\Department;
use App\Models\User;
use App\Services\ComplaintTaxonomy;
use App\Services\DepartmentRouter;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The taxonomy is admin data now, not a config file.
 *
 * These tests guard the promises that makes to the rest of the system:
 * residents see the active rows immediately, archived rows cannot be
 * submitted, keys survive a rename, and a Department Administrator can only
 * touch their own office's categories.
 */
class ComplaintCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $engineering;

    private Department $menro;

    private ComplaintCategoryGroup $roads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DepartmentSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->engineering = Department::where('code', 'ENG')->firstOrFail();
        $this->menro = Department::where('code', 'MENRO')->firstOrFail();
        $this->roads = ComplaintCategoryGroup::where('key', 'roads')->firstOrFail();
    }

    public function test_the_super_admin_page_lists_groups_categories_and_offices(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Complaint categories')
            ->assertSee('Roads &amp; Drainage', false)
            ->assertSee('Road Damage')
            ->assertSee('Engineering Office')
            ->assertSee('Add problem type')
            ->assertSee('Add category');
    }

    public function test_a_super_admin_can_create_a_problem_type_and_its_key_is_generated_from_the_label(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.groups.store'), [
                'label_en' => 'Coastal Concerns',
                'label_fil' => 'Mga Alalahanin sa Baybayin',
                'helper_en' => 'Shoreline and coastal issues.',
                'helper_fil' => 'Mga isyu sa dalampasigan.',
                'icon' => 'waves',
                'color' => 'flood',
                'sort_order' => 9,
            ])
            ->assertRedirect(route('admin.categories.index', ['status' => 'all']))
            ->assertSessionHasNoErrors();

        $group = ComplaintCategoryGroup::where('key', 'coastal_concerns')->first();

        $this->assertNotNull($group, 'The English label should generate the stable key.');
        $this->assertTrue($group->is_active);
        $this->assertSame('Coastal Concerns', $group->label_en);
        $this->assertSame('waves', $group->icon);
    }

    public function test_a_new_category_appears_in_the_resident_wizard_immediately(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'group_id' => $this->roads->id,
                'department_id' => $this->engineering->id,
                'label_en' => 'Drainage Overflow',
                'label_fil' => 'Pag-apaw ng Drainage',
                'helper_en' => 'A canal that has overflowed.',
                'helper_fil' => 'Kanal na umapaw.',
                'icon' => 'waves',
                'color' => 'flood',
                'intake_mode' => 'physical_required',
            ])
            ->assertRedirect(route('admin.categories.index', ['status' => 'all']))
            ->assertSessionHasNoErrors();

        $category = ComplaintCategory::where('key', 'drainage_overflow')->firstOrFail();

        $this->assertSame($this->engineering->id, $category->department_id);
        $this->assertTrue($category->is_active);
        $this->assertContains('drainage_overflow', DepartmentRouter::activeCategoryKeys());

        $citizen = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($citizen)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Drainage Overflow');
    }

    public function test_renaming_a_category_never_changes_its_key(): void
    {
        $category = $this->makeCategory([
            'key' => 'drainage_overflow',
            'label_en' => 'Drainage Overflow',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), [
                'group_id' => $this->roads->id,
                'department_id' => $this->engineering->id,
                'label_en' => 'Blocked Drainage',
                'label_fil' => 'Baradong Drainage',
                'helper_en' => 'A canal that cannot drain.',
                'helper_fil' => 'Kanal na hindi makadaluyan.',
                'icon' => 'waves',
                'color' => 'flood',
                'intake_mode' => 'physical_required',
            ])
            ->assertRedirect(route('admin.categories.index', ['status' => 'all']))
            ->assertSessionHasNoErrors();

        $this->assertSame('drainage_overflow', $category->fresh()->key);
        $this->assertSame('Blocked Drainage', $category->fresh()->label_en);
        $this->assertSame('Blocked Drainage', DepartmentRouter::categoryLabels()['drainage_overflow']);
    }

    public function test_an_archived_category_disappears_from_the_wizard_and_cannot_be_submitted(): void
    {
        $category = $this->makeCategory([
            'key' => 'drainage_overflow',
            'label_en' => 'Drainage Overflow',
        ]);

        $complaint = Complaint::factory()->create([
            'category' => $category->key,
            'department_id' => $this->engineering->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.categories.toggle', $category))
            ->assertRedirect();

        $this->assertFalse($category->fresh()->is_active);
        $this->assertNotContains($category->key, DepartmentRouter::activeCategoryKeys());
        $this->assertSame(
            'Drainage Overflow',
            DepartmentRouter::categoryLabels()[$category->key],
            'Archiving must not erase the label historical reports use.',
        );

        $citizen = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($citizen)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertDontSee('Drainage Overflow');

        $this->actingAs($citizen)
            ->post(route('complaints.store'), [
                'category' => $category->key,
                'title' => 'A blocked drain',
                'description' => 'The drain is blocked and water is rising over the road.',
                'terms' => '1',
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'category' => $category->key,
        ]);
    }

    public function test_archiving_a_problem_type_hides_all_of_its_categories_until_restored(): void
    {
        $this->assertContains('road_damage', DepartmentRouter::activeCategoryKeys());

        $this->actingAs($this->admin)
            ->post(route('admin.categories.groups.toggle', $this->roads))
            ->assertRedirect();

        $this->assertFalse($this->roads->fresh()->is_active);
        $this->assertNotContains('road_damage', DepartmentRouter::activeCategoryKeys());
        $this->assertNotContains('flooding', DepartmentRouter::activeCategoryKeys());

        $citizen = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($citizen)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertDontSee('Road Damage')
            ->assertSee('Garbage Collection');

        $this->actingAs($this->admin)
            ->post(route('admin.categories.groups.toggle', $this->roads))
            ->assertRedirect();

        $this->assertContains('road_damage', DepartmentRouter::activeCategoryKeys());
    }

    public function test_a_department_admin_only_sees_and_edits_their_own_offices_categories(): void
    {
        $engineeringCategory = $this->makeCategory([
            'key' => 'engineering_only',
            'label_en' => 'Engineering Only',
            'department_id' => $this->engineering->id,
        ]);

        $manager = User::factory()->create([
            'role' => 'department_admin',
            'department_id' => $this->menro->id,
        ]);

        $this->actingAs($manager)
            ->get(route('staff.office.categories.index'))
            ->assertOk()
            ->assertSee('Garbage Collection')
            ->assertDontSee('Engineering Only')
            ->assertDontSee('Add problem type');

        // A crafted department_id does not change the scope: the controller
        // passes its own office to the manager.
        $this->actingAs($manager)
            ->post(route('staff.office.categories.store'), [
                'group_id' => $this->roads->id,
                'department_id' => $this->engineering->id,
                'label_en' => 'Menro Special',
                'helper_en' => 'A MENRO-only category.',
                'icon' => 'trash-2',
                'color' => 'waste',
                'intake_mode' => 'proof_optional',
            ])
            ->assertRedirect(route('staff.office.categories.index', ['status' => 'all']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('complaint_categories', [
            'key' => 'menro_special',
            'department_id' => $this->menro->id,
        ]);

        $this->actingAs($manager)
            ->put(route('staff.office.categories.update', $engineeringCategory), [
                'group_id' => $this->roads->id,
                'label_en' => 'Hijacked',
                'icon' => 'construction',
                'color' => 'roads',
                'intake_mode' => 'physical_required',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('staff.office.categories.toggle', $engineeringCategory))
            ->assertForbidden();

        $this->assertTrue($engineeringCategory->fresh()->is_active);
    }

    public function test_category_intake_mode_drives_the_policy_the_form_and_server_use(): void
    {
        $category = $this->makeCategory([
            'key' => 'sensitive_example',
            'label_en' => 'Sensitive Example',
            'intake_mode' => 'sensitive_intake',
        ]);

        $policy = Complaint::intakePolicyFor($category->key);

        $this->assertSame('sensitive_intake', $policy['mode']);
        $this->assertFalse($policy['photos_allowed']);
        $this->assertFalse($policy['location_required']);
        $this->assertFalse($policy['show_location_steps']);

        $category->update(['intake_mode' => 'proof_optional']);

        $updated = Complaint::intakePolicyFor($category->key);
        $this->assertSame('proof_optional', $updated['mode']);
        $this->assertTrue($updated['location_required']);
        $this->assertFalse($updated['photos_required']);
    }

    public function test_the_old_settings_screen_links_to_the_taxonomy_instead_of_editing_it(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee(route('admin.categories.index'))
            ->assertDontSee('/admin/settings/categories', false);
    }

    public function test_unknown_icons_colors_and_intake_modes_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'group_id' => $this->roads->id,
                'department_id' => $this->engineering->id,
                'label_en' => 'Bad Category',
                'icon' => 'not-a-real-icon',
                'color' => 'not-a-real-color',
                'intake_mode' => 'not-a-real-mode',
            ])
            ->assertSessionHasErrors(['icon', 'color', 'intake_mode']);

        $this->assertDatabaseMissing('complaint_categories', ['key' => 'bad_category']);
    }

    public function test_the_taxonomy_snapshot_is_flushed_when_a_category_changes(): void
    {
        $before = app(ComplaintTaxonomy::class)->activeCategoryKeys();
        $this->assertNotContains('live_after_create', $before);

        $this->makeCategory([
            'key' => 'live_after_create',
            'label_en' => 'Live After Create',
        ]);

        $this->assertContains('live_after_create', app(ComplaintTaxonomy::class)->activeCategoryKeys());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCategory(array $overrides = []): ComplaintCategory
    {
        return ComplaintCategory::create(array_merge([
            'key' => 'test_category',
            'group_id' => $this->roads->id,
            'department_id' => $this->engineering->id,
            'label_en' => 'Test Category',
            'label_fil' => null,
            'helper_en' => null,
            'helper_fil' => null,
            'icon' => 'construction',
            'color' => 'roads',
            'intake_mode' => 'physical_required',
            'is_active' => true,
            'sort_order' => 99,
        ], $overrides));
    }
}
