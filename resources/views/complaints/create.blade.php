@extends($mainLayout)

@section('title', __('complaint_create.page.title').' - Daet Listens')

@section('body-class', 'page-complaint-create')

@section('content')
    <script>document.documentElement.classList.add('js');</script>
    <div class="fc-page">
        <div class="fc-back-row">
            <div class="fc-container">
                <a href="{{ route('complaints.index') }}" class="fc-back" aria-label="{{ __('complaint_create.back') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>
                    <span class="fc-back__text">{{ __('complaint_create.back') }}</span>
                </a>
            </div>
        </div>

        <header class="fc-banner">
            <div class="fc-container">
                <h1>{{ __('complaint_create.page.title') }}</h1>
                <p class="fc-lede">{{ __('complaint_create.page.lede') }}</p>
            </div>
        </header>

        @php
            $selectedValue = old('category');
            $selectedGroup = null;
            $selectedLeaf = null;
            if ($selectedValue && isset($categoryGroups)) {
                foreach ($categoryGroups as $group) {
                    foreach ($group['children'] as $leaf) {
                        if ($leaf['value'] === $selectedValue) {
                            $selectedGroup = $group;
                            $selectedLeaf = $leaf;
                            break 2;
                        }
                    }
                }
            }
        @endphp

        <main class="fc-container fc-stack">
            <div class="fc-progress wizard-progress-header" id="wizard-progress-header">
                <div class="fc-progress__meta">
                    <span class="fc-progress__step" id="wizard-step-counter">{{ __('complaint_create.progress.step', ['n' => 1, 'total' => 8]) }}</span>
                    <span class="fc-progress__name" id="wizard-step-label">{{ __('complaint_create.steps.group') }}</span>
                </div>
                <div class="fc-progress__segments" id="wizard-segments" role="progressbar" aria-valuemin="1" aria-valuemax="8" aria-valuenow="1" aria-label="{{ __('complaint_create.steps.group') }}">
                    @for ($i = 1; $i <= 8; $i++)
                        <span class="fc-progress__seg @if($i === 1) fc-progress__seg--current @endif"></span>
                    @endfor
                </div>
            </div>

            <div id="department-hint" class="fc-chip" role="status" aria-live="polite" @if(!$selectedLeaf) hidden @else data-visible="true" @endif>
                <div class="fc-chip__row">
                    <span class="fc-chip__icon tile__icon--{{ $selectedGroup['color'] ?? 'other' }}" id="chip-icon" aria-hidden="true">
                        @if($selectedGroup)
                            @include('complaints.partials.tile-icon', ['icon' => $selectedGroup['icon']])
                        @else
                            @include('complaints.partials.tile-icon', ['icon' => 'circle-help'])
                        @endif
                    </span>
                    <span class="fc-chip__text">
                        <span class="fc-chip__pair"><span id="chip-group">{{ $selectedGroup['label'] ?? '' }}</span> › <span id="chip-leaf">{{ $selectedLeaf['label'] ?? '' }}</span></span>
                        <span class="fc-chip__office">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M2 22h20"></path><path d="M9 6h1"></path><path d="M14 6h1"></path><path d="M9 10h1"></path><path d="M14 10h1"></path><path d="M9 14h1"></path><path d="M14 14h1"></path></svg>
                            <span id="chip-prefix"></span><span id="department-name">{{ $selectedLeaf['department'] ?? '' }}</span>
                        </span>
                    </span>
                    <button type="button" class="fc-chip__change" id="chip-change">{{ __('complaint_create.buttons.change') }}</button>
                </div>
            </div>

            <div class="fc-draft wizard-draft-banner" id="wizard-draft-banner" hidden>
                <span>{{ __('complaint_create.draft.banner') }}</span>
                <div class="fc-draft__actions">
                    <button type="button" class="fc-draft__resume" id="wizard-draft-resume">{{ __('complaint_create.draft.resume') }}</button>
                    <button type="button" class="fc-draft__reset" id="wizard-draft-reset">{{ __('complaint_create.draft.reset') }}</button>
                </div>
            </div>

            <div class="fc-layout">
                <form data-loading-submit method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" id="complaint-form" class="fc-form">
                    @csrf

                    @if($errors->any())
                        <div class="fc-error-banner" role="alert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                            <div>
                                <strong>Check the highlighted fields.</strong>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <section class="fc-card" id="filing-category" aria-labelledby="category-card-heading">
                        <div class="fc-card__head">
                            <span class="fc-section-eyebrow">{{ __('complaint_create.sections.step', ['number' => '01']) }}</span>
                            <h2 id="category-card-heading">What are you reporting?</h2>
                            <p>Choose the closest category so we can route it correctly.</p>
                        </div>
                        <div class="fc-card__body">
                            <div class="fc-field fc-field--category-source">
                                <label class="fc-field__source-label" for="category">{{ __('complaint_create.labels.category') }} <span aria-hidden="true">*</span></label>
                                <p class="fc-field__hint fc-field__source-hint">Pick the main issue. You can add more context in the description.</p>
                                <select id="category" name="category" class="fc-select fc-select--source" required>
                                    <option value="">- Select a category -</option>
                                    @foreach($categoryOptions as $department => $options)
                                        <optgroup label="{{ $department }}">
                                            @foreach($options as $option)
                                                <option value="{{ $option['value'] }}" @selected(old('category') === $option['value'])>{{ $option['label'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('category') <div class="fc-error">{{ $message }}</div> @enderror
                            </div>

                            <div data-wizard-step="group" role="group" aria-labelledby="step-group-heading">
                                <h2 class="fc-step-title" id="step-group-heading" tabindex="-1">{{ __('complaint_create.screens.group.title') }}</h2>
                                <p class="fc-step-help">{{ __('complaint_create.screens.group.help') }}</p>
                                <div class="tile-grid tile-grid--groups" role="radiogroup" aria-labelledby="step-group-heading">
                                    @foreach($categoryGroups as $group)
                                        <label class="tile tile--group" data-group="{{ $group['key'] }}">
                                            <input type="radio" name="category_group" value="{{ $group['key'] }}" class="fc-sr-only" @checked($selectedGroup && $selectedGroup['key'] === $group['key'])>
                                            <span class="tile__icon tile__icon--{{ $group['color'] }}" aria-hidden="true">@include('complaints.partials.tile-icon', ['icon' => $group['icon']])</span>
                                            <span class="tile__label">{{ $group['label'] }}</span>
                                            <span class="tile__helper">{{ $group['helper'] }}</span>
                                            <span class="tile__check tile__check--hidden" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div data-wizard-step="category" role="group" aria-labelledby="step-category-heading">
                                <h2 class="fc-step-title" id="step-category-heading" tabindex="-1">{{ __('complaint_create.screens.category.title') }}</h2>
                                <p class="fc-step-help">{{ __('complaint_create.screens.category.help') }}</p>
                                @foreach($categoryGroups as $group)
                                    <div class="tile-panel" data-group-panel="{{ $group['key'] }}" @if(!$selectedGroup || $selectedGroup['key'] !== $group['key']) hidden @endif>
                                        <div class="fc-category-group-label">
                                            <span class="fc-category-group-name">{{ $group['label'] }}</span>
                                            <button type="button" class="fc-category-group-change" data-category-group-change>{{ __('complaint_create.buttons.change') }}</button>
                                        </div>
                                        <div class="tile-grid tile-grid--leaves" role="radiogroup" aria-labelledby="step-category-heading">
                                            @foreach($group['children'] as $leaf)
                                                <label class="tile tile--leaf" data-value="{{ $leaf['value'] }}">
                                                    <input type="radio" name="category_choice" value="{{ $leaf['value'] }}" class="fc-sr-only" @checked(old('category') === $leaf['value'])>
                                                    <span class="tile__icon tile__icon--{{ $leaf['color'] }}" aria-hidden="true">@include('complaints.partials.tile-icon', ['icon' => $leaf['icon']])</span>
                                                    <span class="tile__body">
                                                        <span class="tile__label">{{ $leaf['label'] }}</span>
                                                        <span class="tile__helper">{{ $leaf['helper'] }}</span>
                                                    </span>
                                                    <span class="tile__radio" aria-hidden="true"></span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <div class="tile-error" id="category-tile-error" role="alert" hidden></div>
                            </div>
                        </div>
                    </section>

                    <section class="fc-card" id="filing-details" aria-labelledby="details-card-heading">
                        <div class="fc-card__head">
                            <span class="fc-section-eyebrow">{{ __('complaint_create.sections.step', ['number' => '02']) }}</span>
                            <h2 id="details-card-heading">Give us the useful details.</h2>
                            <p>Clear facts help the department verify and act faster.</p>
                        </div>
                        <div class="fc-card__body">
                            <div data-wizard-step="describe" role="group" aria-labelledby="step-describe-heading">
                                <h2 class="fc-step-title" id="step-describe-heading" tabindex="-1">Give your report a short title</h2>
                                <p class="fc-step-help">Summarize the problem in one sentence.</p>
                                <div class="fc-field">
                                    <label for="title">Short title <span aria-hidden="true">*</span></label>
                                    <p class="fc-field__hint">Summarize the problem in one sentence.</p>
                                    <input id="title" name="title" class="fc-input" type="text" maxlength="255" placeholder="e.g. Fallen tree blocking the road" value="{{ old('title') }}" required>
                                    <p class="fc-counter" id="title-counter" aria-live="polite"></p>
                                    @error('title') <div class="fc-error">{{ $message }}</div> @enderror
                                </div>
                                <div class="fc-field">
                                    <label for="description">What happened? <span aria-hidden="true">*</span></label>
                                    <p class="fc-field__hint">Include when it started, who is affected, and any immediate risk. Minimum 20 characters.</p>
                                    <textarea id="description" name="description" class="fc-textarea" minlength="20" placeholder="Describe what you observed and how it affects the community..." required>{{ old('description') }}</textarea>
                                    <p class="fc-counter" id="description-counter" aria-live="polite"></p>
                                    @error('description') <div class="fc-error">{{ $message }}</div> @enderror
                                </div>
                                <button type="button" class="fc-toggle" id="priority-toggle" aria-expanded="false" aria-controls="priority-notice">{{ __('complaint_create.wizard.priority_toggle') }}</button>
                                <div class="fc-notice" id="priority-notice">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7l8 4 8-4-8-4Z"></path><path d="M4 12h16"></path><path d="M4 17h16"></path><path d="m4 7 8 4 8-4"></path></svg>
                                    <span><strong>No urgency selection is needed.</strong> The system will suggest a priority from your category and description. The responsible department verifies the report and confirms the final priority.</span>
                                </div>
                            </div>

                            <div data-wizard-step="photos" role="group" aria-labelledby="step-photos-heading">
                                <h2 class="fc-step-title" id="step-photos-heading" tabindex="-1">Add photo evidence</h2>
                                <p class="fc-step-help" id="photos-step-help">Required. Add up to five clear photos taken at the issue location.</p>
                                <div class="fc-field">
                                    <label for="images-input" id="photos-field-label">Photo evidence <span aria-hidden="true" id="photos-required-mark">*</span></label>
                                    <p class="fc-field__hint" id="photo-tips-hint">Required. Add up to five clear photos taken at the issue location. GPS from any photo is used when available; add a landmark or map point as a fallback. JPG, PNG, or WEBP, up to 5 MB each.</p>
                                    <button type="button" class="fc-toggle" id="photo-tips-toggle" aria-expanded="false" aria-controls="photo-tips-hint">{{ __('complaint_create.wizard.photo_tips') }}</button>
                                    <div class="upload-zone" id="upload-zone">
                                        <input type="file" name="images[]" id="images-input" accept="image/jpeg,image/png,image/webp" multiple>
                                        <input type="file" name="camera_photo" id="camera-input" accept="image/jpeg,image/png,image/webp" capture="environment">
                                        <div class="upload-zone__icon">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                                        </div>
                                        <strong>Add up to five photos</strong>
                                        <span>Choose from your device or use your phone’s camera.</span>
                                        <div class="upload-zone__actions">
                                            <button type="button" class="upload-zone__action" data-upload-trigger="images-input">Choose photos</button>
                                            <button type="button" class="upload-zone__action upload-zone__action--camera" data-upload-trigger="camera-input">Use camera</button>
                                        </div>
                                        <div class="upload-zone__counter"><strong id="photo-count">0 / 5</strong> photos attached</div>
                                        <div class="upload-zone__previews" id="upload-previews" aria-live="polite"></div>
                                        <div class="upload-error" id="upload-client-error" role="alert" hidden></div>
                                    </div>
                                    @error('images') <div class="fc-error">{{ $message }}</div> @enderror
                                    @error('camera_photo') <div class="fc-error">{{ $message }}</div> @enderror
                                    @error('image') <div class="fc-error">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="fc-card" id="filing-location" aria-labelledby="location-card-heading">
                        <div class="fc-card__head">
                            <span class="fc-section-eyebrow">{{ __('complaint_create.sections.step', ['number' => '03']) }}</span>
                            <h2 id="location-card-heading">Where is the issue?</h2>
                            <p>Photo GPS is used when available. Add a landmark or map point as a fallback.</p>
                        </div>
                        <div class="fc-card__body">
                            <div data-wizard-step="where" role="group" aria-labelledby="step-where-heading">
                                <h2 class="fc-step-title" id="step-where-heading" tabindex="-1">Where is the issue?</h2>
                                <p class="fc-step-help">Prefilled from your account. Change it if the problem is somewhere else.</p>
                                <div class="fc-field">
                                    <label for="barangay">Which barangay <span aria-hidden="true">*</span></label>
                                    <p class="fc-field__hint">Prefilled from your account. Change it if the problem is somewhere else.</p>
                                    <input
                                        id="barangay"
                                        name="barangay"
                                        class="fc-input"
                                        type="search"
                                        list="complaint-barangay-options"
                                        autocomplete="off"
                                        value="{{ old('barangay', auth()->user()?->barangay ?? '') }}"
                                    >
                                    <datalist id="complaint-barangay-options">
                                        @foreach (\App\Models\User::BARANGAYS as $barangayOption)
                                            <option value="{{ $barangayOption }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('barangay') <div class="fc-error">{{ $message }}</div> @enderror
                                </div>

                                <button type="button" class="fc-toggle" id="landmark-toggle" aria-expanded="false" aria-controls="landmark-field">{{ __('complaint_create.wizard.landmark_toggle') }}</button>
                                <div class="fc-field" id="landmark-field">
                                    <label for="address_text" id="address-text-label">Address or nearest landmark <span id="address-text-required-mark">({{ __('complaint_create.labels.optional') }})</span></label>
                                    <p class="fc-field__hint">Example: in front of Daet Public Market, Barangay VI.</p>
                                    <input id="address_text" name="address_text" class="fc-input" type="text" maxlength="500" placeholder="Describe the location..." value="{{ old('address_text') }}">
                                    @error('address_text') <div class="fc-error">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div data-wizard-step="pin" role="group" aria-labelledby="step-pin-heading">
                                <h2 class="fc-step-title" id="step-pin-heading" tabindex="-1">Drop a map pin</h2>
                                <p class="fc-step-help">{{ __('complaint_create.map.help') }}</p>
                                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                                <div id="complaint-map" class="filing-map fc-map" aria-label="Map for selecting the complaint location"></div>
                                <p class="fc-map-note">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                                    {{ __('complaint_create.map.help') }}
                                </p>

                                <button type="button" class="fc-locate" id="locate-me" data-locate-button>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M12 2v3"></path><path d="M12 19v3"></path><path d="M2 12h3"></path><path d="M19 12h3"></path></svg>
                                    Use my current location
                                </button>
                                <button type="button" class="fc-locate fc-locate--remove" id="remove-pin" data-remove-pin hidden>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                    Remove pin
                                </button>
                                <p class="fc-locate-status" id="locate-status" role="status" aria-live="polite" hidden></p>

                                @if ($locationConflict)
                                    <div class="location-choice">
                                        <h3 class="location-choice__title">Where is the problem?</h3>
                                        <p class="location-choice__note">One of your photos was taken in a different area from the point you marked on the map. Pick the place the problem is actually at.</p>

                                        <fieldset class="location-choice__options">
                                            <legend class="fc-sr-only">Which location is the problem at?</legend>

                                            <label class="location-choice__option">
                                                <input type="radio" name="location_choice" value="map"
                                                       @checked(old('location_choice', \App\Http\Controllers\ComplaintController::LOCATION_CHOICE_MAP) === 'map')>
                                                <span>
                                                    <span class="location-choice__label">The point I marked on the map</span>
                                                    <span class="location-choice__coords">
                                                        {{ $locationConflict['map']['barangay'] ?? 'Location not recognised' }}
                                                    </span>
                                                </span>
                                            </label>

                                            <label class="location-choice__option">
                                                <input type="radio" name="location_choice" value="photo"
                                                       @checked(old('location_choice', \App\Http\Controllers\ComplaintController::LOCATION_CHOICE_PHOTO) === 'photo')>
                                                <span>
                                                    <span class="location-choice__label">Where the photo was taken</span>
                                                    <span class="location-choice__coords">
                                                        {{ $locationConflict['photo']['barangay'] ?? 'Location not recognised' }}
                                                    </span>
                                                </span>
                                            </label>
                                        </fieldset>

                                        @error('location_choice')
                                            <p class="fc-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                                @error('latitude') <div class="fc-error">{{ $message }}</div> @enderror
                                @error('longitude') <div class="fc-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    {{--
                        Public visibility is the resident's decision, and private
                        is the default. Real radio inputs rather than a switch,
                        because the choice is between two equally weighted
                        outcomes and both need to be readable before one is
                        picked.
                    --}}
                    <section class="fc-card" id="filing-visibility" aria-labelledby="visibility-card-heading">
                        <div class="fc-card__head">
                            <span class="fc-section-eyebrow">{{ __('complaint_create.sections.step', ['number' => '04']) }}</span>
                            <h2 id="visibility-card-heading">Who can see this</h2>
                            <p>You decide this. No member of staff can change it later.</p>
                        </div>
                        <div class="fc-card__body">
                            <div data-wizard-step="visibility" role="group" aria-labelledby="step-visibility-heading">
                                <h2 class="fc-step-title" id="step-visibility-heading" tabindex="-1">Who can see this</h2>
                                <p class="fc-step-help">You decide this. No member of staff can change it later.</p>
                                <fieldset class="visibility-choice" @disabled(old('visibility', 'private') === 'public')>
                                    <legend class="fc-sr-only">Who can see this complaint</legend>

                                    <label class="visibility-option">
                                        <input type="radio" name="visibility" value="private" @checked(old('visibility', 'private') === 'private')>
                                        <span class="visibility-option__body">
                                            <span class="visibility-option__title">Just me</span>
                                            <span class="visibility-option__note">Only you and the staff handling your report can see it. It will not appear on the public page.</span>
                                        </span>
                                    </label>

                                    <label class="visibility-option">
                                        <input type="radio" name="visibility" value="public" @checked(old('visibility') === 'public')>
                                        <span class="visibility-option__body">
                                            <span class="visibility-option__title">Publish on the transparency page</span>
                                            <span class="visibility-option__note">Anyone can read it there under your display alias, never your real name. You can make it private again at any time.</span>
                                        </span>
                                    </label>
                                </fieldset>

                                @error('visibility') <div class="fc-error">{{ $message }}</div> @enderror

                                <button type="button" class="fc-toggle" id="visibility-why-toggle" aria-expanded="false" aria-controls="visibility-footnote">{{ __('complaint_create.wizard.why_toggle') }}</button>
                                <p class="visibility-footnote" id="visibility-footnote">
                                    Reports that turn out to be spam or unrelated to municipal services are held back automatically. Staff may also
                                    remove sensitive details from the public page, but they cannot change this choice - and you will always see your
                                    own report exactly as you filed it.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="fc-card" id="filing-review" aria-labelledby="review-card-heading">
                        <div class="fc-card__head">
                            <span class="fc-section-eyebrow">{{ __('complaint_create.sections.step', ['number' => '05']) }}</span>
                            <h2 id="review-card-heading">Review and submit</h2>
                            <p>Check the details before sending.</p>
                        </div>
                        <div class="fc-card__body">
                            <div data-wizard-step="review" role="group" aria-labelledby="step-review-heading">
                                <h2 class="fc-step-title" id="step-review-heading" tabindex="-1">Review &amp; submit</h2>
                                <div class="fc-summary" aria-live="polite">
                                    <h3>Ready to submit?</h3>
                                    <div class="fc-summary__row"><span>Category</span><strong id="review-category">Not selected</strong><button type="button" class="fc-summary__edit fc-summary__edit--wizard" data-edit-step="category">{{ __('complaint_create.wizard.edit') }}</button></div>
                                    <div class="fc-summary__row"><span>Department</span><strong id="review-department">Not routed yet</strong><button type="button" class="fc-summary__edit fc-summary__edit--wizard" data-edit-step="category">{{ __('complaint_create.wizard.edit') }}</button></div>
                                    <div class="fc-summary__row"><span>Location</span><strong id="review-location">No location added</strong><button type="button" class="fc-summary__edit fc-summary__edit--wizard" data-edit-step="where">{{ __('complaint_create.wizard.edit') }}</button></div>
                                    <div class="fc-summary__row fc-summary__row--wizard"><span>{{ __('complaint_create.wizard.title_label') }}</span><strong id="review-title">Not provided</strong><button type="button" class="fc-summary__edit" data-edit-step="describe">{{ __('complaint_create.wizard.edit') }}</button></div>
                                    <div class="fc-summary__row fc-summary__row--wizard"><span>{{ __('complaint_create.wizard.description_label') }}</span><strong id="review-description" class="fc-review-desc">Not provided</strong><button type="button" class="fc-summary__edit" data-edit-step="describe">{{ __('complaint_create.wizard.edit') }}</button></div>
                                    <div class="fc-summary__row fc-summary__row--wizard fc-summary__row--photos"><span>{{ __('complaint_create.wizard.photos_label') }}</span><div class="fc-review-photos"><strong id="review-photos-count">0 / 5</strong><div class="fc-review-thumbs" id="review-photos-thumbs"></div></div><button type="button" class="fc-summary__edit" data-edit-step="photos">{{ __('complaint_create.wizard.edit') }}</button></div>
                                    <div class="fc-summary__row fc-summary__row--wizard"><span>{{ __('complaint_create.wizard.visibility_label') }}</span><strong id="review-visibility">Just me</strong><button type="button" class="fc-summary__edit" data-edit-step="visibility">{{ __('complaint_create.wizard.edit') }}</button></div>
                                </div>

                                <div class="fc-next-steps fc-next-steps--wizard">
                                    <h3>{{ __('complaint_create.wizard.what_next') }}</h3>
                                    <ul>
                                        <li>{{ __('complaint_create.wizard.next_1') }}</li>
                                        <li>{{ __('complaint_create.wizard.next_2') }}</li>
                                        <li>{{ __('complaint_create.wizard.next_3') }}</li>
                                    </ul>
                                </div>

                                <div class="fc-terms">
                                    <input type="checkbox" name="terms" id="terms" value="1" @checked(old('terms')) required>
                                    <label for="terms">I confirm that the information provided is accurate. I understand that the department will verify this report before deciding its priority and next action. <a href="#" target="_blank" rel="noopener">Read the complaint policy.</a></label>
                                </div>
                                @error('terms') <div class="fc-error">{{ $message }}</div> @enderror

                                <button type="submit" class="fc-submit" id="submit-btn">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                                    Submit for department review
                                </button>
                            </div>
                        </div>
                    </section>

                    <div class="wizard-action-bar" id="wizard-action-bar">
                        <button type="button" class="wizard-btn-back" id="wizard-btn-back">{{ __('complaint_create.buttons.back') }}</button>
                        <button type="button" class="wizard-btn-next" id="wizard-btn-next">{{ __('complaint_create.buttons.next') }}</button>
                        <button type="submit" class="wizard-btn-next wizard-btn-submit" id="wizard-btn-submit" hidden>{{ __('complaint_create.buttons.submit') }}</button>
                    </div>
                    <div class="wizard-hint-pill" id="wizard-hint" role="alert" hidden>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        <span id="wizard-hint-text"></span>
                    </div>
                </form>

                <aside class="fc-aside" aria-label="Filing guidance">
                    <div class="fc-aside__card">
                        <h2>Before you submit</h2>
                        <ul class="fc-aside__list">
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><circle cx="8" cy="9" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                                <span>Take one or more clear photos at the issue location; keep location services enabled when possible.</span>
                            </li>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
                                <span>Describe what you observed, not what you assume caused it.</span>
                            </li>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                                <span>Add a nearby landmark or map pin when possible.</span>
                            </li>
                            <li>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18"></path><path d="M3 12h18"></path><circle cx="12" cy="12" r="9"></circle></svg>
                                <span>For immediate danger, contact local emergency services first.</span>
                            </li>
                        </ul>
                    </div>
                    <div class="fc-aside__card">
                        <h2>What happens next</h2>
                        <ol class="fc-aside__next">
                            <li><span class="fc-aside__num">1</span><span><strong>Department review</strong><span>Your report is checked for legitimacy and scope.</span></span></li>
                            <li><span class="fc-aside__num">2</span><span><strong>Priority confirmed</strong><span>The assigned office confirms the suggested priority.</span></span></li>
                            <li><span class="fc-aside__num">3</span><span><strong>Status updates</strong><span>You can follow progress from My Complaints.</span></span></li>
                        </ol>
                    </div>
                </aside>
            </div>
        </main>

        <span id="wizard-live" class="fc-sr-only" aria-live="polite"></span>
    </div>

    <script id="category-department-data" type="application/json">{!! json_encode(
        collect($categoryOptions)
            ->mapWithKeys(fn ($options, $department) => collect($options)->mapWithKeys(fn ($option) => [$option['value'] => $department]))
            ->all(),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
    ) !!}</script>
    <script id="intake-policy-data" type="application/json">{!! json_encode($intakePoliciesByCategory ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script id="complaint-create-i18n" type="application/json">{!! json_encode([
        'steps' => [
            'group' => __('complaint_create.steps.group'),
            'category' => __('complaint_create.steps.category'),
            'describe' => __('complaint_create.steps.describe'),
            'photos' => __('complaint_create.steps.photos'),
            'where' => __('complaint_create.steps.where'),
            'pin' => __('complaint_create.steps.pin'),
            'visibility' => __('complaint_create.steps.visibility'),
            'review' => __('complaint_create.steps.review'),
        ],
        'buttons' => [
            'back' => __('complaint_create.buttons.back'),
            'next' => __('complaint_create.buttons.next'),
            'skip' => __('complaint_create.buttons.skip'),
            'continue' => __('complaint_create.buttons.continue'),
            'submit' => __('complaint_create.buttons.submit'),
            'change' => __('complaint_create.buttons.change'),
        ],
        'hints' => [
            'group' => __('complaint_create.hints.group'),
            'category' => __('complaint_create.hints.category'),
            'describe' => __('complaint_create.hints.describe'),
            'photos' => __('complaint_create.hints.photos'),
            'where' => __('complaint_create.hints.where'),
            'where_landmark' => __('complaint_create.hints.where_landmark'),
            'review' => __('complaint_create.hints.review'),
        ],
        'map' => [
            'outside' => __('complaint_create.map.outside'),
            'previousOutside' => __('complaint_create.map.previous_outside'),
        ],
        'progressStep' => __('complaint_create.progress.step'),
        'liveStep' => __('complaint_create.live.step'),
        'chipGoesTo' => __('complaint_create.chip.goes_to'),
        'descMore' => __('complaint_create.wizard.desc_more'),
        'descGood' => __('complaint_create.wizard.desc_good'),
        'takePhoto' => __('complaint_create.wizard.take_photo'),
        'chooseGallery' => __('complaint_create.wizard.choose_gallery'),
        'photosRequiredHelp' => __('complaint_create.wizard.photos_required_help'),
        'photosOptionalHelp' => __('complaint_create.wizard.photos_optional_help'),
        'optionalMark' => ' ('.__('complaint_create.labels.optional').')',
        'justMe' => 'Just me',
        'publishPage' => 'Publish on the transparency page',
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script id="daet-boundary-data" type="application/json">{!! json_encode([
        'geojson' => $daetBoundary,
        'toleranceMetres' => $daetBoundaryTolerance,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script>
        (() => {
            const initializeFilingPage = () => {
                const form = document.getElementById('complaint-form');
                if (!form || form.dataset.filingInitialized === 'true') return;
                form.dataset.filingInitialized = 'true';
                document.body.classList.add('page-complaint-create');

                const page = document.querySelector('.fc-page');
                const imagesInput = document.getElementById('images-input');
                const cameraInput = document.getElementById('camera-input');
                const uploadZone = document.getElementById('upload-zone');
                const uploadPreviews = document.getElementById('upload-previews');
                const photoCount = document.getElementById('photo-count');
                const clientUploadError = document.getElementById('upload-client-error');
                const categorySelect = document.getElementById('category');
                const departmentHint = document.getElementById('department-hint');
                const departmentName = document.getElementById('department-name');
                const addressInput = document.getElementById('address_text');
                const reviewCategory = document.getElementById('review-category');
                const reviewDepartment = document.getElementById('review-department');
                const reviewLocation = document.getElementById('review-location');
                const submitButton = document.getElementById('submit-btn');
                const categoryDataElement = document.getElementById('category-department-data');
                const intakePolicyDataElement = document.getElementById('intake-policy-data');
                const i18nElement = document.getElementById('complaint-create-i18n');

                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                const mapElement = document.getElementById('complaint-map');
                const barangayInput = document.getElementById('barangay');

                const stepCounter = document.getElementById('wizard-step-counter');
                const stepLabel = document.getElementById('wizard-step-label');
                const segmentsEl = document.getElementById('wizard-segments');
                const draftBanner = document.getElementById('wizard-draft-banner');
                const draftResumeBtn = document.getElementById('wizard-draft-resume');
                const btnBack = document.getElementById('wizard-btn-back');
                const btnNext = document.getElementById('wizard-btn-next');
                const btnSubmitBar = document.getElementById('wizard-btn-submit');
                const hintPill = document.getElementById('wizard-hint');
                const hintText = document.getElementById('wizard-hint-text');
                const actionBar = document.getElementById('wizard-action-bar');
                const liveRegion = document.getElementById('wizard-live');
                const chipGroup = document.getElementById('chip-group');
                const chipLeaf = document.getElementById('chip-leaf');
                const chipIcon = document.getElementById('chip-icon');
                const chipPrefix = document.getElementById('chip-prefix');
                const chipChangeBtn = document.getElementById('chip-change');
                const categoryTileError = document.getElementById('category-tile-error');

                let i18n = {
                    steps: { group: 'Problem type', category: 'Category', describe: 'Details', photos: 'Photos', where: 'Location', pin: 'Map pin', visibility: 'Privacy', review: 'Review' },
                    buttons: { back: 'Back', next: 'Next', skip: 'Skip', continue: 'Continue', submit: 'Submit complaint', change: 'Change' },
                    hints: { group: 'Please choose a problem type.', category: 'Please choose what fits best.', describe: 'Add a short title and at least 20 characters of detail.', photos: 'Add at least one photo.', where: 'Choose your barangay.', review: 'Please tick the box to confirm.' },
                    map: { outside: 'That point is outside Daet. Place the pin inside the city.', previousOutside: 'Your previous pin was outside Daet and was removed. Place a new pin, or skip this step.' },
                    progressStep: 'Step {n} of {total}',
                    liveStep: 'Step {n} of {total}: {name}',
                    chipGoesTo: 'Goes to {office}',
                };

                try {
                    const parsed = JSON.parse(i18nElement?.textContent || '{}');
                    if (parsed && typeof parsed === 'object') {
                        i18n = Object.assign({}, i18n, parsed);
                    }
                } catch (e) { /* keep defaults */ }

                const t = (template, vars) => {
                    let out = template;
                    Object.entries(vars || {}).forEach(([k, v]) => {
                        out = out.split(`{${k}}`).join(String(v));
                    });
                    return out;
                };

                const cssEscape = (s) => {
                    if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(String(s));
                    return String(s).replace(/["\\]/g, '\\$&');
                };

                // ── Daet municipal boundary (map pin jurisdiction) ──
                // The polygon is shipped as GeoJSON in the page so the map can
                // reject an out-of-town click before submit. The server repeats
                // the check with the same tolerance, so this is guidance, not the
                // security boundary.
                const boundaryConfig = (() => {
                    try {
                        const raw = document.getElementById('daet-boundary-data')?.textContent || 'null';
                        const parsed = JSON.parse(raw);
                        return parsed && typeof parsed === 'object' ? parsed : null;
                    } catch (e) {
                        return null;
                    }
                })();

                const boundaryFeature = (() => {
                    const feature = boundaryConfig?.geojson;
                    if (!feature) return null;
                    if (feature.type === 'FeatureCollection') return feature.features?.[0] || null;
                    if (feature.type === 'Feature') return feature;
                    return feature.geometry ? feature : { type: 'Feature', geometry: feature, properties: {} };
                })();

                const boundaryToleranceMetres = Number(boundaryConfig?.toleranceMetres ?? 0);

                const boundaryPolygons = (() => {
                    const geometry = boundaryFeature?.geometry;
                    if (!geometry) return [];
                    if (geometry.type === 'Polygon') return [geometry.coordinates];
                    if (geometry.type === 'MultiPolygon') return geometry.coordinates;
                    return [];
                })();

                const pointInBoundaryRing = (latitude, longitude, ring) => {
                    if (!Array.isArray(ring) || ring.length < 3) return false;

                    let inside = false;

                    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                        const xi = Number(ring[i][0]);
                        const yi = Number(ring[i][1]);
                        const xj = Number(ring[j][0]);
                        const yj = Number(ring[j][1]);

                        if (((yi > latitude) !== (yj > latitude))
                            && (longitude < ((xj - xi) * (latitude - yi)) / (yj - yi) + xi)) {
                            inside = !inside;
                        }
                    }

                    return inside;
                };

                const isInsideDaetBoundary = (latitude, longitude) => {
                    if (boundaryPolygons.length === 0) return true;

                    return boundaryPolygons.some((rings) => {
                        if (!pointInBoundaryRing(latitude, longitude, rings[0] || [])) return false;

                        return !rings.slice(1).some((hole) => pointInBoundaryRing(latitude, longitude, hole));
                    });
                };

                const distanceToBoundaryRingMetres = (latitude, longitude, ring) => {
                    if (!Array.isArray(ring) || ring.length === 0) return Infinity;

                    const earthRadius = 6378100;
                    const cosLatitude = Math.cos(latitude * Math.PI / 180);
                    let minimum = Infinity;

                    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                        const ax = (Number(ring[j][0]) - longitude) * Math.PI / 180 * earthRadius * cosLatitude;
                        const ay = (Number(ring[j][1]) - latitude) * Math.PI / 180 * earthRadius;
                        const bx = (Number(ring[i][0]) - longitude) * Math.PI / 180 * earthRadius * cosLatitude;
                        const by = (Number(ring[i][1]) - latitude) * Math.PI / 180 * earthRadius;
                        const dx = bx - ax;
                        const dy = by - ay;
                        const lengthSquared = (dx * dx) + (dy * dy);
                        const t = lengthSquared > 0
                            ? Math.max(0, Math.min(1, -((ax * dx) + (ay * dy)) / lengthSquared))
                            : 0;

                        minimum = Math.min(minimum, Math.hypot(ax + (t * dx), ay + (t * dy)));
                    }

                    return minimum;
                };

                const distanceToDaetBoundaryMetres = (latitude, longitude) => {
                    let minimum = Infinity;

                    boundaryPolygons.forEach((rings) => {
                        rings.forEach((ring) => {
                            minimum = Math.min(minimum, distanceToBoundaryRingMetres(latitude, longitude, ring));
                        });
                    });

                    return minimum;
                };

                const isLocationAllowed = (latitude, longitude) => {
                    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return false;
                    if (boundaryPolygons.length === 0) return true;
                    if (isInsideDaetBoundary(latitude, longitude)) return true;

                    const distance = distanceToDaetBoundaryMetres(latitude, longitude);

                    return Number.isFinite(distance) && distance <= boundaryToleranceMetres;
                };

                const daetBoundaryBounds = (() => {
                    let south = Infinity;
                    let west = Infinity;
                    let north = -Infinity;
                    let east = -Infinity;

                    boundaryPolygons.forEach((rings) => {
                        rings.forEach((ring) => {
                            ring.forEach((point) => {
                                const longitude = Number(point[0]);
                                const latitude = Number(point[1]);

                                if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

                                south = Math.min(south, latitude);
                                north = Math.max(north, latitude);
                                west = Math.min(west, longitude);
                                east = Math.max(east, longitude);
                            });
                        });
                    });

                    return Number.isFinite(south) ? [[south, west], [north, east]] : null;
                })();

                const WIZARD_BREAKPOINT = '(max-width: 767px)';
                // WIZARD_BREAKPOINT must match the CSS media query in
                // resources/css/pages/complaint-create.css.
                const WIZARD_STEPS = ['group', 'category', 'describe', 'photos', 'where', 'pin', 'visibility', 'review'];

                const mobileQuery = typeof window.matchMedia === 'function'
                    ? window.matchMedia(WIZARD_BREAKPOINT)
                    : { matches: false };
                const isWizardMode = () => Boolean(mobileQuery.matches);

                const currentUserId = '{{ auth()->id() ?? 'guest' }}';
                const draftKey = `wizard:v1:${currentUserId}`;
                let currentKey = 'group';
                let submitting = false;

                let categoryToDepartment = {};
                try {
                    categoryToDepartment = JSON.parse(categoryDataElement?.textContent || '{}');
                } catch (error) {
                    categoryToDepartment = {};
                }

                let intakePoliciesByCategory = {};
                try {
                    intakePoliciesByCategory = JSON.parse(intakePolicyDataElement?.textContent || '{}');
                } catch (error) {
                    intakePoliciesByCategory = {};
                }

                const intakePolicyForCategory = (value) => intakePoliciesByCategory[value] || intakePoliciesByCategory['others'] || { mode: 'physical_required', photos_required: true, photos_allowed: true, barangay_required: true, address_text_required: false, show_photos_step: true, show_location_steps: true };
                const currentIntakePolicy = () => intakePolicyForCategory(categorySelect?.value || '');

                // ── Tile data: read from DOM so server-rendered panels are truth ──
                const groupTiles = () => Array.from(document.querySelectorAll('.tile--group'));
                const leafTiles = () => Array.from(document.querySelectorAll('.tile--leaf'));
                const panels = () => Array.from(document.querySelectorAll('[data-group-panel]'));

                const getSelectedGroupKey = () => {
                    const checked = document.querySelector('input[name="category_group"]:checked');
                    if (checked && checked.value) return checked.value;
                    const val = categorySelect?.value || '';
                    if (!val) return '';
                    for (const panel of panels()) {
                        if (panel.querySelector(`input[name="category_choice"][value="${cssEscape(val)}"]`)) {
                            return panel.getAttribute('data-group-panel') || '';
                        }
                    }
                    return '';
                };

                const getSelectedGroupLeaves = (groupKey) => {
                    const panel = document.querySelector(`[data-group-panel="${cssEscape(groupKey)}"]`);
                    if (!panel) return [];
                    return Array.from(panel.querySelectorAll('input[name="category_choice"]'));
                };

                const visibleSteps = () => {
                    const policy = currentIntakePolicy();
                    let steps = WIZARD_STEPS.slice();
                    const groupKey = getSelectedGroupKey();
                    if (groupKey) {
                        const leaves = getSelectedGroupLeaves(groupKey);
                        if (leaves.length === 1 && categorySelect?.value) {
                            steps = steps.filter((k) => k !== 'category');
                        }
                    }
                    if (!policy.show_photos_step) steps = steps.filter((k) => k !== 'photos');
                    if (!policy.show_location_steps) steps = steps.filter((k) => k !== 'where' && k !== 'pin');
                    return steps.filter((k) => Boolean(document.querySelector(`[data-wizard-step="${k}"]`)));
                };

                const stepName = (key) => i18n.steps[key] || key;

                // ── Validation (client side) ──
                const validate = (key) => {
                    switch (key) {
                        case 'group':
                            return { valid: Boolean(getSelectedGroupKey()), hint: i18n.hints.group, focus: () => document.querySelector('.tile--group input')?.focus() };
                        case 'category':
                            return { valid: Boolean(categorySelect?.value), hint: i18n.hints.category, focus: () => { const gk = getSelectedGroupKey(); const el = (gk && document.querySelector(`[data-group-panel="${cssEscape(gk)}"] input`)) || document.querySelector('.tile--leaf input'); el?.focus(); } };
                        case 'describe': {
                            const title = document.getElementById('title')?.value?.trim() || '';
                            const desc = document.getElementById('description')?.value?.trim() || '';
                            const valid = title.length > 0 && desc.length >= 20;
                            return { valid, hint: i18n.hints.describe, focus: () => (title.length === 0 ? document.getElementById('title') : document.getElementById('description'))?.focus() };
                        }
                        case 'photos': {
                            const policy = currentIntakePolicy();
                            if (!policy.photos_required) return { valid: true, hint: '' };
                            return { valid: selectedPhotos.length >= 1, hint: i18n.hints.photos, focus: () => imagesInput?.focus() };
                        }
                        case 'where': {
                            const policy = currentIntakePolicy();
                            const b = barangayInput?.value?.trim() || '';
                            const a = addressInput?.value?.trim() || '';
                            if (policy.address_text_required) {
                                return { valid: Boolean(b) && Boolean(a), hint: i18n.hints.where_landmark || i18n.hints.where, focus: () => (!b ? barangayInput : addressInput)?.focus() };
                            }
                            return { valid: Boolean(b), hint: i18n.hints.where, focus: () => barangayInput?.focus() };
                        }
                        case 'pin':
                            return { valid: true, hint: '' };
                        case 'visibility':
                            return { valid: true, hint: '' };
                        case 'review': {
                            const ok = Boolean(document.getElementById('terms')?.checked);
                            return { valid: ok, hint: i18n.hints.review, focus: () => document.getElementById('terms')?.focus() };
                        }
                        default:
                            return { valid: true, hint: '' };
                    }
                };

                const firstIncompleteStep = () => {
                    for (const key of visibleSteps()) {
                        if (!validate(key).valid) return key;
                    }
                    const vis = visibleSteps();
                    return vis[vis.length - 1] || 'review';
                };

                const getServerErrorStep = () => {
                    const errors = window.__FILING_ERRORS__ || null;
                    if (errors) {
                        for (const key of WIZARD_STEPS) {
                            if (errors[key]) return key;
                        }
                    }
                    return null;
                };

                // Server error map (Blade injects booleans via __FILING_ERRORS__)
                // category -> category (or group if no value)
                // title/description -> describe, images/camera_photo/image -> photos,
                // barangay/address_text -> where, latitude/longitude/location_choice -> pin,
                // visibility -> visibility, terms -> review. First in wizard order wins.

                const saveDraft = () => {
                    try {
                        const data = {
                            category: categorySelect?.value || '',
                            title: document.getElementById('title')?.value || '',
                            description: document.getElementById('description')?.value || '',
                            barangay: barangayInput?.value || '',
                            address_text: addressInput?.value || '',
                            latitude: latitudeInput?.value || '',
                            longitude: longitudeInput?.value || '',
                            visibility: document.querySelector('input[name="visibility"]:checked')?.value || 'private',
                            location_choice: document.querySelector('input[name="location_choice"]:checked')?.value || '',
                            terms: document.getElementById('terms')?.checked || false,
                        };
                        sessionStorage.setItem(draftKey, JSON.stringify(data));
                    } catch (e) {}
                };

                const loadDraft = () => {
                    try {
                        const raw = sessionStorage.getItem(draftKey);
                        if (!raw) return null;
                        return JSON.parse(raw);
                    } catch (e) {
                        return null;
                    }
                };

                const applyDraft = (draft) => {
                    if (!draft) return;
                    if (categorySelect && draft.category) {
                        categorySelect.value = draft.category;
                        syncTilesFromCategory();
                        categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    const titleEl = document.getElementById('title');
                    if (titleEl && draft.title) titleEl.value = draft.title;
                    const descEl = document.getElementById('description');
                    if (descEl && draft.description) descEl.value = draft.description;
                    if (barangayInput && draft.barangay) barangayInput.value = draft.barangay;
                    if (addressInput && draft.address_text) addressInput.value = draft.address_text;
                    if (latitudeInput && longitudeInput
                        && isLocationAllowed(Number.parseFloat(draft.latitude), Number.parseFloat(draft.longitude))) {
                        latitudeInput.value = draft.latitude;
                        longitudeInput.value = draft.longitude;
                    }
                    if (draft.visibility) {
                        const vis = document.querySelector(`input[name="visibility"][value="${cssEscape(draft.visibility)}"]`);
                        if (vis) vis.checked = true;
                    }
                    if (draft.location_choice) {
                        const loc = document.querySelector(`input[name="location_choice"][value="${cssEscape(draft.location_choice)}"]`);
                        if (loc) loc.checked = true;
                    }
                    const termsEl = document.getElementById('terms');
                    if (termsEl && draft.terms) termsEl.checked = true;
                    updateSummary();
                    updateWizardUI(false);
                    updatePinLabel();
                };

                const updateSummary = () => {
                    if (!reviewCategory) return;
                    const categoryLabel = categorySelect?.selectedOptions?.[0]?.textContent?.trim();
                    const department = categoryToDepartment[categorySelect?.value || ''];
                    reviewCategory.textContent = categoryLabel && categoryLabel !== '- Select a category -' ? categoryLabel : 'Not selected';
                    reviewDepartment.textContent = department || 'Not routed yet';

                    const description = addressInput?.value?.trim();
                    const barangay = barangayInput?.value?.trim();

                    reviewLocation.textContent = [barangay, description]
                        .filter(Boolean)
                        .join(' · ') || 'No location added';

                    const titleEl = document.getElementById('review-title');
                    if (titleEl) {
                        const v = document.getElementById('title')?.value?.trim() || '';
                        titleEl.textContent = v || 'Not provided';
                    }
                    const descEl = document.getElementById('review-description');
                    if (descEl) {
                        const v = document.getElementById('description')?.value?.trim() || '';
                        descEl.textContent = v || 'Not provided';
                    }
                    const photosCountEl = document.getElementById('review-photos-count');
                    const thumbsEl = document.getElementById('review-photos-thumbs');
                    if (photosCountEl || thumbsEl) {
                        const imgs = uploadPreviews ? Array.from(uploadPreviews.querySelectorAll('img')) : [];
                        const count = imgs.length;
                        if (photosCountEl) photosCountEl.textContent = count + ' / 5';
                        if (thumbsEl) {
                            thumbsEl.textContent = '';
                            const previewImgs = uploadPreviews ? uploadPreviews.querySelectorAll('img') : [];
                            Array.from(previewImgs).slice(0, 5).forEach((srcImg) => {
                                const thumb = document.createElement('img');
                                thumb.className = 'fc-review-thumb';
                                thumb.src = srcImg.src;
                                thumb.alt = '';
                                thumbsEl.appendChild(thumb);
                            });
                        }
                    }
                    const visEl = document.getElementById('review-visibility');
                    if (visEl) {
                        const visVal = document.querySelector('input[name="visibility"]:checked')?.value || 'private';
                        visEl.textContent = visVal === 'public' ? (i18n.publishPage || 'Publish on the transparency page') : (i18n.justMe || 'Just me');
                    }
                };

                // ── Tiles ⇄ select sync ──
                const syncTilesFromCategory = () => {
                    const value = categorySelect?.value || '';
                    for (const panel of panels()) {
                        const key = panel.getAttribute('data-group-panel');
                        const hasValue = Boolean(value && panel.querySelector(`input[name="category_choice"][value="${cssEscape(value)}"]`));
                        if (hasValue) {
                            const groupRadio = document.querySelector(`input[name="category_group"][value="${cssEscape(key)}"]`);
                            if (groupRadio) groupRadio.checked = true;
                            const leafRadio = panel.querySelector(`input[name="category_choice"][value="${cssEscape(value)}"]`);
                            if (leafRadio) leafRadio.checked = true;
                        }
                    }
                    syncTileStates();
                };

                const syncTileStates = () => {
                    const groupKey = getSelectedGroupKey();
                    for (const tile of groupTiles()) {
                        const input = tile.querySelector('input');
                        const isSel = Boolean(input && input.checked);
                        tile.classList.toggle('is-selected', isSel);
                        tile.querySelector('.tile__check')?.classList.toggle('tile__check--hidden', !isSel);
                    }
                    for (const tile of leafTiles()) {
                        const input = tile.querySelector('input');
                        tile.classList.toggle('is-selected', Boolean(input && input.checked));
                    }
                    for (const panel of panels()) {
                        const key = panel.getAttribute('data-group-panel');
                        panel.hidden = !(groupKey && key === groupKey);
                    }
                };

                const updateChip = () => {
                    if (!departmentHint) return;
                    const value = categorySelect?.value || '';
                    const department = categoryToDepartment[value] || '';
                    if (!value || !department) {
                        departmentHint.classList.remove('is-visible');
                        departmentHint.hidden = true;
                        departmentHint.removeAttribute('data-visible');
                        return;
                    }
                    const groupKey = getSelectedGroupKey();
                    const groupRadio = groupKey ? document.querySelector(`input[name="category_group"][value="${cssEscape(groupKey)}"]`) : null;
                    const groupLabel = groupRadio ? groupRadio.closest('.tile')?.querySelector('.tile__label')?.textContent?.trim() || '' : '';
                    const leafRadio = document.querySelector(`input[name="category_choice"][value="${cssEscape(value)}"]`);
                    const leafLabel = leafRadio ? leafRadio.closest('.tile')?.querySelector('.tile__label')?.textContent?.trim() || (categorySelect?.selectedOptions?.[0]?.textContent?.trim() || '') : '';
                    const iconEl = leafRadio ? leafRadio.closest('.tile')?.querySelector('.tile__icon') : (groupRadio ? groupRadio.closest('.tile')?.querySelector('.tile__icon') : null);
                    if (chipGroup) chipGroup.textContent = groupLabel;
                    if (chipLeaf) chipLeaf.textContent = leafLabel;
                    if (chipIcon && iconEl) {
                        chipIcon.className = iconEl.className.replace('tile__icon', 'fc-chip__icon');
                        chipIcon.innerHTML = iconEl.innerHTML;
                    }
                    if (departmentName) departmentName.textContent = department;
                    if (chipPrefix) chipPrefix.textContent = String(i18n.chipGoesTo).split('{office}')[0] || '';
                    // Chip shows on every screen after category (not review) in wizard;
                    // on desktop whenever a category is chosen.
                    const show = isWizardMode()
                        ? !['group', 'category', 'review'].includes(currentKey)
                        : true;
                    departmentHint.hidden = !show;
                    departmentHint.classList.toggle('is-visible', show);
                    if (show) departmentHint.setAttribute('data-visible', 'true');
                    else departmentHint.removeAttribute('data-visible');
                };

                const setCategory = (value) => {
                    if (!categorySelect) return;
                    categorySelect.value = value;
                    syncTilesFromCategory();
                    categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const onGroupChosen = (groupKey) => {
                    syncTileStates();
                    const leaves = getSelectedGroupLeaves(groupKey);
                    if (leaves.length === 1) {
                        setCategory(leaves[0].value);
                        if (isWizardMode()) {
                            window.setTimeout(() => {
                                if (currentKey === 'group' || currentKey === 'category') goToStep('describe');
                            }, 200);
                        }
                    } else if (leaves.length === 0) {
                        setCategory('');
                    } else {
                        // Multi-child: keep previous leaf only if it belongs to this group.
                        const current = categorySelect?.value || '';
                        const stillHere = leaves.some((r) => r.value === current);
                        if (!stillHere) {
                            setCategory('');
                        }
                        if (isWizardMode() && currentKey === 'group') {
                            window.setTimeout(() => {
                                if (currentKey === 'group') goToStep('category');
                            }, 200);
                        }
                    }
                    updateWizardUI(false);
                };

                const onLeafChosen = (value) => {
                    setCategory(value);
                    if (isWizardMode()) {
                        window.setTimeout(() => {
                            if (currentKey === 'category') goToStep('describe');
                        }, 200);
                    }
                    updateWizardUI(false);
                };

                groupTiles().forEach((tile) => {
                    const input = tile.querySelector('input');
                    let changeFired = false;
                    input?.addEventListener('change', () => {
                        changeFired = true;
                        onGroupChosen(input.value);
                    });
                    // Re-clicking an already-selected radio doesn't fire `change`; still proceed.
                    tile.addEventListener('click', () => {
                        const wasAlreadyChecked = Boolean(input?.checked);
                        changeFired = false;
                        window.setTimeout(() => {
                            if (wasAlreadyChecked && !changeFired && input?.checked) {
                                onGroupChosen(input.value);
                            }
                        }, 0);
                    });
                });

                leafTiles().forEach((tile) => {
                    const input = tile.querySelector('input');
                    let changeFired = false;
                    input?.addEventListener('change', () => {
                        changeFired = true;
                        onLeafChosen(input.value);
                    });
                    tile.addEventListener('click', () => {
                        const wasAlreadyChecked = Boolean(input?.checked);
                        changeFired = false;
                        window.setTimeout(() => {
                            if (wasAlreadyChecked && !changeFired && input?.checked) {
                                onLeafChosen(input.value);
                            }
                        }, 0);
                    });
                });

                if (chipChangeBtn) {
                    chipChangeBtn.addEventListener('click', () => {
                        hideHint();
                        if (isWizardMode()) {
                            goToStep('group');
                        } else {
                            document.getElementById('filing-category')?.scrollIntoView?.({ block: 'start' });
                            document.querySelector('.tile--group input')?.focus();
                        }
                    });
                }

                if (categorySelect) {
                    // With JS the select is visually hidden; it must not keep
                    // native required (hidden required blocks submit with
                    // "not focusable"). Client validation + server replace it.
                    categorySelect.removeAttribute('required');
                    categorySelect.setAttribute('tabindex', '-1');
                    categorySelect.setAttribute('aria-hidden', 'true');
                    categorySelect.addEventListener('change', () => {
                        syncTilesFromCategory();
                        updateSummary();
                        updateChip();
                        updateWizardUI(false);
                    });
                    updateSummary();
                }

                addressInput?.addEventListener('input', updateSummary);
                barangayInput?.addEventListener('input', updateSummary);

                // ── Photos (ported from the original wizard) ──
                const maxPhotos = 5;
                const maxPhotoBytes = 5 * 1024 * 1024;
                let selectedPhotos = [];
                let previewUrls = [];

                const showUploadError = (message) => {
                    if (!clientUploadError) return;
                    clientUploadError.textContent = message;
                    clientUploadError.hidden = !message;
                };

                const isAllowedPhoto = (file) => {
                    const allowedType = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
                    const allowedName = /\.(jpe?g|png|webp)$/i.test(file.name);
                    return file.size > 0 && file.size <= maxPhotoBytes && (allowedType || allowedName);
                };

                const syncGalleryInput = () => {
                    if (!imagesInput || typeof DataTransfer === 'undefined') return false;
                    try {
                        const transfer = new DataTransfer();
                        selectedPhotos.forEach(({ file }) => transfer.items.add(file));
                        imagesInput.files = transfer.files;
                        return true;
                    } catch (error) {
                        return false;
                    }
                };

                const renderPhotoPreviews = () => {
                    if (!uploadPreviews || !photoCount) return;
                    previewUrls.forEach((url) => URL.revokeObjectURL(url));
                    previewUrls = [];
                    uploadPreviews.replaceChildren();
                    photoCount.textContent = `${selectedPhotos.length} / ${maxPhotos}`;
                    selectedPhotos.forEach(({ id, file }) => {
                        const preview = document.createElement('div');
                        preview.className = 'upload-preview';
                        const image = document.createElement('img');
                        const objectUrl = URL.createObjectURL(file);
                        previewUrls.push(objectUrl);
                        image.src = objectUrl;
                        image.alt = `Selected evidence photo ${file.name}`;
                        preview.appendChild(image);
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'upload-preview__remove';
                        remove.setAttribute('aria-label', `Remove ${file.name}`);
                        remove.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12"></path><path d="m18 6-12 12"></path></svg>';
                        remove.addEventListener('click', () => {
                            selectedPhotos = selectedPhotos.filter((photo) => photo.id !== id);
                            syncGalleryInput();
                            showUploadError('');
                            renderPhotoPreviews();
                        });
                        preview.appendChild(remove);
                        uploadPreviews.appendChild(preview);
                    });
                    updateWizardUI(false);
                };

                const addPhotos = (files) => {
                    const incoming = Array.from(files || []);
                    const invalid = incoming.find((file) => !isAllowedPhoto(file));
                    if (invalid) {
                        showUploadError('Use JPG, PNG, or WEBP photos no larger than 5 MB each.');
                        return;
                    }
                    const existingKeys = new Set(selectedPhotos.map(({ file }) => `${file.name}-${file.size}-${file.lastModified}`));
                    const unique = incoming.filter((file) => {
                        const key = `${file.name}-${file.size}-${file.lastModified}`;
                        if (existingKeys.has(key)) return false;
                        existingKeys.add(key);
                        return true;
                    });
                    const availableSlots = maxPhotos - selectedPhotos.length;
                    if (unique.length > availableSlots) {
                        showUploadError(`You can attach a maximum of ${maxPhotos} photos.`);
                    } else {
                        showUploadError('');
                    }
                    unique.slice(0, Math.max(availableSlots, 0)).forEach((file, index) => {
                        selectedPhotos.push({ id: `${Date.now()}-${index}-${file.name}-${file.size}`, file });
                    });
                    syncGalleryInput();
                    renderPhotoPreviews();
                };

                document.querySelectorAll('[data-upload-trigger]').forEach((trigger) => {
                    trigger.addEventListener('click', () => {
                        const input = document.getElementById(trigger.dataset.uploadTrigger);
                        input?.click();
                    });
                });

                imagesInput?.addEventListener('change', () => addPhotos(imagesInput.files));
                cameraInput?.addEventListener('change', () => {
                    if (!cameraInput.files?.length) return;
                    addPhotos(cameraInput.files);
                    if (syncGalleryInput()) cameraInput.value = '';
                });

                if (uploadZone) {
                    uploadZone.addEventListener('click', (event) => {
                        if (event.target === imagesInput || event.target === cameraInput || event.target.closest('button')) return;
                        imagesInput?.click();
                    });
                    uploadZone.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        uploadZone.classList.add('is-dragging');
                    });
                    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('is-dragging'));
                    uploadZone.addEventListener('drop', (event) => {
                        event.preventDefault();
                        uploadZone.classList.remove('is-dragging');
                        addPhotos(event.dataTransfer?.files);
                    });
                }

                // ── Map (ported; keep window.* working) ──
                let map = null;
                let marker = null;
                let lastValidPin = null;

                const updateCoordinates = (latitude, longitude) => {
                    if (latitudeInput) latitudeInput.value = latitude.toFixed(8);
                    if (longitudeInput) longitudeInput.value = longitude.toFixed(8);
                    updateSummary();
                    updatePinLabel();
                };

                const removePinButton = document.getElementById('remove-pin');
                const updatePinLabel = () => {
                    const hasPin = Boolean(latitudeInput?.value && longitudeInput?.value);
                    if (removePinButton) removePinButton.hidden = !hasPin;
                    if (!btnNext || currentKey !== 'pin') return;
                    btnNext.textContent = hasPin ? i18n.buttons.continue : i18n.buttons.skip;
                };

                removePinButton?.addEventListener('click', () => {
                    clearPin();
                    const locateStatus = document.getElementById('locate-status');
                    if (locateStatus) {
                        locateStatus.hidden = false;
                        locateStatus.classList.remove('is-error');
                        locateStatus.setAttribute('role', 'status');
                        locateStatus.setAttribute('aria-live', 'polite');
                        locateStatus.textContent = 'Pin removed.';
                    }
                    saveDraft && saveDraft();
                });

                const clearPin = () => {
                    if (marker && map) {
                        try { map.removeLayer(marker); } catch (e) {}
                    }
                    marker = null;
                    lastValidPin = null;
                    if (latitudeInput) latitudeInput.value = '';
                    if (longitudeInput) longitudeInput.value = '';
                    updateSummary();
                    updatePinLabel();
                };

                const showMapUnavailable = () => {
                    if (!mapElement) return;
                    mapElement.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:14px">Map unavailable. You can still submit using the address or landmark field.</div>';
                };

                const initializeMap = () => {
                    if (!mapElement) return;
                    if (map) {
                        map.remove();
                        map = null;
                        marker = null;
                    }
                    const keepMapSized = (target) => {
                        if (!target) return;
                        const refresh = () => target.invalidateSize({ animate: false });
                        window.requestAnimationFrame(refresh);
                        window.setTimeout(refresh, 300);
                        window.addEventListener('resize', refresh);
                        window.addEventListener('orientationchange', () => window.setTimeout(refresh, 150));
                    };
                    const boundaryLatLngBounds = daetBoundaryBounds
                        ? window.L.latLngBounds(daetBoundaryBounds)
                        : null;
                    const mapOptions = { maxZoom: 18 };
                    if (boundaryLatLngBounds) {
                        mapOptions.maxBounds = boundaryLatLngBounds.pad(0.12);
                        mapOptions.maxBoundsViscosity = 1;
                    }
                    map = window.L.map(mapElement, mapOptions).setView(
                        boundaryLatLngBounds ? boundaryLatLngBounds.getCenter() : [14.1153, 122.9553],
                        14,
                    );
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 18,
                    }).addTo(map);
                    window.invalidateComplaintMap = () => {
                        if (map) map.invalidateSize({ animate: false });
                    };

                    const locateButton = document.getElementById('locate-me');
                    const locateStatus = document.getElementById('locate-status');
                    const mapBoundaryNotice = (() => {
                        const existing = mapElement.querySelector('#map-boundary-notice');
                        if (existing) return existing;
                        const notice = document.createElement('div');
                        notice.id = 'map-boundary-notice';
                        notice.className = 'fc-map-notice';
                        notice.setAttribute('role', 'alert');
                        notice.setAttribute('aria-live', 'assertive');
                        notice.hidden = true;
                        mapElement.appendChild(notice);
                        return notice;
                    })();
                    const clearMapBoundaryNotice = () => {
                        if (!mapBoundaryNotice) return;
                        mapBoundaryNotice.hidden = true;
                        mapBoundaryNotice.textContent = '';
                    };
                    const clearLocateStatus = () => {
                        clearMapBoundaryNotice();
                        if (!locateStatus) return;
                        locateStatus.hidden = true;
                        locateStatus.textContent = '';
                        locateStatus.classList.remove('is-error');
                        locateStatus.setAttribute('role', 'status');
                        locateStatus.setAttribute('aria-live', 'polite');
                    };
                    const sayLocating = (message, isError = false) => {
                        if (isError) {
                            clearMapBoundaryNotice();
                            if (locateStatus) {
                                locateStatus.hidden = true;
                                locateStatus.textContent = '';
                                locateStatus.classList.remove('is-error');
                                locateStatus.setAttribute('role', 'status');
                                locateStatus.setAttribute('aria-live', 'polite');
                            }
                            if (mapBoundaryNotice) {
                                mapBoundaryNotice.textContent = message;
                                mapBoundaryNotice.hidden = false;
                            }
                            return;
                        }
                        clearMapBoundaryNotice();
                        if (!locateStatus) return;
                        locateStatus.textContent = message;
                        locateStatus.hidden = false;
                        locateStatus.classList.remove('is-error');
                        locateStatus.setAttribute('role', 'status');
                        locateStatus.setAttribute('aria-live', 'polite');
                    };

                    const pinIcon = window.L.divIcon({
                        className: 'gold-pin-marker',
                        html: '<div class="pin-head"></div><div class="pin-pulse"></div>',
                        iconSize: [30, 42],
                        iconAnchor: [15, 42],
                    });
                    const placeMarker = (latitude, longitude, recenter = false) => {
                        if (!isLocationAllowed(latitude, longitude)) {
                            sayLocating(
                                i18n.map?.outside || 'That point is outside Daet. Place the pin inside the city.',
                                true,
                            );
                            return false;
                        }
                        if (marker) {
                            marker.setLatLng([latitude, longitude]);
                        } else {
                            marker = window.L.marker([latitude, longitude], { icon: pinIcon, draggable: true }).addTo(map);
                            marker.on('dragend', () => {
                                const position = marker.getLatLng();
                                if (!isLocationAllowed(position.lat, position.lng)) {
                                    sayLocating(
                                        i18n.map?.outside || 'That point is outside Daet. Place the pin inside the city.',
                                        true,
                                    );
                                    if (lastValidPin) {
                                        marker.setLatLng([lastValidPin.latitude, lastValidPin.longitude]);
                                    }
                                    return;
                                }
                                lastValidPin = { latitude: position.lat, longitude: position.lng };
                                clearLocateStatus();
                                updateCoordinates(position.lat, position.lng);
                            });
                        }
                        lastValidPin = { latitude, longitude };
                        if (recenter) map.setView([latitude, longitude], 16);
                        clearLocateStatus();
                        updateCoordinates(latitude, longitude);
                        return true;
                    };

                    const savedLatitude = Number.parseFloat(latitudeInput?.value);
                    const savedLongitude = Number.parseFloat(longitudeInput?.value);
                    if (Number.isFinite(savedLatitude) && Number.isFinite(savedLongitude)) {
                        if (isLocationAllowed(savedLatitude, savedLongitude)) {
                            placeMarker(savedLatitude, savedLongitude, true);
                        } else {
                            // An old submission or draft can carry a pin from
                            // before the boundary existed. Do not show it as if
                            // it were valid; ask for a new one instead.
                            if (latitudeInput) latitudeInput.value = '';
                            if (longitudeInput) longitudeInput.value = '';
                            updateSummary();
                            updatePinLabel();
                            sayLocating(
                                i18n.map?.previousOutside || 'Your previous pin was outside Daet and was removed. Place a new pin, or skip this step.',
                                true,
                            );
                            if (boundaryLatLngBounds) {
                                map.setView(boundaryLatLngBounds.getCenter(), 14);
                            }
                        }
                    }
                    map.on('click', (event) => placeMarker(event.latlng.lat, event.latlng.lng));
                    keepMapSized(map);

                    const locateFailures = {
                        1: 'Location permission was declined. You can still place the pin on the map.',
                        2: 'Your location is unavailable right now. Please place the pin on the map.',
                        3: 'Finding your location took too long. Please place the pin on the map.',
                    };
                    if (locateButton) {
                        locateButton.addEventListener('click', () => {
                            if (!navigator.geolocation) {
                                sayLocating('This browser cannot share your location. Please place the pin on the map.');
                                return;
                            }
                            locateButton.disabled = true;
                            sayLocating('Finding your location...');
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    locateButton.disabled = false;
                                    const { latitude, longitude, accuracy } = position.coords;
                                    if (placeMarker(latitude, longitude, true)) {
                                        const within = Number.isFinite(accuracy) ? ` It is accurate to about ${Math.round(accuracy)} m.` : '';
                                        sayLocating(`Pin moved to where you are.${within} Drag the pin if the problem is a little way from you.`);
                                    }
                                },
                                (error) => {
                                    locateButton.disabled = false;
                                    if (error.code === 1 && !window.isSecureContext) {
                                        sayLocating('Your browser only shares a location over a secure (https) connection. Please place the pin on the map.');
                                        return;
                                    }
                                    sayLocating(locateFailures[error.code] ?? 'Your location could not be read. Please place the pin on the map.');
                                },
                                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
                            );
                        });
                    }
                };

                if (typeof window.whenLeafletReady === 'function') {
                    window.whenLeafletReady(initializeMap, { onUnavailable: showMapUnavailable });
                } else {
                    showMapUnavailable();
                }

                // ── Wizard UI ──
                const hideHint = () => {
                    if (hintPill) hintPill.hidden = true;
                };

                const showHint = (key) => {
                    const message = i18n.hints[key] || '';
                    if (!message || !hintPill || !hintText) return;
                    hintText.textContent = message;
                    hintPill.hidden = false;
                };

                const updateWizardUI = (announce = true) => {
                    const wizardOn = isWizardMode();
                    page?.classList.toggle('wizard-on', wizardOn);
                    // With JS the hidden category select has no native required
                    // (hidden required blocks submit with "not focusable") and
                    // wizard steps hide required fields, so native checks are
                    // replaced by client + server validation. Without JS the
                    // form keeps native required (see no-JS fallback).
                    form.noValidate = true;

                    const policy = currentIntakePolicy();
                    const policyHidden = (key) => (key === 'photos' && !policy.show_photos_step) || ((key === 'where' || key === 'pin') && !policy.show_location_steps);

                    // Landmark/address_text: required for proof_optional (1a) — expand so it is fillable.
                    const landmarkToggle = document.getElementById('landmark-toggle');
                    const landmarkField = document.getElementById('landmark-field');
                    const addressReqMark = document.getElementById('address-text-required-mark');
                    if (landmarkField && policy.show_location_steps) {
                        if (policy.address_text_required) {
                            landmarkField.hidden = false;
                            landmarkField.classList.add('is-open');
                            landmarkToggle?.setAttribute('aria-expanded', 'true');
                            addressInput?.setAttribute('required', 'required');
                            if (addressReqMark) addressReqMark.textContent = ' *';
                        } else {
                            addressInput?.removeAttribute('required');
                            if (addressReqMark && i18n.optionalMark) addressReqMark.textContent = i18n.optionalMark;
                        }
                    }
                    // Barangay native required only when location is collected; JS validation is authoritative.
                    if (policy.barangay_required) barangayInput?.setAttribute('required', 'required');
                    else barangayInput?.removeAttribute('required');

                    // Photos step text: required vs optional-but-encouraged (proof_optional). No redesign.
                    const photosHelp = document.getElementById('photos-step-help');
                    const photosReqMark = document.getElementById('photos-required-mark');
                    if (photosHelp && photosReqMark) {
                        if (policy.photos_required) {
                            photosReqMark.style.display = '';
                            if (i18n.photosRequiredHelp) photosHelp.textContent = i18n.photosRequiredHelp;
                        } else if (policy.show_photos_step) {
                            photosReqMark.style.display = 'none';
                            if (i18n.photosOptionalHelp) photosHelp.textContent = i18n.photosOptionalHelp;
                        }
                    }

                    if (!wizardOn) {
                        document.querySelectorAll('[data-wizard-step]').forEach((el) => {
                            const key = el.getAttribute('data-wizard-step');
                            const hidden = policyHidden(key);
                            el.classList.remove('wizard-step-active');
                            el.toggleAttribute('hidden', hidden);
                        });
                        document.querySelectorAll('.fc-card').forEach((card) => {
                            const steps = Array.from(card.querySelectorAll('[data-wizard-step]'));
                            const anyVisible = steps.length === 0 || steps.some((el) => !el.hasAttribute('hidden'));
                            card.toggleAttribute('hidden', !anyVisible);
                        });
                        page?.classList.remove('wizard-at-start');
                        document.querySelectorAll('[data-upload-trigger="camera-input"]').forEach((btn) => {
                            if (btn.dataset.origText) btn.textContent = btn.dataset.origText;
                        });
                        document.querySelectorAll('[data-upload-trigger="images-input"]').forEach((btn) => {
                            if (btn.dataset.origText) btn.textContent = btn.dataset.origText;
                        });
                        if (actionBar) actionBar.style.display = 'none';
                        hideHint();
                        syncTileStates();
                        updateChip();
                        updatePinLabel();
                        return;
                    }

                    if (actionBar) actionBar.style.display = '';
                    updateChip();

                    const vis = visibleSteps();
                    if (!vis.includes(currentKey)) currentKey = vis[0] || 'group';

                    document.querySelectorAll('[data-wizard-step]').forEach((el) => {
                        const key = el.getAttribute('data-wizard-step');
                        if (key === currentKey) {
                            el.classList.add('wizard-step-active');
                            el.removeAttribute('hidden');
                        } else {
                            el.classList.remove('wizard-step-active');
                            el.setAttribute('hidden', 'true');
                        }
                    });

                    document.querySelectorAll('.fc-card').forEach((card) => {
                        const hasActive = Boolean(card.querySelector('[data-wizard-step].wizard-step-active'));
                        card.hidden = !hasActive;
                    });
                    page?.classList.toggle('wizard-at-start', currentKey === 'group');
                    document.querySelectorAll('[data-upload-trigger="camera-input"]').forEach((btn) => {
                        if (!btn.dataset.origText) btn.dataset.origText = btn.textContent;
                        if (i18n.takePhoto) btn.textContent = i18n.takePhoto;
                    });
                    document.querySelectorAll('[data-upload-trigger="images-input"]').forEach((btn) => {
                        if (!btn.dataset.origText) btn.dataset.origText = btn.textContent;
                        if (i18n.chooseGallery) btn.textContent = i18n.chooseGallery;
                    });
                    if (currentKey === 'review') updateSummary();

                    const idx = vis.indexOf(currentKey);
                    const n = idx + 1;
                    const total = vis.length;
                    if (stepCounter) stepCounter.textContent = t(i18n.progressStep, { n, total });
                    if (stepLabel) stepLabel.textContent = stepName(currentKey);
                    if (segmentsEl) {
                        segmentsEl.setAttribute('aria-valuenow', String(n));
                        segmentsEl.setAttribute('aria-valuemax', String(total));
                        segmentsEl.setAttribute('aria-label', t(i18n.liveStep, { n, total, name: stepName(currentKey) }));
                        // Rebuild to match visible count (7 vs 8).
                        while (segmentsEl.children.length < total) {
                            const s = document.createElement('span');
                            s.className = 'fc-progress__seg';
                            segmentsEl.appendChild(s);
                        }
                        const segs = Array.from(segmentsEl.children);
                        segs.forEach((seg, i) => {
                            if (i >= total) {
                                seg.style.display = 'none';
                                return;
                            }
                            seg.style.display = '';
                            seg.classList.remove('fc-progress__seg--done', 'fc-progress__seg--current');
                            if (i < idx) seg.classList.add('fc-progress__seg--done');
                            else if (i === idx) seg.classList.add('fc-progress__seg--current');
                        });
                    }

                    // Action bar per step: Next stays type=button; the dedicated
                    // bar submit (type=submit) replaces it on review. Mutating
                    // Next's own type inside its click handler would submit the
                    // same click (visibility → review must not submit).
                    if (actionBar) {
                        if (currentKey === 'group') {
                            actionBar.style.display = 'none';
                        } else {
                            actionBar.style.display = '';
                            if (btnBack) {
                                btnBack.style.display = 'inline-flex';
                                btnBack.classList.toggle('wizard-btn-back--full', currentKey === 'category');
                            }
                            if (btnNext) {
                                btnNext.style.display = (currentKey === 'category' || currentKey === 'review') ? 'none' : 'inline-flex';
                                if (currentKey === 'pin') {
                                    const hasPin = Boolean(latitudeInput?.value && longitudeInput?.value);
                                    btnNext.textContent = hasPin ? i18n.buttons.continue : i18n.buttons.skip;
                                } else {
                                    btnNext.textContent = i18n.buttons.next;
                                }
                                const res = validate(currentKey);
                                btnNext.removeAttribute('disabled');
                                btnNext.setAttribute('aria-disabled', res.valid ? 'false' : 'true');
                            }
                            if (btnSubmitBar) {
                                const isReview = currentKey === 'review';
                                btnSubmitBar.hidden = !isReview;
                                btnSubmitBar.style.display = isReview ? 'inline-flex' : 'none';
                                btnSubmitBar.textContent = i18n.buttons.submit;
                                const resR = validate('review');
                                btnSubmitBar.removeAttribute('disabled');
                                btnSubmitBar.setAttribute('aria-disabled', resR.valid ? 'false' : 'true');
                            }
                            if (btnBack) btnBack.textContent = i18n.buttons.back;
                        }
                    }

                    if (currentKey === 'pin' && window.invalidateComplaintMap) {
                        window.invalidateComplaintMap();
                    }
                    if (announce) {
                        const message = t(i18n.liveStep, { n, total, name: stepName(currentKey) });
                        if (liveRegion) liveRegion.textContent = message;
                        const heading = document.querySelector(`[data-wizard-step="${cssEscape(currentKey)}"] .fc-step-title`);
                        try {
                            heading?.focus({ preventScroll: true });
                        } catch (err) {}
                        try {
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        } catch (err) {}
                    }
                };

                const goToStep = (key, push = true) => {
                    if (!isWizardMode()) return;
                    if (!WIZARD_STEPS.includes(key)) return;
                    const vis = visibleSteps();
                    if (vis.length === 0) return;

                    // If the requested step is hidden by intake policy, pick the
                    // nearest earlier visible step instead of jumping to the start.
                    let targetKey = key;
                    if (!vis.includes(targetKey)) {
                        const requestedIdx = WIZARD_STEPS.indexOf(targetKey);
                        for (let i = requestedIdx - 1; i >= 0; i--) {
                            if (vis.includes(WIZARD_STEPS[i])) {
                                targetKey = WIZARD_STEPS[i];
                                break;
                            }
                        }
                        if (!vis.includes(targetKey)) targetKey = vis[0];
                    }

                    // Only guard forward jumps. Review/Edit actions move backward
                    // and must not be blocked by re-validating earlier steps.
                    const currentIdx = vis.indexOf(currentKey);
                    const targetIdx = vis.indexOf(targetKey);
                    if (targetIdx > currentIdx && targetIdx > 0) {
                        for (let i = 0; i < targetIdx; i++) {
                            if (!validate(vis[i]).valid) {
                                targetKey = vis[i];
                                break;
                            }
                        }
                    }

                    currentKey = targetKey;
                    hideHint();
                    updateWizardUI(true);
                    if (push) {
                        try {
                            history.pushState(null, '', `#step-${currentKey}`);
                        } catch (e) {
                            window.location.hash = `#step-${currentKey}`;
                        }
                    }
                };

                const nextStep = () => {
                    const vis = visibleSteps();
                    const idx = vis.indexOf(currentKey);
                    if (idx >= 0 && idx < vis.length - 1) goToStep(vis[idx + 1]);
                };

                const prevStep = () => {
                    const vis = visibleSteps();
                    const idx = vis.indexOf(currentKey);
                    if (idx > 0) goToStep(vis[idx - 1]);
                };

                btnBack?.addEventListener('click', () => {
                    hideHint();
                    prevStep();
                });

                btnNext?.addEventListener('click', (e) => {
                    if (submitting) return;
                    const res = validate(currentKey);
                    if (!res.valid) {
                        e.preventDefault();
                        showHint(currentKey);
                        res.focus?.();
                        return;
                    }
                    hideHint();
                    nextStep();
                });

                btnSubmitBar?.addEventListener('click', (e) => {
                    if (submitting) return;
                    const res = validate('review');
                    if (!res.valid) {
                        e.preventDefault();
                        showHint('review');
                        res.focus?.();
                    }
                });

                window.addEventListener('popstate', () => {
                    if (!isWizardMode()) return;
                    const match = window.location.hash.match(/^#step-([a-z]+)$/);
                    if (match && WIZARD_STEPS.includes(match[1])) {
                        const requested = match[1];
                        const vis = visibleSteps();
                        if (vis.includes(requested)) {
                            // Apply hash guard without pushing again.
                            const targetIdx = vis.indexOf(requested);
                            let key = requested;
                            for (let i = 0; i < targetIdx; i++) {
                                if (!validate(vis[i]).valid) { key = vis[i]; break; }
                            }
                            currentKey = key;
                            hideHint();
                            updateWizardUI(true);
                            return;
                        }
                    }
                    currentKey = firstIncompleteStep();
                    updateWizardUI(true);
                });

                // Keyboard scrolls the focused field into view above the bar.
                form.addEventListener('focusin', (e) => {
                    if (!isWizardMode()) return;
                    const target = e.target;
                    if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
                        try {
                            target.scrollIntoView?.({ block: 'center' });
                        } catch (err) {}
                    }
                });

                latitudeInput?.addEventListener('input', updatePinLabel);
                longitudeInput?.addEventListener('input', updatePinLabel);

                const serverErrKey = getServerErrorStep();
                const hashMatch = window.location.hash.match(/^#step-([a-z]+)$/);
                const hashKey = hashMatch && WIZARD_STEPS.includes(hashMatch[1]) ? hashMatch[1] : null;

                if (serverErrKey) {
                    currentKey = serverErrKey;
                } else if (isWizardMode() && hashKey && visibleSteps().includes(hashKey)) {
                    const vis = visibleSteps();
                    const idx = vis.indexOf(hashKey);
                    let key = hashKey;
                    for (let i = 0; i < idx; i++) {
                        if (!validate(vis[i]).valid) { key = vis[i]; break; }
                    }
                    currentKey = key;
                } else {
                    currentKey = isWizardMode() ? firstIncompleteStep() : 'group';
                    if (!isWizardMode()) currentKey = 'group';
                }

                const existingDraft = loadDraft();
                if (!window.__FILING_HAS_ERRORS__ && existingDraft && draftBanner) {
                    const meaningful = ['category', 'title', 'description', 'address_text']
                        .filter((key) => existingDraft[key] && String(existingDraft[key]).trim()).length;
                    if (meaningful >= 2) draftBanner.hidden = false;
                }

                draftResumeBtn?.addEventListener('click', () => {
                    applyDraft(existingDraft);
                    if (draftBanner) draftBanner.hidden = true;
                });

                const draftResetBtn = document.getElementById('wizard-draft-reset');
                draftResetBtn?.addEventListener('click', () => {
                    try {
                        sessionStorage.removeItem(draftKey);
                    } catch (e) {}
                    if (draftBanner) draftBanner.hidden = true;
                });

                form.addEventListener('input', () => {
                    saveDraft();
                    updateWizardUI(false);
                    updatePinLabel();
                });
                form.addEventListener('change', () => {
                    saveDraft();
                    updateWizardUI(false);
                    updatePinLabel();
                });

                const bindToggle = (btnId, panelId) => {
                    const btn = document.getElementById(btnId);
                    const panel = document.getElementById(panelId);
                    if (!btn || !panel) return;
                    btn.addEventListener('click', () => {
                        const open = panel.classList.toggle('is-open');
                        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    });
                };
                bindToggle('priority-toggle', 'priority-notice');
                bindToggle('photo-tips-toggle', 'photo-tips-hint');
                bindToggle('visibility-why-toggle', 'visibility-footnote');

                const landmarkToggle = document.getElementById('landmark-toggle');
                const landmarkField = document.getElementById('landmark-field');
                const syncLandmark = () => {
                    if (!landmarkToggle || !landmarkField) return;
                    const hasValue = Boolean(addressInput?.value?.trim());
                    if (hasValue) {
                        landmarkField.classList.add('is-open');
                        landmarkToggle.setAttribute('aria-expanded', 'true');
                    }
                };
                if (landmarkToggle && landmarkField) {
                    landmarkToggle.addEventListener('click', () => {
                        const open = landmarkField.classList.toggle('is-open');
                        landmarkToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                        if (open) addressInput?.focus();
                    });
                    syncLandmark();
                }

                const updateCounters = () => {
                    const titleEl = document.getElementById('title');
                    const titleCounter = document.getElementById('title-counter');
                    if (titleEl && titleCounter) {
                        const max = Number(titleEl.getAttribute('maxlength') || 255);
                        const len = titleEl.value.length;
                        titleCounter.textContent = titleEl.maxLength === 255 || titleEl.getAttribute('maxlength') ? len + ' / ' + max : String(len);
                    }
                    const descEl = document.getElementById('description');
                    const descCounter = document.getElementById('description-counter');
                    if (descEl && descCounter) {
                        const len = descEl.value.trim().length;
                        if (len < 20) {
                            const need = 20 - len;
                            descCounter.textContent = i18n.descMore ? t(i18n.descMore, { count: need }) : need + ' more characters';
                        } else {
                            descCounter.textContent = i18n.descGood || 'Looks good';
                        }
                        if (isWizardMode()) {
                            descEl.style.height = 'auto';
                            descEl.style.height = Math.max(140, descEl.scrollHeight) + 'px';
                        }
                    }
                };
                document.getElementById('title')?.addEventListener('input', updateCounters);
                document.getElementById('description')?.addEventListener('input', updateCounters);
                updateCounters();

                document.querySelectorAll('[data-category-group-change]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        hideHint();
                        goToStep('group');
                    });
                });
                document.querySelectorAll('[data-edit-step]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const key = btn.getAttribute('data-edit-step');
                        if (key) goToStep(key);
                    });
                });

                if (mobileQuery.addEventListener) {
                    mobileQuery.addEventListener('change', () => updateWizardUI(false));
                } else if (mobileQuery.addListener) {
                    mobileQuery.addListener(() => updateWizardUI(false));
                }

                updateWizardUI(false);
                syncTilesFromCategory();
                updateChip();
                updatePinLabel();

                form.addEventListener('submit', (event) => {
                    if (!isWizardMode() && !categorySelect?.value) {
                        event.preventDefault();
                        if (categoryTileError) {
                            categoryTileError.textContent = i18n.hints.category;
                            categoryTileError.hidden = false;
                        }
                        document.getElementById('filing-category')?.scrollIntoView?.({ block: 'start' });
                        document.querySelector('.tile--group input')?.focus();
                        return;
                    }
                    if (categoryTileError) categoryTileError.hidden = true;

                    if (selectedPhotos.length === 0 && !window.__HAS_LOCATION_CONFLICT__) {
                        event.preventDefault();
                        showUploadError('Add at least one clear photo before submitting.');
                        if (isWizardMode()) {
                            currentKey = 'photos';
                            updateWizardUI(true);
                            try {
                                history.pushState(null, '', `#step-photos`);
                            } catch (e) {}
                        } else {
                            document.getElementById('filing-details')?.scrollIntoView?.({ block: 'start' });
                        }
                        return;
                    }

                    try {
                        sessionStorage.removeItem(draftKey);
                    } catch (e) {}

                    submitting = true;
                    const busy = (el) => {
                        if (!el) return;
                        el.disabled = true;
                        el.setAttribute('aria-disabled', 'true');
                        el.setAttribute('aria-busy', 'true');
                        el.classList.add('is-loading');
                    };
                    if (btnNext) busy(btnNext);
                    if (btnSubmitBar) busy(btnSubmitBar);
                    if (submitButton) busy(submitButton);
                });
            };

            // Server-error mapping: first matching step in wizard order wins.
            window.__FILING_ERRORS__ = {
                group: @json($errors->has('category') && !old('category')),
                category: @json($errors->has('category') && (bool) old('category')),
                describe: @json($errors->has('title') || $errors->has('description')),
                photos: @json($errors->has('images') || $errors->has('camera_photo') || $errors->has('image')),
                where: @json($errors->has('barangay') || $errors->has('address_text')),
                pin: @json($errors->has('latitude') || $errors->has('longitude') || $errors->has('location_choice')),
                visibility: @json($errors->has('visibility')),
                review: @json($errors->has('terms')),
            };
            window.__FILING_HAS_ERRORS__ = @json($errors->any());
            window.__HAS_LOCATION_CONFLICT__ = @json((bool) $locationConflict);

            const scheduleInitialize = () => window.setTimeout(initializeFilingPage, 80);
            document.addEventListener('livewire:navigating', () => {
                document.body.classList.remove('page-complaint-create');
            });
            document.addEventListener('livewire:navigated', scheduleInitialize);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', scheduleInitialize);
            } else {
                scheduleInitialize();
            }
        })();
    </script>
@endpush
