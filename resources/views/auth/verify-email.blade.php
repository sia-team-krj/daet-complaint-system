@extends('layouts.app')
@section('title', __('auth_ui.titles.verify'))

@section('content')
<style>
  .verification-page {
    min-height: calc(100svh - var(--navbar-height));
    display: grid;
    place-items: center;
    padding: 48px 20px 72px;
    background:
      radial-gradient(circle at 50% 0%, rgba(201, 168, 76, 0.12), transparent 38%),
      linear-gradient(135deg, var(--color-navy) 0%, var(--color-navy-mid) 100%);
  }

  .verification-shell {
    width: min(100%, 460px);
    padding: 40px;
    color: #fff;
    text-align: center;
    background: rgba(255, 255, 255, 0.045);
    border: 1px solid rgba(201, 168, 76, 0.25);
    border-radius: 8px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
  }

  .verification-mark {
    width: 58px;
    height: 58px;
    display: inline-grid;
    place-items: center;
    margin-bottom: 20px;
    color: var(--color-gold);
    background: rgba(201, 168, 76, 0.12);
    border: 1px solid rgba(201, 168, 76, 0.35);
    border-radius: 50%;
  }

  .verification-eyebrow {
    margin: 0 0 8px;
    color: var(--color-gold);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }

  .verification-title {
    margin: 0 0 12px;
    font-family: var(--font-display);
    font-size: clamp(32px, 5vw, 44px);
    line-height: 1.05;
  }

  .verification-copy {
    margin: 0 auto 26px;
    max-width: 38ch;
    color: rgba(255, 255, 255, 0.62);
    font-size: 13px;
    line-height: 1.7;
  }

  .verification-email {
    color: #fff;
    font-weight: 600;
    overflow-wrap: anywhere;
  }

  .verification-form {
    text-align: left;
  }

  .verification-label {
    display: block;
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.72);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .verification-input {
    width: 100%;
    padding: 15px 16px;
    color: #fff;
    font-family: var(--font-sans);
    font-size: 24px;
    letter-spacing: 0.32em;
    text-align: center;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(201, 168, 76, 0.3);
    border-radius: 4px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .verification-input:focus {
    border-color: var(--color-gold);
    box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.12);
  }

  .verification-error {
    margin: 8px 0 0;
    color: #fca5a5;
    font-size: 11px;
  }

  .verification-submit,
  .verification-resend,
  .verification-signout {
    width: 100%;
    min-height: 46px;
    margin-top: 16px;
    padding: 13px 18px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s, border-color 0.2s;
  }

  .verification-submit {
    color: var(--color-navy);
    background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
    border: 0;
  }

  .verification-submit:hover,
  .verification-resend:hover {
    transform: translateY(-1px);
  }

  .verification-resend,
  .verification-signout {
    color: var(--color-gold);
    background: transparent;
    border: 1px solid rgba(201, 168, 76, 0.4);
  }

  /* Counting down: the button is inert, so it must not look clickable. The
     hover lift is explicitly cancelled because it otherwise still fires on a
     disabled control and invites the click that the server will reject. */
  .verification-resend[disabled] {
    color: rgba(201, 168, 76, 0.55);
    border-color: rgba(201, 168, 76, 0.2);
    cursor: not-allowed;
    transform: none;
  }

  .verification-signout {
    margin-top: 10px;
    color: rgba(255, 255, 255, 0.5);
    border-color: rgba(255, 255, 255, 0.15);
  }

  .verification-note {
    margin: 18px 0 0;
    color: rgba(255, 255, 255, 0.38);
    font-size: 10px;
    line-height: 1.5;
  }

  .verification-alert {
    margin-bottom: 18px;
    padding: 11px 13px;
    color: #fca5a5;
    font-size: 12px;
    line-height: 1.5;
    text-align: left;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 4px;
  }

  .verification-success {
    color: #9de7b5;
    background: rgba(63, 203, 111, 0.1);
    border-color: rgba(63, 203, 111, 0.25);
  }

  @media (max-width: 480px) {
    .verification-page {
      place-items: start center;
      padding: 24px 14px 48px;
    }

    .verification-shell {
      padding: 30px 22px;
    }
  }
</style>

<div class="verification-page">
  <div class="verification-shell">
    <div class="verification-mark" aria-hidden="true">
      <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
        <path d="m3 7 9 6 9-6"></path>
      </svg>
    </div>

    <p class="verification-eyebrow">{{ __('auth_ui.verify.eyebrow') }}</p>
    <h1 class="verification-title">{{ __('auth_ui.verify.heading') }}</h1>
    <p class="verification-copy">
      @if ($isEmailChange)
        {{ __('auth_ui.verify.email_change') }}
      @else
        {{ __('auth_ui.verify.sent_code_before') }} <span class="verification-email">{{ $verificationEmail }}</span>{{ __('auth_ui.verify.sent_code_after') }}
      @endif
    </p>

    @if (session('error'))
      <div class="verification-alert" role="alert">{{ session('error') }}</div>
    @endif

    @if (session('status'))
      <div class="verification-alert verification-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('email.verify.submit') }}" class="verification-form">
      @csrf
      <input type="hidden" name="purpose" value="{{ old('purpose', $purpose) }}">

      <label class="verification-label" for="otp">{{ __('auth_ui.verify.code_label') }}</label>
      <input
        id="otp"
        name="code"
        type="text"
        inputmode="numeric"
        autocomplete="one-time-code"
        pattern="[0-9]{6}"
        maxlength="6"
        placeholder="000000"
        class="verification-input"
        required
        autofocus
      >
      @error('code')
        <p class="verification-error" role="alert">{{ $message }}</p>
      @enderror
      @error('email')
        <p class="verification-error" role="alert">{{ $message }}</p>
      @enderror

      <button type="submit" class="verification-submit">{{ __('auth_ui.verify.verify_button') }}</button>
    </form>

    <p class="verification-note">{{ __('auth_ui.verify.expires', ['minutes' => config('email-verification.expires_in_minutes', 10)]) }}</p>

    {{--
        The button is deliberately NOT disabled in the markup. Without
        JavaScript it stays clickable, and the server rejects the request with an
        accurate "wait N seconds" message. The disabled state is applied by the
        script below once it takes over, so the countdown is an enhancement and
        never the only thing standing between a user and a rejected POST.
    --}}
    <form method="POST" action="{{ route('email.verify.resend') }}">
      @csrf
      <input type="hidden" name="purpose" value="{{ old('purpose', $purpose) }}">
      <button
        type="submit"
        class="verification-resend"
        data-resend-button
        data-remaining="{{ $resendAvailableIn }}"
        data-label="{{ __('auth_ui.verify.resend') }}"
      ><span data-resend-label>{{ __('auth_ui.verify.resend') }}</span></button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="verification-signout">{{ __('auth_ui.verify.sign_out') }}</button>
    </form>
  </div>
</div>

{{--
    Counts the resend cooldown down in place.

    The server is the authority on the limit - this only stops the button being
    pressed while it is known to be blocked, and keeps the label honest about
    how long is left. It is derived from data-remaining, which the server
    renders from the rate limiter, so a page reload resumes the real remaining
    time rather than restarting a client-side guess.
--}}
<script>
  (function () {
    var button = document.querySelector('[data-resend-button]');

    if (!button) {
      return;
    }

    var label = button.querySelector('[data-resend-label]');
    var idle = button.getAttribute('data-label') || 'Send a new code';
    var remaining = parseInt(button.getAttribute('data-remaining') || '0', 10);

    if (isNaN(remaining) || remaining <= 0) {
      return;
    }

    var render = function (seconds) {
      button.disabled = seconds > 0;
      label.textContent = seconds > 0 ? 'Send a new code in ' + seconds + 's' : idle;
    };

    render(remaining);

    var timer = setInterval(function () {
      remaining -= 1;

      if (remaining <= 0) {
        clearInterval(timer);
        render(0);

        return;
      }

      render(remaining);
    }, 1000);
  })();
</script>
@endsection
