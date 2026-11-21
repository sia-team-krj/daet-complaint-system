<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ComplaintCategoryGroup;
use App\Models\Department;
use Illuminate\Support\Collection;

/**
 * The grouped category directory shared by the global admin and an office.
 *
 * Archive is a filter, not a second list: the same groups and rows are
 * filtered by status, so a manager looking for something they retired finds
 * it beside a running total of everything hidden. The office scope is applied
 * in the eager-load query, never by hiding rows after they were counted.
 */
trait BuildsComplaintCategoryBoard
{
    protected function categoryStatus(?string $status): string
    {
        return in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active';
    }

    /**
     * @return array{
     *     status: string,
     *     groups: Collection<int, ComplaintCategoryGroup>,
     *     activeCount: int,
     *     archivedCount: int
     * }
     */
    protected function categoryBoard(?Department $office, string $status, bool $includeEmptyGroups): array
    {
        $groups = ComplaintCategoryGroup::query()
            ->with(['categories' => function ($query) use ($office): void {
                $query->with('department')
                    ->withCount('complaints')
                    ->orderBy('sort_order')
                    ->orderBy('label_en');

                if ($office !== null) {
                    $query->where('department_id', $office->id);
                }
            }])
            ->orderBy('sort_order')
            ->orderBy('label_en')
            ->get();

        $activeCount = 0;
        $archivedCount = 0;

        foreach ($groups as $group) {
            foreach ($group->categories as $category) {
                if ($group->is_active && $category->is_active) {
                    $activeCount++;
                } else {
                    $archivedCount++;
                }
            }
        }

        $groups = $groups
            ->map(function (ComplaintCategoryGroup $group) use ($status, $includeEmptyGroups): ?ComplaintCategoryGroup {
                if ($status === 'active' && ! $group->is_active) {
                    return null;
                }

                if ($status === 'archived' && $group->is_active && $group->categories->every(fn ($category): bool => $category->is_active)) {
                    return null;
                }

                $categories = $group->categories
                    ->filter(function ($category) use ($group, $status): bool {
                        $effectiveActive = $group->is_active && $category->is_active;

                        return match ($status) {
                            'active' => $effectiveActive,
                            'archived' => ! $effectiveActive,
                            default => true,
                        };
                    })
                    ->values();

                if (! $includeEmptyGroups && $categories->isEmpty()) {
                    return null;
                }

                $group->setRelation('categories', $categories);

                return $group;
            })
            ->filter()
            ->values();

        return [
            'status' => $status,
            'groups' => $groups,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
        ];
    }
}
