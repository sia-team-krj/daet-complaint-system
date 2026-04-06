@extends('layouts.guest')
@section('title', 'Forgot Password — Daet Listens')
@section('content')

<div class="auth-root">

  {{-- Decorative elements --}}
  <div class="auth-decorations">
    <div class="auth-bg"></div>
    <div class="auth-glow-tr"></div>
    <div class="auth-glow-bl"></div>
    <div class="auth-bar"></div>
    <div class="auth-wm">DAET</div>
  </div>

  {{-- Centered single card layout (no split) --}}
  <div class="auth-layout auth-layout-centered">
    <div class="auth-form-panel">
      <div class="auth-form-wrap">

        <div class="auth-form-eyebrow fu d1">Account Recovery</div>
        <h1 class="auth-form-heading fu d2">Reset Password</h1>
        <p class="auth-form-sub fu d2">
          Enter your email address and we'll send you a secure link to reset your password. The link will expire in 60 minutes.
        </p>

        @if ($errors->any())
          <div class="auth-alert alert-error fu">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
          </div>
        @endif
        @if (session('status'))
          <div class="auth-alert alert-success fu">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
          @csrf

          <div class="field-group fu d3">
            <label class="field-label" for="email">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </span>
              <input id="email" name="email" type="email" class="field-input" placeholder="your@email.com" value="{{ old('email') }}" required autocomplete="email" autofocus>
              <div class="field-underline"></div>
            </div>
          </div>

          <button type="submit" class="btn-submit fu d4">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send Reset Link
          </button>

          <div class="auth-alt-link fu d5">
            <a href="{{ route('login') }}" wire:navigate>&larr; Back to Sign In</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="auth-footer">
    <p><strong>Municipality of Daet</strong> &nbsp;&middot;&nbsp; Camarines Norte &nbsp;&middot;&nbsp; Official Transparency Portal</p>
    <span class="footer-seal">Republic Act 6713 &nbsp;&middot;&nbsp; FOI Compliant</span>
  </div>
</div>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');

  :root {
    --navy:        #0B1F3A;
    --navy-mid:    #12294d;
    --gold:        #C9A84C;
    --gold-light:  #E2C06A;
    --gold-pale:   rgba(201,168,76,0.12);
    --white:       #ffffff;
    --text-dim:    rgba(255,255,255,0.55);
    --border-gold: rgba(201,168,76,0.20);
  }

  .auth-root, .auth-root * { box-sizing: border-box; }

  .auth-root {
    font-family: 'DM Sans', sans-serif;
    padding-top: 64px;
    min-height: 100svh;
    background: var(--navy);
    position: relative;
    display: flex;
    flex-direction: column;
  }

  .auth-decorations {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
  }

  .auth-bg {
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
  }
  .auth-glow-tr {
    position: absolute; top: -20%; right: -8%;
    width: 50vw; height: 50vw; max-width: 700px; max-height: 700px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%);
  }
  .auth-glow-bl {
    position: absolute; bottom: -20%; left: -8%;
    width: 40vw; height: 40vw; max-width: 560px; max-height: 560px;
    background: radial-gradient(ellipse, rgba(26,53,96,0.6) 0%, transparent 70%);
  }
  .auth-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }
  .auth-wm {
    position: absolute; bottom: -4%; right: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 18vw, 280px); font-weight: 700;
    color: rgba(255,255,255,0.018); line-height: 1;
    user-select: none; letter-spacing: -0.02em;
  }

  .auth-layout-centered {
    position: relative; z-index: 2;
    width: 100%; flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(40px, 5vh, 80px) clamp(20px, 3vw, 48px);
  }

  .auth-form-panel {
    display: flex; flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
  }
  .auth-form-wrap {
    width: 100%;
    max-width: 420px;
  }

  .auth-form-eyebrow {
    display: inline-flex; align-items: center; gap: 12px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 10px;
  }
  .auth-form-eyebrow::before { content: ''; width: 24px; height: 1px; background: var(--gold); }

  .auth-form-heading {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 2.8vw, 44px); font-weight: 700;
    color: var(--white); line-height: 1.08; margin-bottom: 8px; letter-spacing: -0.01em;
  }
  .auth-form-sub {
    font-size: 13px; color: var(--text-dim); font-weight: 300; line-height: 1.7; margin-bottom: 32px;
  }

  .field-group { margin-bottom: 24px; }
  .field-label {
    display: block; font-size: 10.5px; font-weight: 600;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: rgba(255,255,255,0.45); margin-bottom: 8px;
  }
  .field-wrap { position: relative; }
  .field-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: rgba(201,168,76,0.5); pointer-events: none; transition: color 0.2s;
  }
  .field-wrap:focus-within .field-icon { color: rgba(201,168,76,0.9); }
  .field-input {
    width: 100%; background: rgba(255,255,255,0.04);
    border: 1px solid rgba(201,168,76,0.18); border-radius: 4px;
    padding: 13px 14px 13px 42px;
    font-family: 'DM Sans', sans-serif; font-size: 13.5px; color: var(--white);
    outline: none; transition: border-color 0.22s, background 0.22s, box-shadow 0.22s;
  }
  .field-input::placeholder { color: rgba(255,255,255,0.2); }
  .field-input:focus {
    border-color: rgba(201,168,76,0.55); background: rgba(255,255,255,0.06);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.06);
  }
  .field-underline {
    position: absolute; bottom: 0; left: 0; width: 0; height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    transition: width 0.35s ease; border-radius: 0 0 4px 4px;
  }
  .field-input:focus ~ .field-underline { width: 100%; }

  .btn-submit {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy); font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
    padding: 15px 32px; border-radius: 4px; border: none; cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 24px rgba(201,168,76,0.28); margin-bottom: 20px;
  }
  .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(201,168,76,0.45); }
  .btn-submit:active { transform: translateY(0); }

  .auth-alt-link { text-align: center; font-size: 12.5px; }
  .auth-alt-link a { color: rgba(255,255,255,0.4); text-decoration: none; font-weight: 500; transition: color 0.2s; }
  .auth-alt-link a:hover { color: var(--gold); }

  .auth-alert { border-radius: 4px; padding: 12px 16px; font-size: 12.5px; line-height: 1.6; margin-bottom: 20px; }
  .alert-error { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }
  .alert-success { background: rgba(63,203,111,0.08); border: 1px solid rgba(63,203,111,0.2); color: #6ee7a0; }

  .auth-footer {
    position: relative; z-index: 2;
    background: #060f1e; border-top: 1px solid rgba(201,168,76,0.1);
    padding: 10px clamp(20px, 3vw, 48px);
    display: flex; align-items: center; justify-content: space-between;
  }
  .auth-footer p { font-size: 10px; color: rgba(255,255,255,0.2); letter-spacing: 0.06em; }
  .auth-footer strong { color: rgba(201,168,76,0.4); }
  .footer-seal { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.12); }

  @keyframes fadeUp { from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);} }
  .fu  { animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) both; }
  .d1  { animation-delay: 0.04s; } .d2 { animation-delay: 0.16s; }
  .d3  { animation-delay: 0.28s; } .d4 { animation-delay: 0.40s; }
  .d5  { animation-delay: 0.52s; }

  @media (max-width: 768px) {
    .auth-root { min-height: calc(100svh - 64px); }
    .auth-layout-centered { padding: 48px 24px 80px; }
    .auth-wm { display: none; }
    .auth-footer { position: static; flex-direction: column; gap: 8px; text-align: center; }
  }

  @media (max-width: 480px) {
    .auth-layout-centered { padding: 36px 16px 80px; }
    .auth-form-wrap { max-width: 100%; }
  }
</style>

@endsection
