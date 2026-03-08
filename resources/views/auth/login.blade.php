@extends('layouts.guest')
@section('title', 'Login — Daet Listens')
@section('content')

{{-- When user is logged in, and tried to logged in new/existing account thru new tab after clicking submit button
    Add A notice : Something went wrong and refresh the page, instead of displaying  419 Page Expired
--}}
<div class="auth-root">
  <div class="auth-bg"></div>
  <div class="auth-glow-tr"></div>
  <div class="auth-glow-bl"></div>
  <div class="auth-bar"></div>
  <div class="auth-wm">DAET</div>

  <div class="auth-layout">

    {{-- LEFT: Brand --}}
    <div class="auth-brand">
      <div class="auth-badge fu d1">
        <span class="badge-dot"></span>
        Official Portal &nbsp;·&nbsp; LGU Daet, Camarines Norte
      </div>

      <h2 class="auth-brand-title fu d2">
        Welcome Back<br>
        <span class="gold-line">to Daet Listens.</span>
      </h2>
      <div class="auth-brand-divider fu d2"></div>

      <p class="auth-brand-desc fu d3">
        Sign in to access your complaints, track ongoing cases,
        and hold your local government to a higher standard of
        transparency and accountability.
      </p>

      <div class="auth-stats fu d4">
        <div class="auth-stat">
          <div class="auth-stat-num">{{ $totalComplaints ?? '0' }}</div>
          <div class="auth-stat-lbl">Filed</div>
        </div>
        <div class="auth-stat">
          <div class="auth-stat-num">{{ $resolvedComplaints ?? '0' }}</div>
          <div class="auth-stat-lbl">Resolved</div>
        </div>
        <div class="auth-stat">
          <div class="auth-stat-num">{{ $avgDays ?? '—' }}</div>
          <div class="auth-stat-lbl">Avg. Days</div>
        </div>
      </div>
    </div>

    {{-- RIGHT: Form --}}
    <div class="auth-form-panel">
      <div class="auth-form-wrap">

        <div class="auth-form-eyebrow fu d1">Secure Access</div>
        <h1 class="auth-form-heading fu d2">Sign In</h1>
        <p class="auth-form-sub fu d2">
          Don't have an account?
          <a href="{{ route('register') }}" wire:navigate>Create one here</a>
        </p>

        @if ($errors->any())
          <div class="auth-alert alert-error fu">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
          </div>
        @endif
        @if (session('status'))
          <div class="auth-alert alert-success fu">{{ session('status') }}</div>
        @endif

        {{-- <form method="POST" action=""> --}}
        <form method="POST" action="{{ route('login') }}">
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

          <div class="field-group fu d3">
            <label class="field-label" for="password">Password</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <input id="password" name="password" type="password" class="field-input" placeholder="••••••••••" required autocomplete="current-password">
              <div class="field-underline"></div>
            </div>
          </div>

          <div class="field-opts fu d4">
            <label class="field-check">
              <input type="checkbox" name="remember">
              <span class="field-check-label">Keep me signed in</span>
            </label>
            {{-- <a href="{{ route('password.request') }}" class="forgot-link">Forgot Password?</a> --}}
            <a href="" class="forgot-link">Forgot Password?</a>
          </div>
          {{-- TODO : Add Loading uppon pressing button submit,
          --}}
          <button type="submit" class="btn-submit fu d5">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Sign In to Your Account
          </button>

          <div class="auth-or fu d5">
            <div class="auth-or-line"></div>
            <span class="auth-or-text">or</span>
            <div class="auth-or-line"></div>
          </div>

          <p class="auth-alt-link fu d5">
            New to Daet Listens?
            <a href="{{ route('register') }}"  wire:navigate >Create an account &rarr;</a>
          </p>
        </form>
      </div>
    </div>

  </div><!-- /.auth-layout -->

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

  /*
   * auth-root fills exactly the space below the fixed 64px navbar.
   * position:fixed keeps the navbar visually separate at ALL widths.
   * overflow-y:auto on auth-root itself means the whole page scrolls
   * if content is taller than the viewport (e.g. small laptops).
   */
  .auth-root {
    font-family: 'DM Sans', sans-serif;
    position: fixed;
    top: 64px; left: 0; right: 0; bottom: 0;
    background: var(--navy);
    overflow-y: auto;
  }

  /* Diagonal texture */
  .auth-bg {
    position: absolute; inset: 0; pointer-events: none;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
  }
  /* Glow top-right */
  .auth-glow-tr {
    position: absolute; top: -20%; right: -8%;
    width: 50vw; height: 50vw; max-width: 700px; max-height: 700px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%);
    pointer-events: none;
  }
  /* Glow bottom-left */
  .auth-glow-bl {
    position: absolute; bottom: -20%; left: -8%;
    width: 40vw; height: 40vw; max-width: 560px; max-height: 560px;
    background: radial-gradient(ellipse, rgba(26,53,96,0.6) 0%, transparent 70%);
    pointer-events: none;
  }
  /* Left gold accent bar */
  .auth-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }
  /* Watermark */
  .auth-wm {
    position: absolute; bottom: -4%; right: -1%;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 18vw, 280px); font-weight: 700;
    color: rgba(255,255,255,0.018); line-height: 1;
    pointer-events: none; user-select: none; letter-spacing: -0.02em;
  }

  /*
   * Two-column grid.
   * min-height: 100% means it fills the scrollable area when content is short,
   * but grows taller on small screens so nothing gets cropped.
   */
  .auth-layout {
    position: relative; z-index: 2;
    width: 100%; min-height: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    /* Both columns share the same align-items so they're always the same height */
  }

  /* ─── Brand panel (LEFT) ─── */
  .auth-brand {
    display: flex; flex-direction: column; justify-content: center; align-items:center; text-align:center;
    padding: clamp(40px, 5vh, 80px) clamp(32px, 4vw, 64px) clamp(40px, 5vh, 80px) clamp(40px, 5vw, 80px);
    position: relative;
  }

  /* Vertical separator */
  .auth-brand::after {
    content: '';
    position: absolute; top: 8%; right: 0;
    width: 1px; height: 84%;
    background: linear-gradient(180deg,
      transparent 0%,
      rgba(201,168,76,0.22) 20%,
      rgba(201,168,76,0.22) 80%,
      transparent 100%
    );
  }

  .auth-badge {
    display: inline-flex; align-items: center; gap: 10px;
    border: 1px solid var(--border-gold); border-radius: 3px;
    padding: 7px 16px; margin-bottom: clamp(20px, 3vh, 36px);
    font-size: 10px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase;
    color: rgba(201,168,76,0.8); background: rgba(201,168,76,0.06); width: fit-content;
  }
  .badge-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--gold); flex-shrink: 0;
    animation: dot-pulse 2.4s infinite;
  }
  @keyframes dot-pulse { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:0.3;transform:scale(0.6);} }

  .auth-brand-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 3.2vw, 58px); font-weight: 700;
    line-height: 1.05; color: var(--white);
    margin-bottom: clamp(12px, 2vh, 20px); letter-spacing: -0.01em;
  }
  .auth-brand-title .gold-line { color: var(--gold); font-style: italic; display: block; }

  .auth-brand-divider {
    width: 48px; height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    margin-bottom: clamp(12px, 2vh, 22px);
  }

  .auth-brand-desc {
    font-size: clamp(13px, 1.1vw, 15px); line-height: 1.85;
    color: var(--text-dim); font-weight: 300;
    max-width: 400px; margin-bottom: clamp(24px, 4vh, 48px);
  }

  /* Mini stats */
  .auth-stats {
    display: flex;
    border: 1px solid var(--border-gold); border-radius: 6px;
    overflow: hidden; background: var(--border-gold); width: fit-content;
  }
  .auth-stat {
    background: rgba(9,25,48,0.94);
    padding: clamp(12px, 1.5vh, 18px) clamp(18px, 2vw, 28px);
    transition: background 0.2s; position: relative;
  }
  .auth-stat::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 2px; height: 0; background: var(--gold); transition: height 0.35s ease;
  }
  .auth-stat:hover { background: rgba(18,41,77,0.96); }
  .auth-stat:hover::before { height: 100%; }
  .auth-stat + .auth-stat { border-left: 1px solid var(--border-gold); }
  .auth-stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(20px, 2.2vw, 32px); font-weight: 700; color: var(--gold);
    line-height: 1; margin-bottom: 4px; letter-spacing: -0.01em;
  }
  .auth-stat-lbl {
    font-size: 9px; font-weight: 600; letter-spacing: 0.13em;
    text-transform: uppercase; color: rgba(255,255,255,0.35);
  }

  /* ─── Form panel (RIGHT) ─── */
  .auth-form-panel {
    display: flex; flex-direction: column;
    justify-content: center;     /* vertically centered — matches brand panel */
    align-items: center;
    padding: clamp(40px, 5vh, 80px) clamp(40px, 5vw, 80px) clamp(40px, 5vh, 80px) clamp(32px, 4vw, 64px);
  }

  /* Form card — max-width so it doesn't stretch silly wide on 1920px */
  .auth-form-wrap {
    width: 100%;
    max-width: min(420px, 90%);
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
  .auth-form-sub a { color: var(--gold); text-decoration: none; font-weight: 500; transition: color 0.2s; }
  .auth-form-sub a:hover { color: var(--gold-light); }

  /* Fields */
  .field-group { margin-bottom: 18px; }
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

  /* Remember / Forgot row */
  .field-opts {
    display: flex; align-items: center;
    justify-content: space-between; margin-bottom: 28px; gap: 12px;
  }
  .field-check { display: flex; align-items: center; gap: 8px; cursor: pointer; }
  .field-check input[type="checkbox"] {
    appearance: none; width: 14px; height: 14px;
    border: 1px solid rgba(201,168,76,0.35); border-radius: 2px;
    background: transparent; cursor: pointer; flex-shrink: 0;
    transition: background 0.18s, border-color 0.18s; position: relative;
  }
  .field-check input[type="checkbox"]:checked { background: var(--gold); border-color: var(--gold); }
  .field-check input[type="checkbox"]:checked::after {
    content: ''; position: absolute; top: 1px; left: 3.5px;
    width: 4px; height: 7px;
    border: 1.5px solid var(--navy); border-top: none; border-left: none; transform: rotate(45deg);
  }
  .field-check-label { font-size: 11.5px; color: rgba(255,255,255,0.4); user-select: none; }
  .forgot-link { font-size: 11.5px; color: rgba(201,168,76,0.6); text-decoration: none; font-weight: 500; transition: color 0.2s; white-space: nowrap; }
  .forgot-link:hover { color: var(--gold); }

  /* Submit */
  .btn-submit {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy); font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
    padding: 15px 32px; border-radius: 4px; border: none; cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 24px rgba(201,168,76,0.28); margin-bottom: 24px;
  }
  .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(201,168,76,0.45); }
  .btn-submit:active { transform: translateY(0); }

  /* OR divider */
  .auth-or { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
  .auth-or-line { flex: 1; height: 1px; background: rgba(201,168,76,0.12); }
  .auth-or-text { font-size: 10px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255,255,255,0.2); }

  .auth-alt-link { text-align: center; font-size: 12.5px; color: rgba(255,255,255,0.35); }
  .auth-alt-link a { color: var(--gold); text-decoration: none; font-weight: 600; margin-left: 4px; transition: color 0.2s; }
  .auth-alt-link a:hover { color: var(--gold-light); }

  /* Alerts */
  .auth-alert { border-radius: 4px; padding: 12px 16px; font-size: 12.5px; line-height: 1.6; margin-bottom: 20px; }
  .alert-error { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }
  .alert-success { background: rgba(63,203,111,0.08); border: 1px solid rgba(63,203,111,0.2); color: #6ee7a0; }

  /* Footer */
  .auth-footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #060f1e; border-top: 1px solid rgba(201,168,76,0.1);
    padding: 10px clamp(20px, 3vw, 48px);
    display: flex; align-items: center; justify-content: space-between;
    z-index: 100;
  }
  .auth-footer p { font-size: 10px; color: rgba(255,255,255,0.2); letter-spacing: 0.06em; }
  .auth-footer strong { color: rgba(201,168,76,0.4); }
  .footer-seal { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.12); }

  /* Animations */
  @keyframes fadeUp { from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);} }
  .fu{animation:fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) both;}
  .d1{animation-delay:0.04s;}.d2{animation-delay:0.16s;}
  .d3{animation-delay:0.28s;}.d4{animation-delay:0.40s;}
  .d5{animation-delay:0.52s;}

  /* ── Responsive ── */

  /* Tablet: still two columns but tighter */
  @media (max-width: 1100px) and (min-width: 769px) {
    .auth-brand { padding: 40px 32px 40px 40px; }
    .auth-form-panel { padding: 40px 40px 40px 32px; }
    .auth-brand-desc { max-width: 100%; }
  }

  /* Mobile: single column, auth-root scrolls normally */
  @media (max-width: 768px) {
    .auth-root { position: static; min-height: calc(100svh - 64px); overflow: visible; }
    .auth-layout { grid-template-columns: 1fr; min-height: calc(100svh - 64px); }
    .auth-brand { display: none; }
    .auth-form-panel { padding: 48px 24px 80px; justify-content: flex-start; }
    .auth-wm { display: none; }
    .auth-footer { position: static; margin-top: 0; }
  }

  @media (max-width: 480px) {
    .auth-form-panel { padding: 36px 16px 80px; }
    .field-opts { flex-direction: column; align-items: flex-start; }
  }
</style>


@endsection
