<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use App\Models\ComplaintCategoryGroup;
use App\Models\Department;
use Database\Seeders\Support\ComplaintTaxonomyDefaults;
use Illuminate\Database\Seeder;

/**
 * The canonical complaint taxonomy, with its groups and receiving offices.
 *
 * This runs after DepartmentSeeder (which also invokes it) so every category
 * has an office on a fresh install. It is install data: once an administrator
 * edits a label, helper, icon or office, the database is the authority and
 * re-seeding restores the canonical defaults on purpose.
 *
 * Archived categories are seeded too. They are not offered to residents, but
 * complaints already filed under them still need their label and owner.
 */
class ComplaintCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding complaint taxonomy...');

        foreach (ComplaintTaxonomyDefaults::groups() as $key => $group) {
            ComplaintCategoryGroup::updateOrCreate(
                ['key' => $key],
                [
                    'label_en' => $group['label_en'],
                    'label_fil' => $group['label_fil'],
                    'helper_en' => $group['helper_en'],
                    'helper_fil' => $group['helper_fil'],
                    'icon' => $group['icon'],
                    'color' => $group['color'],
                    'sort_order' => $group['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $groupIds = ComplaintCategoryGroup::pluck('id', 'key');
        $departmentIds = Department::pluck('id', 'code');
        $fallbackDepartmentId = $departmentIds[ComplaintTaxonomyDefaults::fallbackDepartmentCode()]
            ?? Department::where('is_active', true)->value('id');

        foreach (ComplaintTaxonomyDefaults::categories() as $key => $category) {
            ComplaintCategory::updateOrCreate(
                ['key' => $key],
                [
                    'group_id' => $groupIds[$category['group']] ?? $groupIds['other'] ?? null,
                    'department_id' => $departmentIds[$category['department_code']] ?? $fallbackDepartmentId,
                    'label_en' => $category['label_en'],
                    'label_fil' => $category['label_fil'],
                    'helper_en' => $category['helper_en'],
                    'helper_fil' => $category['helper_fil'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'intake_mode' => $category['intake_mode'],
                    'is_active' => $category['is_active'],
                    'sort_order' => $category['sort_order'],
                ],
            );
        }

        $this->command?->info('Complaint taxonomy seeded.');
    }
}
