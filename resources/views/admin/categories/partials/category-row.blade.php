@php
    /** @var \App\Models\ComplaintCategory $category */
    $isStaff = $office !== null;
    $formKey = 'category-edit-'.$category->id;
    $isEditing = old('_form') === $formKey;
    $statusKey = ! $group->is_active
        ? 'hidden_by_group'
        : ($category->is_active ? 'active' : 'archived');
    $intakeLabel = $intakeModes[$category->intake_mode]['label'] ?? $category->intake_mode;
    $updateAction = $isStaff
        ? route('staff.office.categories.update', $category)
        : route('admin.categories.update', $category);
    $toggleAction = $isStaff
        ? route('staff.office.categories.toggle', $category)
        : route('admin.categories.toggle', $category);
@endphp

<article class="cat-row {{ $category->is_active && $group->is_active ? '' : 'is-archived' }}"
         id="category-{{ $category->id }}">
    <div class="cat-row__summary">
        <span class="tile__icon tile__icon--{{ $category->color }}" aria-hidden="true">
            @include('complaints.partials.tile-icon', ['icon' => $category->icon])
        </span>

        <div class="cat-row__copy">
            <div class="cat-row__title">
                <h3>{{ $category->label() }}</h3>
                <code class="cat-key">{{ $category->key }}</code>
            </div>
            @if($category->helper() !== '')
                <p class="cat-row__helper">{{ $category->helper() }}</p>
            @endif
            <div class="cat-row__meta">
                <span class="cat-meta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5l6-3 6 3v16"></path><path d="M9 9h1"></path><path d="M14 9h1"></path><path d="M9 13h1"></path><path d="M14 13h1"></path></svg>
                    {{ $category->department?->name ?? __('admin_categories.categories.no_office') }}
                </span>
                <span class="cat-meta">{{ $intakeLabel }}</span>
                <span class="cat-meta">{{ trans_choice('admin_categories.categories.filed_count', $category->complaints_count, ['count' => $category->complaints_count]) }}</span>
            </div>
        </div>

        <span class="cat-status cat-status--{{ $statusKey }}">{{ __('admin_categories.categories.'.$statusKey) }}</span>

        <div class="cat-row__actions">
            <details class="cat-edit" @if($isEditing) open @endif>
                <summary class="cat-btn cat-btn--ghost">{{ __('admin_categories.actions.edit') }}</summary>
                <div class="cat-edit__body">
                    @include('admin.categories.partials.category-form', [
                        'category' => $category,
                        'mode' => 'edit',
                        'action' => $updateAction,
                        'method' => 'PUT',
                        'formKey' => $formKey,
                        'groups' => $groups,
                        'departments' => $departments ?? collect(),
                        'office' => $office,
                        'showOffice' => $isStaff ? false : true,
                        'icons' => $icons,
                        'colors' => $colors,
                        'intakeModes' => $intakeModes,
                    ])
                </div>
            </details>

            <form data-loading-submit method="POST" action="{{ $toggleAction }}" class="cat-row__toggle">
                @csrf
                <button type="submit" class="cat-btn cat-btn--ghost {{ $category->is_active ? 'cat-btn--quiet-danger' : '' }}">
                    {{ $category->is_active ? __('admin_categories.actions.archive') : __('admin_categories.actions.restore') }}
                </button>
            </form>
        </div>
    </div>
</article>
