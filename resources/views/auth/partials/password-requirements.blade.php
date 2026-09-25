@php
    $passwordTarget = $passwordTarget ?? 'password';
@endphp

<div class="password-requirements" data-password-requirements data-password-target="{{ $passwordTarget }}" aria-live="polite">
    <p class="password-requirements-title">Password requirements</p>
    <ul>
        <li data-password-requirement="length"><span class="password-requirement-marker" aria-hidden="true"></span>At least 8 characters</li>
        <li data-password-requirement="case"><span class="password-requirement-marker" aria-hidden="true"></span>Uppercase and lowercase letters</li>
        <li data-password-requirement="number"><span class="password-requirement-marker" aria-hidden="true"></span>At least one number</li>
    </ul>
</div>
