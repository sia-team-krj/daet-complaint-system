@php
    $isStaff = $office !== null;
    $filterRoute = $isStaff
        ? route('staff.office.categories.index')
        : route('admin.categories.index');
    $groupCreateOpen = old('_form') === 'group-create';
    $categoryCreateOpen = old('_form') === 'category-create';
    $categoryCreateAction = $isStaff
        ? route('staff.office.categories.store')
        : route('admin.categories.store');
    $groupCreateAction = route('admin.categories.groups.store');
    $totalCount = $activeCount + $archivedCount;
@endphp

<div class="cat-manager">
    @if(session('status'))
        <div class="cat-flash cat-flash--success" role="status">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="cat-flash cat-flash--error" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="cat-flow" aria-label="{{ __('admin_categories.page.title') }}">
        <div class="cat-flow__step">
            <span class="cat-flow__num">1</span>
            <span>
                <strong>{{ __('admin_categories.problem_types.title') }}</strong>
                <small>{{ __('admin_categories.problem_types.hint') }}</small>
            </span>
        </div>
        <span class="cat-flow__arrow" aria-hidden="true">→</span>
        <div class="cat-flow__step">
            <span class="cat-flow__num">2</span>
            <span>
                <strong>{{ __('admin_categories.categories.title') }}</strong>
                <small>{{ __('admin_categories.form.category_create_helper') }}</small>
            </span>
        </div>
        <span class="cat-flow__arrow" aria-hidden="true">→</span>
        <div class="cat-flow__step">
            <span class="cat-flow__num">3</span>
            <span>
                <strong>{{ __('admin_categories.form.office') }}</strong>
                <small>{{ __('admin_categories.form.office_hint') }}</small>
            </span>
        </div>
    </div>

    <div class="cat-manager__toolbar">
        <div class="cat-filters" role="group" aria-label="{{ __('admin_categories.filters.label') }}">
            <a href="{{ $filterRoute }}"
               class="cat-chip {{ $status === 'active' ? 'is-active' : '' }}"
               @if($status === 'active') aria-current="true" @endif>
                {{ __('admin_categories.filters.active') }}
                <span class="cat-chip__count">{{ $activeCount }}</span>
            </a>
            <a href="{{ $filterRoute.'?status=archived' }}"
               class="cat-chip {{ $status === 'archived' ? 'is-active' : '' }}"
               @if($status === 'archived') aria-current="true" @endif>
                {{ __('admin_categories.filters.archived') }}
                <span class="cat-chip__count">{{ $archivedCount }}</span>
            </a>
            <a href="{{ $filterRoute.'?status=all' }}"
               class="cat-chip {{ $status === 'all' ? 'is-active' : '' }}"
               @if($status === 'all') aria-current="true" @endif>
                {{ __('admin_categories.filters.all') }}
                <span class="cat-chip__count">{{ $totalCount }}</span>
            </a>
        </div>
    </div>

    <div class="cat-manager__creates">
        @if(! $isStaff)
            <details class="cat-create" @if($groupCreateOpen) open @endif>
                <summary class="cat-create__summary">
                    <span class="cat-create__plus" aria-hidden="true">+</span>
                    <span class="cat-create__copy">
                        <strong>{{ __('admin_categories.actions.add_problem_type') }}</strong>
                        <small>{{ __('admin_categories.form.problem_type_create_helper') }}</small>
                    </span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                </summary>
                <div class="cat-create__body">
                    @include('admin.categories.partials.group-form', [
                        'group' => null,
                        'mode' => 'create',
                        'action' => $groupCreateAction,
                        'method' => 'POST',
                        'formKey' => 'group-create',
                        'icons' => $icons,
                        'colors' => $colors,
                    ])
                </div>
            </details>
        @endif

        @if($groupsForCreate->isNotEmpty())
            <details class="cat-create cat-create--primary" @if($categoryCreateOpen) open @endif>
                <summary class="cat-create__summary">
                    <span class="cat-create__plus" aria-hidden="true">+</span>
                    <span class="cat-create__copy">
                        <strong>{{ __('admin_categories.actions.add_category') }}</strong>
                        <small>{{ $isStaff ? __('admin_categories.staff.subtitle', ['office' => $office->name]) : __('admin_categories.form.category_create_helper') }}</small>
                    </span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                </summary>
                <div class="cat-create__body">
                    @include('admin.categories.partials.category-form', [
                        'category' => null,
                        'mode' => 'create',
                        'action' => $categoryCreateAction,
                        'method' => 'POST',
                        'formKey' => 'category-create',
                        'groups' => $groupsForCreate,
                        'departments' => $departments ?? collect(),
                        'office' => $office,
                        'showOffice' => ! $isStaff,
                        'icons' => $icons,
                        'colors' => $colors,
                        'intakeModes' => $intakeModes,
                    ])
                </div>
            </details>
        @elseif($isStaff)
            <p class="cat-manager__notice">{{ __('admin_categories.staff.no_problem_types') }}</p>
        @endif
    </div>

    @if($groups->isEmpty())
        <section class="cat-empty">
            <span class="cat-empty__icon" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5Z"></path><path d="m2 17 10 5 10-5"></path><path d="m2 12 10 5 10-5"></path></svg>
            </span>
            <h2>{{ $totalCount === 0 ? __('admin_categories.empty.title') : __('admin_categories.empty.filter') }}</h2>
            @if($totalCount === 0)
                <p>{{ $isStaff ? __('admin_categories.staff.no_problem_types') : __('admin_categories.empty.body') }}</p>
            @endif
        </section>
    @else
        <div class="cat-groups">
            @foreach($groups as $group)
                @include('admin.categories.partials.group', [
                    'group' => $group,
                    'office' => $office,
                    'groupsForSelect' => $groupsForSelect,
                    'departments' => $departments ?? collect(),
                    'icons' => $icons,
                    'colors' => $colors,
                    'intakeModes' => $intakeModes,
                ])
            @endforeach
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const forms = document.querySelectorAll('[data-cat-form]');

                forms.forEach((form) => {
                    const previewIcon = form.querySelector('[data-cat-preview-icon]');
                    const previewLabel = form.querySelector('[data-cat-preview-label]');
                    const previewHelper = form.querySelector('[data-cat-preview-helper]');
                    const labelInput = form.querySelector('input[name="label_en"]');
                    const helperInput = form.querySelector('input[name="helper_en"]');
                    const iconRadios = form.querySelectorAll('input[name="icon"]');
                    const colorRadios = form.querySelectorAll('input[name="color"]');

                    if (!previewIcon || !previewLabel || !previewHelper) {
                        return;
                    }

                    const iconChoices = () => form.querySelectorAll('.cat-choice--icon .tile__icon');

                    const updateColor = (color) => {
                        [previewIcon, ...iconChoices()].forEach((tile) => {
                            [...tile.classList]
                                .filter((name) => name.startsWith('tile__icon--'))
                                .forEach((name) => tile.classList.remove(name));
                            tile.classList.add(`tile__icon--${color}`);
                        });
                    };

                    const update = () => {
                        if (labelInput?.value.trim()) {
                            previewLabel.textContent = labelInput.value.trim();
                        }

                        if (helperInput) {
                            previewHelper.textContent = helperInput.value.trim();
                        }

                        const selectedIcon = form.querySelector('input[name="icon"]:checked');
                        const selectedColor = form.querySelector('input[name="color"]:checked');
                        const source = selectedIcon?.closest('.cat-choice')?.querySelector('svg');

                        if (source) {
                            previewIcon.innerHTML = source.outerHTML;
                        }

                        if (selectedColor) {
                            updateColor(selectedColor.value);
                        }
                    };

                    [labelInput, helperInput].forEach((field) => field?.addEventListener('input', update));
                    iconRadios.forEach((radio) => radio.addEventListener('change', update));
                    colorRadios.forEach((radio) => radio.addEventListener('change', update));
                });

                const highlighted = @json(session('highlight'));
                if (highlighted) {
                    const target = document.getElementById(highlighted);
                    if (target) {
                        target.classList.add('is-highlighted');
                        target.scrollIntoView({ block: 'center' });
                    }
                }
            })();
        </script>
    @endpush
@endonce
