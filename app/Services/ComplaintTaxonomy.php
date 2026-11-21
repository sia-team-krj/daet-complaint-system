<?php

namespace App\Services;

use App\Models\ComplaintCategory;
use App\Models\ComplaintCategoryGroup;
use App\Models\Department;
use Illuminate\Support\Facades\Cache;

/**
 * The database-backed complaint taxonomy, cached as one snapshot.
 *
 * The wizard, routing service and public labels all read from here. The
 * snapshot deliberately stores raw English/Filipino fields rather than one
 * resolved label: the cache is shared by every request, and a resident who
 * switches language must see the other language immediately.
 *
 * Cache invalidation is explicit. ComplaintCategory, ComplaintCategoryGroup
 * and Department fire model events that flush the snapshot, and the test
 * suite flushes it between cases. Nothing relies on a container instance
 * surviving (or not surviving) an HTTP request.
 */
class ComplaintTaxonomy
{
    private const CACHE_KEY = 'complaint-taxonomy:v1';

    /**
     * @return array{
     *     groups: list<array<string, mixed>>,
     *     categories: list<array<string, mixed>>,
     *     departments: list<array{id: int, name: string, code: string}>,
     *     fallback_department: array{id: int, name: string, code: string}|null
     * }
     */
    public function snapshot(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->build());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function groups(): array
    {
        return $this->snapshot()['groups'];
    }

    /**
     * Every category, active or archived. Historical complaints and the admin
     * directory both need the labels of categories no longer offered.
     *
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return $this->snapshot()['categories'];
    }

    public function category(string $key): ?array
    {
        foreach ($this->categories() as $category) {
            if ($category['key'] === $key) {
                return $category;
            }
        }

        return null;
    }

    public function categoryLabel(string $key): ?string
    {
        $category = $this->category($key);

        return $category === null ? null : $this->localized($category);
    }

    /**
     * @return array{icon: string, color: string}
     */
    public function categoryStyle(string $key): array
    {
        $category = $this->category($key);

        return [
            'icon' => $category['icon'] ?? 'circle-help',
            'color' => $category['color'] ?? 'other',
        ];
    }

    public function intakeModeFor(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        return $this->category($key)['intake_mode'] ?? null;
    }

    /**
     * The office that will receive this category right now.
     *
     * An archived office cannot receive new work, so its categories fall to
     * the Mayor's Office. The category itself stays visible as history and in
     * the admin directory.
     *
     * @return array{id: int, name: string, code: string}|null
     */
    public function departmentFor(string $key): ?array
    {
        $snapshot = $this->snapshot();
        $category = null;

        foreach ($snapshot['categories'] as $row) {
            if ($row['key'] === $key) {
                $category = $row;
                break;
            }
        }

        if ($category !== null && $category['department_is_active'] && $category['department'] !== null) {
            return $category['department'];
        }

        return $snapshot['fallback_department'];
    }

    public function resolve(string $key): ?Department
    {
        $department = $this->departmentFor($key);

        return $department === null
            ? null
            : Department::query()->whereKey($department['id'])->first();
    }

    /**
     * Keys the resident may submit right now.
     *
     * @return list<string>
     */
    public function activeCategoryKeys(): array
    {
        $keys = [];

        foreach ($this->offeredCategories() as $category) {
            $keys[] = $category['key'];
        }

        return $keys;
    }

    /**
     * The resident picker, grouped exactly as the database defines it.
     *
     * Empty groups disappear; a category whose office cannot receive work
     * still appears under the fallback office rather than vanishing. Nothing
     * may become unreachable just because an office was archived.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     helper: string,
     *     icon: string,
     *     color: string,
     *     children: list<array<string, mixed>>
     * }>
     */
    public function residentGroups(): array
    {
        $offered = collect($this->offeredCategories())->groupBy('group_id');
        $groups = [];

        foreach ($this->groups() as $group) {
            if (! $group['is_active']) {
                continue;
            }

            $children = [];

            foreach ($offered->get($group['id'], []) as $category) {
                $department = $this->effectiveDepartment($category);

                if ($department === null) {
                    continue;
                }

                $children[] = [
                    'value' => $category['key'],
                    'label' => $this->localized($category),
                    'helper' => $this->localizedHelper($category),
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'department' => $department['name'],
                    'intake_mode' => $category['intake_mode'],
                ];
            }

            if ($children === []) {
                continue;
            }

            $groups[] = [
                'key' => $group['key'],
                'label' => $this->localized($group),
                'helper' => $this->localizedHelper($group),
                'icon' => $group['icon'],
                'color' => $group['color'],
                'children' => $children,
            ];
        }

        return $groups;
    }

    /**
     * The `<select>` and #category-department-data shape used by the wizard.
     * Department names stay in the key so the page can show who receives a
     * report before it is filed.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public function categoryOptions(): array
    {
        $options = [];

        foreach ($this->offeredCategories() as $category) {
            $department = $this->effectiveDepartment($category);

            if ($department === null) {
                continue;
            }

            $options[$department['name']][] = [
                'value' => $category['key'],
                'label' => $this->localized($category),
            ];
        }

        return $options;
    }

    /**
     * Key => localized label for every category, archived included.
     *
     * @return array<string, string>
     */
    public function categoryLabels(): array
    {
        $labels = [];

        foreach ($this->categories() as $category) {
            $labels[$category['key']] = $this->localized($category);
        }

        return $labels;
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function departments(): array
    {
        return $this->snapshot()['departments'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function offeredCategories(): array
    {
        $activeGroupIds = [];

        foreach ($this->groups() as $group) {
            if ($group['is_active']) {
                $activeGroupIds[$group['id']] = true;
            }
        }

        $offered = [];

        foreach ($this->categories() as $category) {
            if (! $category['is_active'] || ! isset($activeGroupIds[$category['group_id']])) {
                continue;
            }

            $offered[] = $category;
        }

        return $offered;
    }

    /**
     * @param  array<string, mixed>  $category
     * @return array{id: int, name: string, code: string}|null
     */
    private function effectiveDepartment(array $category): ?array
    {
        if ($category['department_is_active'] && $category['department'] !== null) {
            return $category['department'];
        }

        return $this->snapshot()['fallback_department'];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function localized(array $row): string
    {
        return app()->getLocale() === 'fil' && filled($row['label_fil'] ?? null)
            ? $row['label_fil']
            : ($row['label_en'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function localizedHelper(array $row): string
    {
        return app()->getLocale() === 'fil' && filled($row['helper_fil'] ?? null)
            ? $row['helper_fil']
            : ($row['helper_en'] ?? '');
    }

    /**
     * @return array{
     *     groups: list<array<string, mixed>>,
     *     categories: list<array<string, mixed>>,
     *     departments: list<array{id: int, name: string, code: string}>,
     *     fallback_department: array{id: int, name: string, code: string}|null
     * }
     */
    private function build(): array
    {
        $groups = ComplaintCategoryGroup::query()
            ->orderBy('sort_order')
            ->orderBy('label_en')
            ->get()
            ->map(fn (ComplaintCategoryGroup $group): array => [
                'id' => $group->id,
                'key' => $group->key,
                'label_en' => $group->label_en,
                'label_fil' => $group->label_fil,
                'helper_en' => $group->helper_en,
                'helper_fil' => $group->helper_fil,
                'icon' => $group->icon,
                'color' => $group->color,
                'sort_order' => $group->sort_order,
                'is_active' => $group->is_active,
            ])
            ->values()
            ->all();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
            ])
            ->values()
            ->all();

        $activeDepartmentIds = collect($departments)->pluck('id')->flip();
        $fallback = null;

        foreach ($departments as $department) {
            if ($department['code'] === DepartmentRouter::FALLBACK_CODE) {
                $fallback = $department;
                break;
            }
        }

        $fallback ??= $departments[0] ?? null;

        $categories = ComplaintCategory::query()
            ->with('department:id,name,code,is_active')
            ->orderBy('sort_order')
            ->orderBy('label_en')
            ->get()
            ->map(function (ComplaintCategory $category) use ($activeDepartmentIds): array {
                $department = $category->department === null ? null : [
                    'id' => $category->department->id,
                    'name' => $category->department->name,
                    'code' => $category->department->code,
                ];

                return [
                    'id' => $category->id,
                    'key' => $category->key,
                    'group_id' => $category->group_id,
                    'department_id' => $category->department_id,
                    'department' => $department,
                    'department_is_active' => $department !== null
                        && $activeDepartmentIds->has($department['id']),
                    'label_en' => $category->label_en,
                    'label_fil' => $category->label_fil,
                    'helper_en' => $category->helper_en,
                    'helper_fil' => $category->helper_fil,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'intake_mode' => $category->intake_mode,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                ];
            })
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'categories' => $categories,
            'departments' => $departments,
            'fallback_department' => $fallback,
        ];
    }
}
