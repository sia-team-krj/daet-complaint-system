@component('mail::message')
# Verify your {{ $appName }} email

@if ($purpose === 'email_change')
    We received a request to change the email address on your account. Use the code below to confirm the new address.
@else
    Use the code below to verify your email address and activate your {{ $appName }} account.
@endif

**Your verification code:** `{{ $code }}`

This code expires in {{ $expiryMinutes }} minutes. For your security, do not share it with anyone.

@component('mail::button', ['url' => route('email.verify', ['purpose' => $purpose]), 'color' => 'primary'])
Open verification page
@endcomponent

If you did not request this, you can safely ignore this email.

Thanks,<br>
{{ config('mail.from.name', $appName) }}
@endcomponent
