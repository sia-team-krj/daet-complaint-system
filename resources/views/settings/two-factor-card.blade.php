<section class="tr-card settings-card" id="two-factor-auth-title">
        <header class="settings-card__head">
            <h2 class="settings-card__title">{{ __('settings.two_factor.title') }}</h2>
            @unless($confirmed)
                <p class="settings-card__lede">{{ __('settings.two_factor.lede') }}</p>
            @endunless
        </header>

        @if(session('status'))
            <p class="settings-group__help" role="status">{{ session('status') }}</p>
        @endif
        @if(session('error'))
            <p class="settings-field__error" role="alert">{{ session('error') }}</p>
        @endif

        @if(count($recoveryCodes) > 0)
            <fieldset class="settings-group">
                <legend class="settings-group__legend">{{ __('settings.two_factor.recovery_codes_title') }}</legend>
                <p class="settings-group__help">{{ __('settings.two_factor.recovery_codes_lede') }}</p>

                <div class="settings-field">
                    <label class="settings-field__label" for="two-factor-recovery-codes">{{ __('settings.two_factor.recovery_codes_title') }}</label>
                    <textarea id="two-factor-recovery-codes" class="settings-input" rows="9" readonly autocomplete="off" spellcheck="false" aria-describedby="two-factor-recovery-codes-help">{{ implode("\n", $recoveryCodes) }}</textarea>
                    <p class="settings-field__hint" id="two-factor-recovery-codes-help">{{ __('settings.two_factor.recovery_codes_once') }}</p>
                </div>
            </fieldset>
        @endif

        @if($needsPassword)
            <form data-loading-submit method="POST" action="{{ route('settings.two-factor.start') }}">
                @csrf

                <fieldset class="settings-group">
                    <legend class="settings-group__legend">{{ __('settings.two_factor.start_title') }}</legend>
                    <p class="settings-group__help">{{ __('settings.two_factor.start_lede') }}</p>

                    <div class="settings-field">
                        <label class="settings-field__label" for="two-factor-current-password">{{ __('settings.two_factor.current_password') }} <span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="two-factor-current-password" type="password" name="current_password" class="settings-input" required autocomplete="current-password" @error('current_password') aria-invalid="true" @enderror>
                        @error('current_password')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <div class="settings-save-row">
                    <div class="settings-save-row__actions">
                        <button type="submit" class="tr-button tr-button--primary">{{ __('settings.two_factor.start') }}</button>
                    </div>
                </div>
            </form>
        @elseif($secret && ! $confirmed)
            <fieldset class="settings-group">
                <legend class="settings-group__legend">{{ __('settings.two_factor.scan_title') }}</legend>
                <p class="settings-group__help">{{ __('settings.two_factor.scan_lede') }}</p>

                <div role="img" aria-label="{{ __('settings.two_factor.scan_title') }}">
                    {!! $qrSvg !!}
                </div>

                <div class="settings-field">
                    <label class="settings-field__label" for="two-factor-secret">{{ __('settings.two_factor.manual_secret') }}</label>
                    <input id="two-factor-secret" class="settings-input" type="text" value="{{ $secret }}" readonly autocomplete="off" spellcheck="false">
                </div>
            </fieldset>

            <form data-loading-submit method="POST" action="{{ route('settings.two-factor.enable') }}">
                @csrf

                <fieldset class="settings-group">
                    <legend class="settings-group__legend">{{ __('settings.two_factor.code') }}</legend>

                    <div class="settings-field">
                        <label class="settings-field__label" for="two-factor-code">{{ __('settings.two_factor.code') }} <span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="two-factor-code" type="text" name="code" class="settings-input" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required aria-describedby="two-factor-code-help" @error('code') aria-invalid="true" @enderror>
                        <p class="settings-field__hint" id="two-factor-code-help">{{ __('settings.two_factor.code_hint') }}</p>
                        @error('code')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <div class="settings-save-row">
                    <div class="settings-save-row__actions">
                        <button type="submit" class="tr-button tr-button--primary">{{ __('settings.two_factor.enable') }}</button>
                    </div>
                </div>
            </form>
        @elseif($confirmed)
            <fieldset class="settings-group">
                <legend class="settings-group__legend">{{ __('settings.two_factor.status') }}</legend>
                <p class="settings-group__help">
                    {{ __('settings.two_factor.enabled_status') }}
                    @if($user->two_factor_confirmed_at)
                        — {{ __('settings.two_factor.enabled_on', ['date' => $user->two_factor_confirmed_at->locale(app()->getLocale())->translatedFormat('M j, Y')]) }}
                    @endif
                    · {{ __('settings.two_factor.recovery_codes_remaining', ['count' => $remainingRecoveryCodes]) }}
                </p>
                @if($remainingRecoveryCodes === 0)
                    <p class="settings-field__error" role="alert">{{ __('settings.two_factor.no_recovery_codes') }}</p>
                @endif
            </fieldset>

            <form data-loading-submit method="POST" action="{{ route('settings.two-factor.recovery-codes') }}">
                @csrf

                <fieldset class="settings-group">
                    <legend class="settings-group__legend">{{ __('settings.two_factor.recovery_codes_title') }}</legend>
                    <p class="settings-group__help">{{ __('settings.two_factor.regenerate_help') }}</p>

                    <div class="settings-field">
                        <label class="settings-field__label" for="two-factor-recovery-code">{{ __('settings.two_factor.code') }} <span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="two-factor-recovery-code" type="text" name="recovery_code" class="settings-input" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required @error('recovery_code') aria-invalid="true" @enderror>
                        @error('recovery_code')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <div class="settings-save-row">
                    <div class="settings-save-row__actions">
                        <button type="submit" class="tr-button tr-button--secondary">{{ __('settings.two_factor.regenerate_recovery_codes') }}</button>
                    </div>
                </div>
            </form>

            <form data-loading-submit method="POST" action="{{ route('settings.two-factor.re-enroll') }}">
                @csrf

                <fieldset class="settings-group">
                    <legend class="settings-group__legend">{{ __('settings.two_factor.replace_authenticator_legend') }}</legend>
                    <p class="settings-group__help">{{ __('settings.two_factor.replace_authenticator_help') }}</p>

                    <div class="settings-field">
                        <label class="settings-field__label" for="two-factor-re-enroll-code">{{ __('settings.two_factor.code') }} <span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="two-factor-re-enroll-code" type="text" name="re_enroll_code" class="settings-input" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required @error('re_enroll_code') aria-invalid="true" @enderror>
                        @error('re_enroll_code')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <div class="settings-save-row">
                    <div class="settings-save-row__actions">
                        <button type="submit" class="tr-button tr-button--secondary">{{ __('settings.two_factor.replace_authenticator') }}</button>
                    </div>
                </div>
            </form>
        @endif

        @unless($confirmed)
            <div class="settings-save-row">
                <div class="settings-save-row__actions">
                    <a href="{{ route('settings', ['tab' => 'security']) }}" class="tr-button tr-button--secondary">{{ __('settings.two_factor.back_to_settings') }}</a>
                </div>
            </div>
        @else
            <div class="settings-save-row">
                <div class="settings-save-row__actions">
                    <a href="{{ route('settings', ['tab' => 'security']) }}" class="tr-button tr-button--secondary">{{ __('settings.two_factor.back_to_settings') }}</a>
                </div>
            </div>
        @endunless
    </section>