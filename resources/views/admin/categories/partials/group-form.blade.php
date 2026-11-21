@php
    /** @var \App\Models\ComplaintCategoryGroup|null $group */
    $group ??= null;
    $mode ??= 'create';
    $formKey ??= $mode === 'edit' ? 'group-edit-'.$group?->id : 'group-create';
    $isActiveForm = old('_form') === $formKey;
    $value = fn (string $field) => $isActiveForm ? old($field, $group?->{$field}) : ($group?->{$field});
    $selectedIcon = $value('icon') ?: 'circle-help';
    $selectedColor = $value('color') ?: 'other';
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
        <p class="cat-form__lede">{{ __('admin_categories.form.problem_type_create_helper') }}</p>
    </div>

    <div class="cat-form__grid">
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

    <div class="cat-form__grid cat-form__grid--short">
        <label class="cat-field" for="{{ $formKey }}-sort">
            <span>{{ __('admin_categories.form.sort_order') }} <em>{{ __('admin_categories.form.optional') }}</em></span>
            <input id="{{ $formKey }}-sort" type="number" name="sort_order" class="cat-input" min="0" max="9999"
                   value="{{ $value('sort_order') }}" inputmode="numeric">
            <span class="cat-form__hint">{{ __('admin_categories.form.sort_order_hint') }}</span>
        </label>

        @if($group !== null)
            <div class="cat-field">
                <span>{{ __('admin_categories.categories.key') }}</span>
                <code class="cat-key">{{ $group->key }}</code>
                <span class="cat-form__hint">{{ __('admin_categories.categories.key_hint') }}</span>
            </div>
        @endif
    </div>

    <div class="cat-form__actions">
        <button type="submit" class="cat-btn cat-btn--primary">
            {{ $mode === 'edit' ? __('admin_categories.actions.save') : __('admin_categories.actions.create_problem_type') }}
        </button>
    </div>
</form>
