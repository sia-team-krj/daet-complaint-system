<form data-loading-submit method="POST" action="{{ route('identity.verify.store') }}" enctype="multipart/form-data">
    @csrf

    <fieldset class="settings-group">
        <legend class="settings-group__legend">{{ __('identity.verify.id_type') }}</legend>

        <div class="settings-field">
            <label class="settings-field__label" for="id_type">{{ __('identity.verify.id_type') }} <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="id_type" name="id_type" class="settings-select" required @error('id_type') aria-invalid="true" @enderror>
                @foreach($idTypes as $idType)
                    <option value="{{ $idType }}">{{ $idType }}</option>
                @endforeach
            </select>
            @error('id_type')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
        </div>
        <p class="settings-group__help">{{ __('identity.verify.id_type_hint') }}</p>

        <div class="settings-field">
            <label class="settings-field__label" for="id_number">{{ __('identity.verify.id_number') }} <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="id_number" type="text" name="id_number" class="settings-input" required autocomplete="off" maxlength="128" @error('id_number') aria-invalid="true" @enderror>
            <p class="settings-field__hint" id="id_number_hint">{{ __('identity.verify.id_number_hint') }}</p>
            @error('id_number')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
        </div>

        <p class="settings-field__hint identity-privacy">{{ __('identity.verify.privacy_id_number') }}</p>
    </fieldset>

    <fieldset class="settings-group">
        <legend class="settings-group__legend">{{ __('identity.verify.photo_section_title') }}</legend>

        <div class="identity-photo" data-identity-photo="id_front">
            <div class="identity-photo__head">
                <span class="identity-photo__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-4.5-4.5L7 20"></path></svg>
                </span>
                <div class="identity-photo__heading">
                    <h3 class="identity-photo__title">{{ __('identity.verify.photo_id_front_title') }}</h3>
                    <p class="identity-photo__hint">{{ __('identity.verify.photo_id_front_hint') }}</p>
                </div>
            </div>

            <div class="identity-photo__actions">
                <button type="button" class="tr-button tr-button--secondary" data-identity-camera-start>{{ __('identity.verify.take_photo') }}</button>
                <label class="identity-photo__upload" for="id_front">
                    <input type="file" name="id_front" id="id_front" data-identity-input accept="image/jpeg,image/png,image/webp" class="identity-photo__file-input" required @error('id_front') aria-invalid="true" @enderror>
                    <span>{{ __('identity.verify.upload_file') }}</span>
                </label>
            </div>

            <div class="identity-photo__preview" data-identity-preview hidden>
                <img src="" alt="{{ __('identity.verify.photo_id_front_title') }}" data-identity-preview-image>
                <div class="identity-photo__preview-actions">
                    <button type="button" class="identity-photo__icon-btn" data-identity-retake aria-label="{{ __('identity.verify.retake') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4.5 3 16v3h3l11.5-11.5a2.1 2.1 0 0 0-3-3z"></path><circle cx="12" cy="8.5" r="3.5"></circle></svg>
                    </button>
                    <button type="button" class="identity-photo__icon-btn" data-identity-replace aria-label="{{ __('identity.verify.replace') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" x2="12" y1="3" y2="15"></line></svg>
                    </button>
                    <button type="button" class="identity-photo__icon-btn identity-photo__icon-btn--danger" data-identity-remove aria-label="{{ __('identity.verify.remove') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" x2="6" y1="6" y2="18"></line><line x1="6" x2="18" y1="6" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <div class="identity-photo__camera" data-identity-camera hidden>
                <video data-identity-camera-video playsinline></video>
                <div class="identity-photo__camera-actions">
                    <button type="button" class="identity-photo__icon-btn identity-photo__icon-btn--capture" data-identity-camera-capture aria-label="{{ __('identity.verify.camera_capture') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </button>
                    <button type="button" class="identity-photo__icon-btn" data-identity-camera-cancel aria-label="{{ __('identity.verify.camera_cancel') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" x2="6" y1="6" y2="18"></line><line x1="6" x2="18" y1="6" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <p class="identity-photo__state" data-identity-state></p>
            @error('id_front')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="identity-photo" data-identity-photo="selfie">
            <div class="identity-photo__head">
                <span class="identity-photo__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"></path></svg>
                </span>
                <div class="identity-photo__heading">
                    <h3 class="identity-photo__title">{{ __('identity.verify.photo_selfie_title') }}</h3>
                    <p class="identity-photo__hint">{{ __('identity.verify.photo_selfie_hint') }}</p>
                </div>
            </div>

            <div class="identity-photo__actions">
                <button type="button" class="tr-button tr-button--secondary" data-identity-camera-start>{{ __('identity.verify.take_photo') }}</button>
                <label class="identity-photo__upload" for="selfie">
                    <input type="file" name="selfie" id="selfie" data-identity-input accept="image/jpeg,image/png,image/webp" class="identity-photo__file-input" required @error('selfie') aria-invalid="true" @enderror>
                    <span>{{ __('identity.verify.upload_file') }}</span>
                </label>
            </div>

            <div class="identity-photo__preview" data-identity-preview hidden>
                <img src="" alt="{{ __('identity.verify.photo_selfie_title') }}" data-identity-preview-image>
                <div class="identity-photo__preview-actions">
                    <button type="button" class="identity-photo__icon-btn" data-identity-retake aria-label="{{ __('identity.verify.retake') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4.5 3 16v3h3l11.5-11.5a2.1 2.1 0 0 0-3-3z"></path><circle cx="12" cy="8.5" r="3.5"></circle></svg>
                    </button>
                    <button type="button" class="identity-photo__icon-btn" data-identity-replace aria-label="{{ __('identity.verify.replace') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" x2="12" y1="3" y2="15"></line></svg>
                    </button>
                    <button type="button" class="identity-photo__icon-btn identity-photo__icon-btn--danger" data-identity-remove aria-label="{{ __('identity.verify.remove') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" x2="6" y1="6" y2="18"></line><line x1="6" x2="18" y1="6" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <div class="identity-photo__camera" data-identity-camera hidden>
                <video data-identity-camera-video playsinline></video>
                <div class="identity-photo__camera-actions">
                    <button type="button" class="identity-photo__icon-btn identity-photo__icon-btn--capture" data-identity-camera-capture aria-label="{{ __('identity.verify.camera_capture') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    </button>
                    <button type="button" class="identity-photo__icon-btn" data-identity-camera-cancel aria-label="{{ __('identity.verify.camera_cancel') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" x2="6" y1="6" y2="18"></line><line x1="6" x2="18" y1="6" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <p class="identity-photo__state" data-identity-state></p>
            @error('selfie')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
        </div>
    </fieldset>

    <fieldset class="settings-group">
        <legend class="settings-group__legend">{{ __('identity.verify.consent_title') }}</legend>
        <div class="identity-consent">
            <input type="checkbox" id="consent_purpose" name="consent_purpose" value="1" class="settings-checkbox" required>
            <label class="identity-consent__label" for="consent_purpose">{{ __('identity.verify.consent_purpose') }} <span class="required-mark" aria-hidden="true">*</span></label>
        </div>
        <div class="identity-consent">
            <input type="checkbox" id="terms" name="terms" value="1" class="settings-checkbox" required>
            <label class="identity-consent__label" for="terms">{{ __('identity.verify.terms') }} <span class="required-mark" aria-hidden="true">*</span></label>
        </div>
        <div class="identity-consent">
            <input type="checkbox" id="consent_access" name="consent_access" value="1" class="settings-checkbox" required>
            <label class="identity-consent__label" for="consent_access">{{ __('identity.verify.consent_access') }} <span class="required-mark" aria-hidden="true">*</span></label>
        </div>
        <div class="identity-consent">
            <input type="checkbox" id="consent_retention" name="consent_retention" value="1" class="settings-checkbox" required>
            <label class="identity-consent__label" for="consent_retention">{{ __('identity.verify.consent_retention') }} <span class="required-mark" aria-hidden="true">*</span></label>
        </div>
    </fieldset>

    <div class="settings-save-row">
        <div class="settings-save-row__actions">
            <button type="submit" class="tr-button tr-button--primary" data-identity-submit>{{ __('identity.verify.submit') }}</button>
        </div>
    </div>
</form>
