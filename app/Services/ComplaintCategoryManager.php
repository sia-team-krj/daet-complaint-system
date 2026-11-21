<?php

namespace App\Services;

use App\Models\ComplaintCategory;
use App\Models\ComplaintCategoryGroup;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes the complaint taxonomy.
 *
 * Controllers own authorization and validation; this class owns the decisions
 * that must be identical for the global admin and a Department Administrator:
 * key generation, append ordering, archive toggling and the audit trail.
 */
class ComplaintCategoryManager
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @param  array{label_en: string, label_fil?: ?string, helper_en?: ?string, helper_fil?: ?string, icon: string, color: string, sort_order?: ?int}  $data
     */
    public function createGroup(array $data, ?User $actor = null): ComplaintCategoryGroup
    {
        return DB::transaction(function () use ($data, $actor): ComplaintCategoryGroup {
            $group = ComplaintCategoryGroup::create([
                'key' => $this->uniqueKey($data['label_en'], 'complaint_category_groups'),
                'label_en' => trim($data['label_en']),
                'label_fil' => $this->nullable($data['label_fil'] ?? null),
                'helper_en' => $this->nullable($data['helper_en'] ?? null),
                'helper_fil' => $this->nullable($data['helper_fil'] ?? null),
                'icon' => $data['icon'],
                'color' => $data['color'],
                'sort_order' => $this->nextGroupOrder($data['sort_order'] ?? null),
                'is_active' => true,
            ]);

            $this->activityLogger->log(
                'complaint_category.group_created',
                "Complaint problem type {$group->label_en} ({$group->key}) created.",
                $group,
                actor: $actor,
                metadata: [
                    'key' => $group->key,
                    'label_en' => $group->label_en,
                    'icon' => $group->icon,
                    'color' => $group->color,
                ],
            );

            return $group;
        });
    }

    /**
     * @param  array{label_en: string, label_fil?: ?string, helper_en?: ?string, helper_fil?: ?string, icon: string, color: string, sort_order?: ?int}  $data
     */
    public function updateGroup(ComplaintCategoryGroup $group, array $data, ?User $actor = null): ComplaintCategoryGroup
    {
        return DB::transaction(function () use ($group, $data, $actor): ComplaintCategoryGroup {
            $group->update([
                'label_en' => trim($data['label_en']),
                'label_fil' => $this->nullable($data['label_fil'] ?? null),
                'helper_en' => $this->nullable($data['helper_en'] ?? null),
                'helper_fil' => $this->nullable($data['helper_fil'] ?? null),
                'icon' => $data['icon'],
                'color' => $data['color'],
                'sort_order' => (int) ($data['sort_order'] ?? $group->sort_order),
            ]);

            $this->activityLogger->log(
                'complaint_category.group_updated',
                "Complaint problem type {$group->label_en} ({$group->key}) updated.",
                $group,
                actor: $actor,
                metadata: [
                    'key' => $group->key,
                    'label_en' => $group->label_en,
                    'is_active' => $group->is_active,
                ],
            );

            return $group;
        });
    }

    public function toggleGroup(ComplaintCategoryGroup $group, ?User $actor = null): ComplaintCategoryGroup
    {
        $group->update(['is_active' => ! $group->is_active]);

        $this->activityLogger->log(
            'complaint_category.group_'.($group->is_active ? 'restored' : 'archived'),
            "Complaint problem type {$group->label_en} ({$group->key}) ".($group->is_active ? 'restored' : 'archived').'.',
            $group,
            actor: $actor,
            metadata: [
                'key' => $group->key,
                'is_active' => $group->is_active,
            ],
        );

        return $group;
    }

    /**
     * @param  array{
     *     group_id: int,
     *     department_id: int,
     *     label_en: string,
     *     label_fil?: ?string,
     *     helper_en?: ?string,
     *     helper_fil?: ?string,
     *     icon: string,
     *     color: string,
     *     intake_mode: string,
     *     sort_order?: ?int
     * }  $data
     */
    public function createCategory(array $data, ?Department $office = null, ?User $actor = null): ComplaintCategory
    {
        $departmentId = $office?->id ?? (int) $data['department_id'];

        return DB::transaction(function () use ($data, $departmentId, $actor): ComplaintCategory {
            $category = ComplaintCategory::create([
                'key' => $this->uniqueKey($data['label_en'], 'complaint_categories'),
                'group_id' => (int) $data['group_id'],
                'department_id' => $departmentId,
                'label_en' => trim($data['label_en']),
                'label_fil' => $this->nullable($data['label_fil'] ?? null),
                'helper_en' => $this->nullable($data['helper_en'] ?? null),
                'helper_fil' => $this->nullable($data['helper_fil'] ?? null),
                'icon' => $data['icon'],
                'color' => $data['color'],
                'intake_mode' => $data['intake_mode'],
                'sort_order' => $this->nextCategoryOrder((int) $data['group_id'], $data['sort_order'] ?? null),
                'is_active' => true,
            ]);

            $this->activityLogger->log(
                'complaint_category.created',
                "Complaint category {$category->label_en} ({$category->key}) created for department #{$departmentId}.",
                $category,
                departmentId: $departmentId,
                actor: $actor,
                metadata: [
                    'key' => $category->key,
                    'group_id' => $category->group_id,
                    'department_id' => $departmentId,
                    'label_en' => $category->label_en,
                    'intake_mode' => $category->intake_mode,
                ],
            );

            return $category;
        });
    }

    /**
     * @param  array{
     *     group_id?: int,
     *     department_id?: int,
     *     label_en: string,
     *     label_fil?: ?string,
     *     helper_en?: ?string,
     *     helper_fil?: ?string,
     *     icon: string,
     *     color: string,
     *     intake_mode: string,
     *     sort_order?: ?int
     * }  $data
     */
    public function updateCategory(
        ComplaintCategory $category,
        array $data,
        ?Department $office = null,
        ?User $actor = null,
    ): ComplaintCategory {
        $departmentId = $office?->id ?? (int) ($data['department_id'] ?? $category->department_id);

        return DB::transaction(function () use ($category, $data, $departmentId, $actor): ComplaintCategory {
            $category->update([
                'group_id' => (int) ($data['group_id'] ?? $category->group_id),
                'department_id' => $departmentId,
                'label_en' => trim($data['label_en']),
                'label_fil' => $this->nullable($data['label_fil'] ?? null),
                'helper_en' => $this->nullable($data['helper_en'] ?? null),
                'helper_fil' => $this->nullable($data['helper_fil'] ?? null),
                'icon' => $data['icon'],
                'color' => $data['color'],
                'intake_mode' => $data['intake_mode'],
                'sort_order' => (int) ($data['sort_order'] ?? $category->sort_order),
            ]);

            $this->activityLogger->log(
                'complaint_category.updated',
                "Complaint category {$category->label_en} ({$category->key}) updated.",
                $category,
                departmentId: $departmentId,
                actor: $actor,
                metadata: [
                    'key' => $category->key,
                    'group_id' => $category->group_id,
                    'department_id' => $departmentId,
                    'label_en' => $category->label_en,
                    'is_active' => $category->is_active,
                ],
            );

            return $category;
        });
    }

    public function toggleCategory(
        ComplaintCategory $category,
        ?User $actor = null,
        ?Department $office = null,
    ): ComplaintCategory {
        $category->update(['is_active' => ! $category->is_active]);

        $this->activityLogger->log(
            'complaint_category.'.($category->is_active ? 'restored' : 'archived'),
            "Complaint category {$category->label_en} ({$category->key}) ".($category->is_active ? 'restored' : 'archived').'.',
            $category,
            departmentId: $office?->id ?? $category->department_id,
            actor: $actor,
            metadata: [
                'key' => $category->key,
                'department_id' => $category->department_id,
                'is_active' => $category->is_active,
            ],
        );

        return $category;
    }

    /**
     * Turn a human label into a stable snake_case key, then make it unique.
     * The key is generated once and never edited afterwards.
     */
    public function uniqueKey(string $label, string $table): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '_', Str::lower(Str::ascii($label))), '_');
        $base = $base === '' ? 'category' : $base;
        $base = substr($base, 0, 45);

        $key = $base;
        $suffix = 2;

        while (DB::table($table)->where('key', $key)->exists()) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        return $key;
    }

    private function nextGroupOrder(?int $requested): int
    {
        if ($requested !== null) {
            return max(0, $requested);
        }

        return (int) (ComplaintCategoryGroup::max('sort_order') ?? 0) + 1;
    }

    private function nextCategoryOrder(int $groupId, ?int $requested): int
    {
        if ($requested !== null) {
            return max(0, $requested);
        }

        return (int) (ComplaintCategory::where('group_id', $groupId)->max('sort_order') ?? 0) + 1;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
