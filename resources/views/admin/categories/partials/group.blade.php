@php
    /** @var \App\Models\ComplaintCategoryGroup $group */
    $isStaff = $office !== null;
    $isEditing = old('_form') === 'group-edit-'.$group->id;
    $groupUpdateAction = $isStaff
        ? null
        : route('admin.categories.groups.update', $group);
    $groupToggleAction = $isStaff
        ? null
        : route('admin.categories.groups.toggle', $group);
@endphp

<section class="cat-group {{ $group->is_active ? '' : 'is-archived' }}" id="group-{{ $group->id }}">
    <header class="cat-group__header">
        <span class="tile__icon tile__icon--{{ $group->color }}" aria-hidden="true">
            @include('complaints.partials.tile-icon', ['icon' => $group->icon])
        </span>

        <div class="cat-group__copy">
            <div class="cat-group__title">
                <h2>{{ $group->label() }}</h2>
                <code class="cat-key">{{ $group->key }}</code>
            </div>
            @if($group->helper() !== '')
                <p class="cat-group__helper">{{ $group->helper() }}</p>
            @endif
        </div>

        <div class="cat-group__meta">
            @if(! $group->is_active)
                <span class="cat-status cat-status--archived">{{ __('admin_categories.problem_types.archived') }}</span>
            @endif
            <span class="cat-group__count">
                {{ trans_choice('admin_categories.problem_types.category_count', $group->categories->count(), ['count' => $group->categories->count()]) }}
            </span>
        </div>

        @if(! $isStaff)
            <div class="cat-group__actions">
                <details class="cat-edit" @if($isEditing) open @endif>
                    <summary class="cat-btn cat-btn--ghost">{{ __('admin_categories.actions.edit') }}</summary>
                    <div class="cat-edit__body">
                        @include('admin.categories.partials.group-form', [
                            'group' => $group,
                            'mode' => 'edit',
                            'action' => $groupUpdateAction,
                            'method' => 'PUT',
                            'formKey' => 'group-edit-'.$group->id,
                            'icons' => $icons,
                            'colors' => $colors,
                        ])
                    </div>
                </details>

                <form data-loading-submit method="POST" action="{{ $groupToggleAction }}" class="cat-row__toggle">
                    @csrf
                    <button type="submit" class="cat-btn cat-btn--ghost {{ $group->is_active ? 'cat-btn--quiet-danger' : '' }}">
                        {{ $group->is_active ? __('admin_categories.actions.archive') : __('admin_categories.actions.restore') }}
                    </button>
                </form>
            </div>
        @endif
    </header>

    @if(! $group->is_active)
        <p class="cat-group__notice">{{ __('admin_categories.problem_types.archived_hint') }}</p>
    @endif

    <div class="cat-group__categories">
        @forelse($group->categories as $category)
            @include('admin.categories.partials.category-row', [
                'category' => $category,
                'group' => $group,
                'office' => $office,
                'groups' => $groupsForSelect,
                'departments' => $departments ?? collect(),
                'icons' => $icons,
                'colors' => $colors,
                'intakeModes' => $intakeModes,
            ])
        @empty
            @if(! $isStaff)
                <p class="cat-group__empty">{{ __('admin_categories.categories.none_in_group') }}</p>
            @endif
        @endforelse
    </div>
</section>
