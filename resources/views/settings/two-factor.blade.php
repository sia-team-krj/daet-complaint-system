@php
    // Same shell routing as the settings page itself: super admins render in
    // the admin rail, department staff (verifiers included) in the staff rail,
    // and everyone else in the standalone portal layout.
    $settingsLayout = match ($user->role) {
        'citizen' => 'layouts.app',
        default => $user->isAdmin() ? 'admin.layouts.app' : 'staff.layouts.app',
    };
    $settingsContentSection = match ($user->role) {
        'citizen' => 'content',
        default => $user->isAdmin() ? 'admin-content' : 'staff-content',
    };
@endphp
@extends($settingsLayout)
@section('title', __('settings.two_factor.title').' - Daet Listens')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@section($settingsContentSection)
<div class="settings-page">
    <div class="tr-container settings-container">
        <header class="settings-header">
            <h1 class="tr-h1 settings-header__title">{{ __('settings.two_factor.setup_title') }}</h1>
            <p class="tr-privacy settings-header__lede">{{ __('settings.two_factor.setup_lede') }}</p>
        </header>

        @include('settings.two-factor-card', ['user' => $user, 'confirmed' => $confirmed, 'needsPassword' => $needsPassword, 'secret' => $secret, 'qrSvg' => $qrSvg, 'recoveryCodes' => $recoveryCodes, 'remainingRecoveryCodes' => $remainingRecoveryCodes])
    </div>
</div>
@endsection
