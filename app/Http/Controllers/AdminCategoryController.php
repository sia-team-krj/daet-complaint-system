<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsComplaintCategoryBoard;
use App\Models\ComplaintCategory;
use App\Models\ComplaintCategoryGroup;
use App\Models\Department;
use App\Services\ComplaintCategoryManager;
use App\Support\ComplaintTaxonomyOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The super admin's complaint taxonomy surface.
 *
 * Problem types and categories live together because they are one decision:
 * what the resident sees and who receives it. Archive is a filter, not a
 * delete — every complaint keeps the key, label, office and history it was
 * filed with.
 */
class AdminCategoryController extends Controller
{
    use BuildsComplaintCategoryBoard;

    public function index(Request $request): View
    {
        $status = $this->categoryStatus($request->query('status'));
        $board = $this->categoryBoard(null, $status, true);

        return view('admin.categories.index', [
            ...$board,
            'office' => null,
            'groupsForSelect' => ComplaintCategoryGroup::query()
                ->orderBy('sort_order')
                ->orderBy('label_en')
                ->get(),
            'groupsForCreate' => ComplaintCategoryGroup::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('label_en')
                ->get(),
            'departments' => Department::query()
                ->active()
                ->orderBy('name')
                ->get(),
            'icons' => ComplaintTaxonomyOptions::icons(),
            'colors' => ComplaintTaxonomyOptions::colors(),
            'intakeModes' => ComplaintTaxonomyOptions::intakeModes(),
        ]);
    }

    public function storeGroup(Request $request, ComplaintCategoryManager $manager): RedirectResponse
    {
        $validated = $request->validate($this->groupRules(), [], $this->attributeNames());

        $group = $manager->createGroup($validated);

        return redirect()
            ->route('admin.categories.index', ['status' => 'all'])
            ->with('status', __('admin_categories.flash.problem_type_created'))
            ->with('highlight', 'group-'.$group->id);
    }

    public function updateGroup(
        Request $request,
        ComplaintCategoryGroup $group,
        ComplaintCategoryManager $manager,
    ): RedirectResponse {
        $validated = $request->validate($this->groupRules($group), [], $this->attributeNames());

        $manager->updateGroup($group, $validated);

        return redirect()
            ->route('admin.categories.index', ['status' => 'all'])
            ->with('status', __('admin_categories.flash.problem_type_updated'))
            ->with('highlight', 'group-'.$group->id);
    }

    public function toggleGroup(
        ComplaintCategoryGroup $group,
        ComplaintCategoryManager $manager,
    ): RedirectResponse {
        $wasActive = $group->is_active;
        $manager->toggleGroup($group);

        return back()->with(
            'status',
            $wasActive
                ? __('admin_categories.flash.problem_type_archived')
                : __('admin_categories.flash.problem_type_restored'),
        );
    }

    public function storeCategory(Request $request, ComplaintCategoryManager $manager): RedirectResponse
    {
        $validated = $request->validate(
            $this->categoryRules(creating: true),
            [],
            $this->attributeNames(),
        );

        $category = $manager->createCategory($validated);

        return redirect()
            ->route('admin.categories.index', ['status' => 'all'])
            ->with('status', __('admin_categories.flash.category_created'))
            ->with('highlight', 'category-'.$category->id);
    }

    public function updateCategory(
        Request $request,
        ComplaintCategory $category,
        ComplaintCategoryManager $manager,
    ): RedirectResponse {
        $validated = $request->validate(
            $this->categoryRules(creating: false, ignoreCategory: $category),
            [],
            $this->attributeNames(),
        );

        $manager->updateCategory($category, $validated);

        return redirect()
            ->route('admin.categories.index', ['status' => 'all'])
            ->with('status', __('admin_categories.flash.category_updated'))
            ->with('highlight', 'category-'.$category->id);
    }

    public function toggleCategory(
        ComplaintCategory $category,
        ComplaintCategoryManager $manager,
    ): RedirectResponse {
        $wasActive = $category->is_active;
        $manager->toggleCategory($category);

        return back()->with(
            'status',
            $wasActive
                ? __('admin_categories.flash.category_archived')
                : __('admin_categories.flash.category_restored'),
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function groupRules(?ComplaintCategoryGroup $ignore = null): array
    {
        return [
            'label_en' => [
                'required',
                'string',
                'max:80',
                function (string $attribute, mixed $value, \Closure $fail) use ($ignore): void {
                    $label = trim((string) $value);
                    if ($label === '') {
                        return;
                    }

                    $exists = ComplaintCategoryGroup::query()
                        ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
                        ->whereRaw('LOWER(TRIM(label_en)) = ?', [mb_strtolower($label)])
                        ->exists();

                    if ($exists) {
                        $fail(__('admin_categories.validation.problem_type_duplicate'));
                    }
                },
            ],
            'label_fil' => ['nullable', 'string', 'max:80'],
            'helper_en' => ['nullable', 'string', 'max:160'],
            'helper_fil' => ['nullable', 'string', 'max:160'],
            'icon' => ['required', Rule::in(ComplaintTaxonomyOptions::ICONS)],
            'color' => ['required', Rule::in(ComplaintTaxonomyOptions::COLORS)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function categoryRules(bool $creating, ?ComplaintCategory $ignoreCategory = null): array
    {
        $groupExists = Rule::exists('complaint_category_groups', 'id');
        $departmentExists = Rule::exists('departments', 'id');

        if ($creating) {
            $groupExists->where('is_active', true);
            $departmentExists->where('is_active', true);
        }

        return [
            'group_id' => ['required', 'integer', $groupExists],
            'department_id' => ['required', 'integer', $departmentExists],
            'label_en' => [
                'required',
                'string',
                'max:80',
                function (string $attribute, mixed $value, \Closure $fail) use ($ignoreCategory): void {
                    $label = trim((string) $value);
                    $departmentId = $this->integerInput('department_id')
                        ?? ($ignoreCategory?->department_id ? (int) $ignoreCategory->department_id : null);

                    if ($label === '' || $departmentId === null) {
                        return;
                    }

                    $exists = ComplaintCategory::query()
                        ->where('department_id', $departmentId)
                        ->when($ignoreCategory, fn ($query) => $query->whereKeyNot($ignoreCategory->id))
                        ->whereRaw('LOWER(TRIM(label_en)) = ?', [mb_strtolower($label)])
                        ->exists();

                    if ($exists) {
                        $fail(__('admin_categories.validation.category_duplicate_office'));
                    }
                },
            ],
            'label_fil' => ['nullable', 'string', 'max:80'],
            'helper_en' => ['nullable', 'string', 'max:160'],
            'helper_fil' => ['nullable', 'string', 'max:160'],
            'icon' => ['required', Rule::in(ComplaintTaxonomyOptions::ICONS)],
            'color' => ['required', Rule::in(ComplaintTaxonomyOptions::COLORS)],
            'intake_mode' => ['required', Rule::in(ComplaintTaxonomyOptions::INTAKE_MODES)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    private function integerInput(string $key): ?int
    {
        $value = request()->input($key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'label_en' => strtolower(__('admin_categories.form.label_en')),
            'label_fil' => strtolower(__('admin_categories.form.label_fil')),
            'helper_en' => strtolower(__('admin_categories.form.helper_en')),
            'helper_fil' => strtolower(__('admin_categories.form.helper_fil')),
            'group_id' => strtolower(__('admin_categories.form.group')),
            'department_id' => strtolower(__('admin_categories.form.office')),
            'intake_mode' => strtolower(__('admin_categories.form.intake')),
            'sort_order' => strtolower(__('admin_categories.form.sort_order')),
        ];
    }
}
