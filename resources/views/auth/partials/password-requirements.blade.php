@php
    $passwordTarget = $passwordTarget ?? 'password';
@endphp

<div class="password-requirements" data-password-requirements data-password-target="{{ $passwordTarget }}" aria-live="polite">
    <p class="password-requirements-title">{{ __('auth_ui.password_requirements.title') }}</p>
    <ul>
        <li data-password-requirement="length"><span class="password-requirement-marker" aria-hidden="true"></span>{{ __('auth_ui.password_requirements.length') }}</li>
        <li data-password-requirement="uppercase"><span class="password-requirement-marker" aria-hidden="true"></span>{{ __('auth_ui.password_requirements.uppercase') }}</li>
        <li data-password-requirement="lowercase"><span class="password-requirement-marker" aria-hidden="true"></span>{{ __('auth_ui.password_requirements.lowercase') }}</li>
        <li data-password-requirement="number"><span class="password-requirement-marker" aria-hidden="true"></span>{{ __('auth_ui.password_requirements.number') }}</li>
    </ul>
</div>
