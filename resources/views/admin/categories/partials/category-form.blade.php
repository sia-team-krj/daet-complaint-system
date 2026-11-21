@php
    /** @var \App\Models\ComplaintCategory|null $category */
    $category ??= null;
    $mode ??= 'create';
    $office ??= null;
    $showOffice ??= true;
    $formKey ??= $mode === 'edit' ? 'category-edit-'.$category?->id : 'category-create';
    $isActiveForm = old('_form') === $formKey;
    $value = fn (string $field) => $isActiveForm ? old($field, $category?->{$field}) : ($category?->{$field});
    $selectedIcon = $value('icon') ?: 'circle-help';
    $selectedColor = $value('color') ?: 'other';
    $selectedGroup = $value('group_id') ?: $groups->first()?->id;
    $selectedOffice = $value('department_id') ?: $office?->id;
    $selectedIntake = $value('intake_mode') ?: 'physical_required';
    $officeChoices = collect($departments ?? []);
    if ($category?->department !== null && ! $officeChoices->contains('id', $category->department_id)) {
        $officeChoices->push($category->department);
    }
@endphp

<form
    data-loading-submit
    method="POST"
    action="{{ $action }}"
    class="cat-form"
    data-cat-form
>
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="_form" value="{{ $formKey }}">

    <div class="cat-form__intro">
        <div class="cat-form__preview" data-cat-preview-tile>
            <span class="tile__icon tile__icon--{{ $selectedColor }}" data-cat-preview-icon>
                @include('complaints.partials.tile-icon', ['icon' => $selectedIcon])
            </span>
            <span class="cat-form__preview-copy">
                <strong data-cat-preview-label>{{ $value('label_en') ?: __('admin_categories.form.preview') }}</strong>
                <span data-cat-preview-helper>{{ $value('helper_en') ?: __('admin_categories.form.preview_hint') }}</span>
            </span>
        </div>
        <p class="cat-form__lede">{{ __('admin_categories.form.category_create_helper') }}</p>
    </div>

    <div class="cat-form__grid">
        <label class="cat-field" for="{{ $formKey }}-group">
            <span>{{ __('admin_categories.form.group') }}</span>
            <select id="{{ $formKey }}-group" name="group_id" class="cat-input" required
                    @if($isActiveForm && $errors->has('group_id')) aria-invalid="true" @endif>
                <option value="">{{ __('admin_categories.form.no_group') }}</option>
                @foreach($groups as $groupOption)
                    <option value="{{ $groupOption->id }}" @selected((int) $selectedGroup === $groupOption->id)>
                        {{ $groupOption->label() }}@if(! $groupOption->is_active) — {{ __('admin_categories.categories.archived') }}@endif
                    </option>
                @endforeach
            </select>
            <span class="cat-form__hint">{{ __('admin_categories.form.group_hint') }}</span>
            @if($isActiveForm && $errors->has('group_id'))<span class="cat-field__error">{{ $errors->first('group_id') }}</span>@endif
        </label>

        @if($showOffice)
            <label class="cat-field" for="{{ $formKey }}-office">
                <span>{{ __('admin_categories.form.office') }}</span>
                <select id="{{ $formKey }}-office" name="department_id" class="cat-input" required
                        @if($isActiveForm && $errors->has('department_id')) aria-invalid="true" @endif>
                    <option value="">{{ __('admin_categories.form.no_office') }}</option>
                    @foreach($officeChoices as $department)
                        <option value="{{ $department->id }}" @selected((int) $selectedOffice === $department->id)>
                            {{ $department->name }}@if(! $department->is_active) — {{ __('admin_categories.categories.archived') }}@endif
                        </option>
                    @endforeach
                </select>
                <span class="cat-form__hint">{{ __('admin_categories.form.office_hint') }}</span>
                @if($isActiveForm && $errors->has('department_id'))<span class="cat-field__error">{{ $errors->first('department_id') }}</span>@endif
            </label>
        @else
            <div class="cat-field">
                <span>{{ __('admin_categories.form.office') }}</span>
                <span class="cat-scope">
                    <span class="tile__icon tile__icon--other" aria-hidden="true">
                        @include('complaints.partials.tile-icon', ['icon' => 'users'])
                    </span>
                    <span>{{ $office?->name }}</span>
                </span>
                <span class="cat-form__hint">{{ __('admin_categories.form.office_hint') }}</span>
            </div>
        @endif

        <label class="cat-field" for="{{ $formKey }}-label-en">
            <span>{{ __('admin_categories.form.label_en') }}</span>
            <input id="{{ $formKey }}-label-en" type="text" name="label_en" class="cat-input"
                   value="{{ $value('label_en') }}" required maxlength="80"
                   @if($isActiveForm && $errors->has('label_en')) aria-invalid="true" @endif>
            @if($isActiveForm && $errors->has('label_en'))<span class="cat-field__error">{{ $errors->first('label_en') }}</span>@endif
        </label>

        <label class="cat-field" for="{{ $formKey }}-label-fil">
            <span>{{ __('admin_categories.form.label_fil') }} <em>{{ __('admin_categories.form.optional') }}</em></span>
            <input id="{{ $formKey }}-label-fil" type="text" name="label_fil" class="cat-input"
                   value="{{ $value('label_fil') }}" maxlength="80">
        </label>

        <label class="cat-field cat-field--wide" for="{{ $formKey }}-helper-en">
            <span>{{ __('admin_categories.form.helper_en') }} <em>{{ __('admin_categories.form.optional') }}</em></span>
            <input id="{{ $formKey }}-helper-en" type="text" name="helper_en" class="cat-input"
                   value="{{ $value('helper_en') }}" maxlength="160">
        </label>

        <label class="cat-field cat-field--wide" for="{{ $formKey }}-helper-fil">
            <span>{{ __('admin_categories.form.helper_fil') }} <em>{{ __('admin_categories.form.optional') }}</em></span>
            <input id="{{ $formKey }}-helper-fil" type="text" name="helper_fil" class="cat-input"
                   value="{{ $value('helper_fil') }}" maxlength="160">
        </label>
    </div>

    <p class="cat-form__hint">{{ __('admin_categories.form.label_hint') }}</p>

    <fieldset class="cat-fieldset">
        <legend>{{ __('admin_categories.form.icon') }}</legend>
        <p class="cat-form__hint">{{ __('admin_categories.form.icon_hint') }}</p>
        <div class="cat-choice-grid cat-choice-grid--icons">
            @foreach($icons as $icon => $label)
                <label class="cat-choice cat-choice--icon" for="{{ $formKey }}-icon-{{ $icon }}">
                    <input id="{{ $formKey }}-icon-{{ $icon }}" type="radio" name="icon" value="{{ $icon }}" @checked($selectedIcon === $icon)>
                    <span class="tile__icon tile__icon--{{ $selectedColor }}">
                        @include('complaints.partials.tile-icon', ['icon' => $icon])
                    </span>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @if($isActiveForm && $errors->has('icon'))<span class="cat-field__error">{{ $errors->first('icon') }}</span>@endif
    </fieldset>

    <fieldset class="cat-fieldset">
        <legend>{{ __('admin_categories.form.color') }}</legend>
        <p class="cat-form__hint">{{ __('admin_categories.form.color_hint') }}</p>
        <div class="cat-choice-grid cat-choice-grid--colors">
            @foreach($colors as $color => $label)
                <label class="cat-choice cat-choice--color" for="{{ $formKey }}-color-{{ $color }}">
                    <input id="{{ $formKey }}-color-{{ $color }}" type="radio" name="color" value="{{ $color }}" @checked($selectedColor === $color)>
                    <span class="cat-swatch cat-swatch--{{ $color }}" aria-hidden="true"></span>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @if($isActiveForm && $errors->has('color'))<span class="cat-field__error">{{ $errors->first('color') }}</span>@endif
    </fieldset>

    <fieldset class="cat-fieldset">
        <legend>{{ __('admin_categories.form.intake') }}</legend>
        <p class="cat-form__hint">{{ __('admin_categories.form.intake_hint') }}</p>
        <div class="cat-intake-grid">
            @foreach($intakeModes as $modeKey => $intakeMode)
                <label class="cat-choice cat-choice--intake" for="{{ $formKey }}-intake-{{ $modeKey }}">
                    <input id="{{ $formKey }}-intake-{{ $modeKey }}" type="radio" name="intake_mode" value="{{ $modeKey }}" @checked($selectedIntake === $modeKey)>
                    <span class="cat-choice__copy">
                        <strong>{{ $intakeMode['label'] }}</strong>
                        <span>{{ $intakeMode['helper'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @if($isActiveForm && $errors->has('intake_mode'))<span class="cat-field__error">{{ $errors->first('intake_mode') }}</span>@endif
    </fieldset>

    <div class="cat-form__grid cat-form__grid--short">
        <label class="cat-field" for="{{ $formKey }}-sort">
            <span>{{ __('admin_categories.form.sort_order') }} <em>{{ __('admin_categories.form.optional') }}</em></span>
            <input id="{{ $formKey }}-sort" type="number" name="sort_order" class="cat-input" min="0" max="9999"
                   value="{{ $value('sort_order') }}" inputmode="numeric">
            <span class="cat-form__hint">{{ __('admin_categories.form.sort_order_hint') }}</span>
        </label>

        @if($category !== null)
            <div class="cat-field">
                <span>{{ __('admin_categories.categories.key') }}</span>
                <code class="cat-key">{{ $category->key }}</code>
                <span class="cat-form__hint">{{ __('admin_categories.categories.key_hint') }}</span>
            </div>
        @endif
    </div>

    <div class="cat-form__actions">
        <button type="submit" class="cat-btn cat-btn--primary">
            {{ $mode === 'edit' ? __('admin_categories.actions.save') : __('admin_categories.actions.create_category') }}
        </button>
    </div>
</form>
