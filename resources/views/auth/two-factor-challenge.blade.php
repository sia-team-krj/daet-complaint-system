@extends('layouts.app')
@section('title', __('settings.two_factor.challenge_title').' - Daet Listens')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@section('content')
<div class="settings-page">
    <div class="tr-container settings-container">

        <header class="settings-header">
            <h1 class="tr-h1 settings-header__title">{{ __('settings.two_factor.challenge_title') }}</h1>
            <p class="tr-privacy settings-header__lede">{{ __('settings.two_factor.challenge_lede') }}</p>
        </header>

        <section class="tr-card settings-card">
            <header class="settings-card__head">
                <h2 class="settings-card__title">{{ __('settings.two_factor.challenge_title') }}</h2>
                <p class="settings-card__lede">{{ __('settings.two_factor.challenge_lede') }}</p>
            </header>

            @if(session('status'))
                <p class="settings-group__help" role="status">{{ session('status') }}</p>
            @endif

            <form data-loading-submit method="POST" action="{{ route('two-factor.verify') }}">
                @csrf

                <fieldset class="settings-group">
                    <legend class="settings-group__legend">{{ __('settings.two_factor.challenge_code') }}</legend>

                    <div class="settings-field">
                        <label class="settings-field__label" for="two-factor-challenge-code">{{ __('settings.two_factor.challenge_code') }} <span class="required-mark" aria-hidden="true">*</span></label>
                        <input id="two-factor-challenge-code" type="text" name="code" class="settings-input" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required autofocus aria-describedby="two-factor-challenge-help" @error('code') aria-invalid="true" @enderror>
                        <p class="settings-field__hint" id="two-factor-challenge-help">{{ __('settings.two_factor.challenge_code_hint') }}</p>
                        @error('code')<p class="settings-field__error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <div class="settings-save-row">
                    <div class="settings-save-row__actions">
                        <button type="submit" class="tr-button tr-button--primary">{{ __('settings.two_factor.verify') }}</button>
                    </div>
                </div>
            </form>

            @if($recoveryAvailable)
                <p class="settings-group__help">{{ __('settings.two_factor.recovery_help') }}</p>
            @endif

            <div class="settings-save-row">
                <div class="settings-save-row__actions">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="tr-button tr-button--secondary">{{ __('settings.two_factor.sign_out') }}</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
