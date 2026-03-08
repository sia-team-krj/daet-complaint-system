@extends('layouts.guest')
@section('title', 'Create Account — Daet Listens')
@section('content')

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
   * Register has more form fields so the right panel CAN overflow vertically
   * on short screens. We allow auth-root itself to scroll.
   */
  .auth-root {
    font-family: 'DM Sans', sans-serif;
    position: fixed;
    top: 64px; left: 0; right: 0; bottom: 0;
    background: var(--navy);
    overflow-y: auto;
  }

  .auth-bg {
    position: absolute; inset: 0; pointer-events: none;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
  }
  .auth-glow-tr {
    position: absolute; top: -20%; right: -8%;
    width: 50vw; height: 50vw; max-width: 700px; max-height: 700px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%);
    pointer-events: none;
  }
  .auth-glow-bl {
    position: absolute; bottom: -20%; left: -8%;
    width: 40vw; height: 40vw; max-width: 560px; max-height: 560px;
    background: radial-gradient(ellipse, rgba(26,53,96,0.6) 0%, transparent 70%);
    pointer-events: none;
  }
  .auth-bar {
    position: absolute; top: 0; left: 0; width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }
  .auth-wm {
    position: absolute; bottom: -4%; right: -1%;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 18vw, 280px); font-weight: 700;
    color: rgba(255,255,255,0.018); line-height: 1;
    pointer-events: none; user-select: none; letter-spacing: -0.02em;
  }

  /* Two-column grid — min-height grows with content */
  .auth-layout {
    position: relative; z-index: 2;
    width: 100%; min-height: 100%;
    display: grid; grid-template-columns: 1fr 1fr;
  }

  /* ─── Brand panel (LEFT) ─── */
  .auth-brand {
    display: flex; flex-direction: column; justify-content: center; align-items:center; text-align:center;
    padding: clamp(40px, 5vh, 80px) clamp(32px, 4vw, 64px) clamp(40px, 5vh, 80px) clamp(40px, 5vw, 80px);
    position: relative;
    /* Sticky so brand panel scrolls with the page but stays in view */
    position: sticky; top: 0; align-self: start;
    min-height: calc(100vh - 64px);
  }

  .auth-brand::after {
    content: ''; position: absolute; top: 8%; right: 0;
    width: 1px; height: 84%;
    background: linear-gradient(180deg, transparent, rgba(201,168,76,0.22) 20%, rgba(201,168,76,0.22) 80%, transparent);
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
    background: var(--gold); flex-shrink: 0; animation: dot-pulse 2.4s infinite;
  }
  @keyframes dot-pulse { 0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.3;transform:scale(0.6);} }

  .auth-brand-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 3.2vw, 56px); font-weight: 700;
    line-height: 1.05; color: var(--white);
    margin-bottom: clamp(12px, 2vh, 18px); letter-spacing: -0.01em;
  }
  .auth-brand-title .gold-line { color: var(--gold); font-style: italic; display: block; }
  .auth-brand-divider { width: 48px; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); margin-bottom: clamp(12px, 2vh, 20px); }
  .auth-brand-desc { font-size: clamp(13px, 1.1vw, 15px); line-height: 1.85; color: var(--text-dim); font-weight: 300; max-width: 400px; margin-bottom: clamp(24px, 4vh, 40px); }

  /* Benefits */
  .auth-benefits { display: flex; flex-direction: column; gap: clamp(12px, 1.8vh, 20px); }
  .auth-benefit { display: flex; align-items: flex-start; gap: 14px; }
  .auth-benefit-icon {
    width: 34px; height: 34px; flex-shrink: 0;
    border: 1px solid var(--border-gold); border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    background: var(--gold-pale); color: var(--gold);
    transition: background 0.2s, border-color 0.2s;
  }
  .auth-benefit:hover .auth-benefit-icon { background: rgba(201,168,76,0.2); border-color: rgba(201,168,76,0.45); }
  .auth-benefit-title { font-family: 'Cormorant Garamond', serif; font-size: 15px; font-weight: 700; color: var(--white); margin-bottom: 2px; }
  .auth-benefit-desc { font-size: 11.5px; color: rgba(255,255,255,0.32); line-height: 1.6; font-weight: 300; }

  /* ─── Form panel (RIGHT) ─── */
  .auth-form-panel {
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: clamp(40px, 5vh, 72px) clamp(40px, 5vw, 80px) clamp(48px, 6vh, 80px) clamp(32px, 4vw, 64px);
  }

  .auth-form-wrap {
    width: 100%;
    max-width: min(440px, 90%);
  }

  .auth-form-eyebrow { display: inline-flex; align-items: center; gap: 12px; font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: var(--gold); margin-bottom: 10px; }
  .auth-form-eyebrow::before { content: ''; width: 24px; height: 1px; background: var(--gold); }
  .auth-form-heading { font-family: 'Cormorant Garamond', serif; font-size: clamp(28px, 2.8vw, 42px); font-weight: 700; color: var(--white); line-height: 1.08; margin-bottom: 8px; letter-spacing: -0.01em; }
  .auth-form-sub { font-size: 13px; color: var(--text-dim); font-weight: 300; line-height: 1.7; margin-bottom: 28px; }
  .auth-form-sub a { color: var(--gold); text-decoration: none; font-weight: 500; transition: color 0.2s; }
  .auth-form-sub a:hover { color: var(--gold-light); }

  /* Fields */
  .field-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .field-group { margin-bottom: 16px; }
  .field-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(255,255,255,0.45); margin-bottom: 7px; }
  .field-wrap { position: relative; }
  .field-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(201,168,76,0.5); pointer-events: none; transition: color 0.2s; }
  .field-wrap:focus-within .field-icon { color: rgba(201,168,76,0.9); }
  .field-input { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(201,168,76,0.18); border-radius: 4px; padding: 12px 14px 12px 42px; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--white); outline: none; transition: border-color 0.22s, background 0.22s, box-shadow 0.22s; }
  .field-input::placeholder { color: rgba(255,255,255,0.2); }
  .field-input:focus { border-color: rgba(201,168,76,0.55); background: rgba(255,255,255,0.06); box-shadow: 0 0 0 3px rgba(201,168,76,0.06); }
  .field-underline { position: absolute; bottom: 0; left: 0; width: 0; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); transition: width 0.35s ease; border-radius: 0 0 4px 4px; }
  .field-input:focus ~ .field-underline { width: 100%; }

  .field-select { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(201,168,76,0.18); border-radius: 4px; padding: 12px 14px 12px 42px; font-family: 'DM Sans', sans-serif; font-size: 13px; color: rgba(255,255,255,0.7); outline: none; transition: border-color 0.22s, background 0.22s, box-shadow 0.22s; appearance: none; cursor: pointer; }
  .field-select option { background: #12294d; color: #fff; }
  .field-select:focus { border-color: rgba(201,168,76,0.55); background: rgba(255,255,255,0.06); box-shadow: 0 0 0 3px rgba(201,168,76,0.06); color: #fff; }
  .field-select-arrow { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: rgba(201,168,76,0.5); pointer-events: none; }

  /* Password strength */
  .ps-wrap { margin-top: 7px; }
  .ps-bars { display: flex; gap: 4px; margin-bottom: 4px; }
  .ps-bar { flex: 1; height: 2px; border-radius: 2px; background: rgba(255,255,255,0.08); transition: background 0.3s; }
  .ps-bar.weak   { background: #ef4444; }
  .ps-bar.fair   { background: #f59e0b; }
  .ps-bar.strong { background: #3FCB6F; }
  .ps-label { font-size: 10px; color: rgba(255,255,255,0.25); letter-spacing: 0.08em; }

  /* Terms */
  .field-terms { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 22px; cursor: pointer; }
  .field-terms input[type="checkbox"] { appearance: none; width: 14px; height: 14px; flex-shrink: 0; border: 1px solid rgba(201,168,76,0.35); border-radius: 2px; background: transparent; cursor: pointer; margin-top: 1px; transition: background 0.18s, border-color 0.18s; position: relative; }
  .field-terms input[type="checkbox"]:checked { background: var(--gold); border-color: var(--gold); }
  .field-terms input[type="checkbox"]:checked::after { content: ''; position: absolute; top: 1px; left: 3.5px; width: 4px; height: 7px; border: 1.5px solid var(--navy); border-top: none; border-left: none; transform: rotate(45deg); }
  .field-terms-label { font-size: 11.5px; color: rgba(255,255,255,0.35); line-height: 1.65; user-select: none; }
  .field-terms-label a { color: var(--gold); text-decoration: none; font-weight: 500; }
  .field-terms-label a:hover { color: var(--gold-light); }

  .btn-submit { width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: var(--navy); font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; padding: 15px 32px; border-radius: 4px; border: none; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 24px rgba(201,168,76,0.28); margin-bottom: 22px; }
  .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(201,168,76,0.45); }
  .btn-submit:active { transform: translateY(0); }

  .auth-or { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
  .auth-or-line { flex: 1; height: 1px; background: rgba(201,168,76,0.12); }
  .auth-or-text { font-size: 10px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255,255,255,0.2); }
  .auth-alt-link { text-align: center; font-size: 12.5px; color: rgba(255,255,255,0.35); }
  .auth-alt-link a { color: var(--gold); text-decoration: none; font-weight: 600; margin-left: 4px; transition: color 0.2s; }
  .auth-alt-link a:hover { color: var(--gold-light); }

  .auth-alert { border-radius: 4px; padding: 12px 16px; font-size: 12.5px; line-height: 1.6; margin-bottom: 18px; }
  .alert-error { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }

  .auth-footer { position: fixed; bottom: 0; left: 0; right: 0; background: #060f1e; border-top: 1px solid rgba(201,168,76,0.1); padding: 10px clamp(20px, 3vw, 48px); display: flex; align-items: center; justify-content: space-between; z-index: 100; }
  .auth-footer p { font-size: 10px; color: rgba(255,255,255,0.2); letter-spacing: 0.06em; }
  .auth-footer strong { color: rgba(201,168,76,0.4); }
  .footer-seal { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.12); }

  @keyframes fadeUp{from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);}}
  .fu{animation:fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) both;}
  .d1{animation-delay:0.04s;}.d2{animation-delay:0.16s;}
  .d3{animation-delay:0.28s;}.d4{animation-delay:0.40s;}
  .d5{animation-delay:0.52s;}

  /* Tablet */
  @media (max-width: 1100px) and (min-width: 769px) {
    .auth-brand { padding: 40px 32px 40px 40px; min-height: auto; }
    .auth-form-panel { padding: 40px 40px 80px 32px; }
  }

  /* Mobile */
  @media (max-width: 768px) {
    .auth-root { position: static; min-height: calc(100svh - 64px); overflow: visible; }
    .auth-layout { grid-template-columns: 1fr; }
    .auth-brand { display: none; }
    .auth-form-panel { padding: 48px 24px 90px; justify-content: flex-start; }
    .auth-wm { display: none; }
    .auth-footer { position: static; }
  }

  @media (max-width: 480px) {
    .auth-form-panel { padding: 36px 16px 90px; }
    .field-row-2 { grid-template-columns: 1fr; }
  }
</style>

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
        Join<br>
        <span class="gold-line">Daet Listens.</span>
      </h2>
      <div class="auth-brand-divider fu d2"></div>
      <p class="auth-brand-desc fu d3">
        Create your account and become part of a community committed to a
        more transparent, accountable, and responsive local government.
      </p>
      <div class="auth-benefits fu d4">
        <div class="auth-benefit">
          <div class="auth-benefit-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
          <div><div class="auth-benefit-title">Track Every Complaint</div><div class="auth-benefit-desc">Real-time updates from submission to resolution.</div></div>
        </div>
        <div class="auth-benefit">
          <div class="auth-benefit-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          <div><div class="auth-benefit-title">Secure &amp; Confidential</div><div class="auth-benefit-desc">Encrypted and protected under RA 6713 and FOI.</div></div>
        </div>
        <div class="auth-benefit">
          <div class="auth-benefit-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <div><div class="auth-benefit-title">Drive Accountability</div><div class="auth-benefit-desc">Hold offices to measurable public standards.</div></div>
        </div>
      </div>
    </div>

    {{-- RIGHT: Form --}}
    <div class="auth-form-panel">
      <div class="auth-form-wrap">

        <div class="auth-form-eyebrow fu d1">Create Account</div>
        <h1 class="auth-form-heading fu d2">Register</h1>
        <p class="auth-form-sub fu d2">
          Already have an account?
          <a href="{{ route('login') }}" wire:navigate>Sign in here</a>
        </p>

        @if ($errors->any())
          <div class="auth-alert alert-error fu">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
          </div>
        @endif

        {{-- <form method="POST" action="{{ route('register') }}"> --}}
        <form method="POST" action="">
          @csrf

          <div class="field-row-2 fu d3">
            <div class="field-group">
              <label class="field-label" for="first_name">First Name</label>
              <div class="field-wrap">
                <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input id="first_name" name="first_name" type="text" class="field-input" placeholder="Juan" value="{{ old('first_name') }}" required>
                <div class="field-underline"></div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label" for="last_name">Last Name</label>
              <div class="field-wrap">
                <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input id="last_name" name="last_name" type="text" class="field-input" placeholder="dela Cruz" value="{{ old('last_name') }}" required>
                <div class="field-underline"></div>
              </div>
            </div>
          </div>

          <div class="field-group fu d3">
            <label class="field-label" for="email">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
              <input id="email" name="email" type="email" class="field-input" placeholder="your@email.com" value="{{ old('email') }}" required>
              <div class="field-underline"></div>
            </div>
          </div>

          <div class="field-group fu d3">
            <label class="field-label" for="phone">Contact Number</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 6.29 6.29l.91-.82a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
              <input id="phone" name="phone" type="tel" class="field-input" placeholder="09XX XXX XXXX" value="{{ old('phone') }}">
              <div class="field-underline"></div>
            </div>
          </div>

          <div class="field-group fu d4">
            <label class="field-label" for="barangay">Barangay</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
              <select id="barangay" name="barangay" class="field-select" >
                  <option value="" disabled selected>Select your barangay</option>
                    <option>Alawihao</option>
                    <option>Awitan</option>
                    <option>Bagasbas</option>
                    <option>Barangay I (Hilahod)</option>
                    <option>Barangay II (Pasig)</option>
                    <option>Barangay III (Iraya)</option>
                    <option>Barangay IV (Mantagbac)</option>
                    <option>Barangay V (Pandan)</option>
                    <option>Barangay VI (Centro)</option>
                    <option>Barangay VII (Diego Liñan)</option>
                    <option>Barangay VIII (Salcedo)</option>
                    <option>Bibirao</option>
                    <option>Borabod</option>
                    <option>Calasgasan</option>
                    <option>Camambugan</option>
                    <option>Cobangbang</option>
                    <option>Dogongan</option>
                    <option>Gahonon</option>
                    <option>Gubat (Moreno, Gubat, Mandulongan)</option>
                    <option>Lag-on</option>
                    <option>Magang</option>
                    <option>Mambalite</option>
                    <option>Mancruz</option>
                    <option>Pamorangon</option>
                    <option>San Isidro</option>
              </select>
              <span class="field-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>
            </div>
          </div>

          <div class="field-group fu d4">
            <label class="field-label" for="password">Password</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input id="password" name="password" type="password" class="field-input" placeholder="Minimum 8 characters" required oninput="checkStrength(this.value)">
              <div class="field-underline"></div>
            </div>
            <div class="ps-wrap" id="ps-wrap" style="display:none">
              <div class="ps-bars"><div class="ps-bar" id="pb1"></div><div class="ps-bar" id="pb2"></div><div class="ps-bar" id="pb3"></div><div class="ps-bar" id="pb4"></div></div>
              <span class="ps-label" id="ps-label">Weak</span>
            </div>
          </div>

          <div class="field-group fu d4">
            <label class="field-label" for="password_confirmation">Confirm Password</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
              <input id="password_confirmation" name="password_confirmation" type="password" class="field-input" placeholder="Re-enter password" required>
              <div class="field-underline"></div>
            </div>
          </div>

          <label class="field-terms fu d4">
            <input type="checkbox" name="terms" required>
            <span class="field-terms-label">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> of the Municipality of Daet Transparency Complaint Portal.</span>
          </label>

          <button type="submit" class="btn-submit fu d5">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Create My Account
          </button>

          <div class="auth-or fu d5">
            <div class="auth-or-line"></div><span class="auth-or-text">or</span><div class="auth-or-line"></div>
          </div>

          <p class="auth-alt-link fu d5">
            Already have an account?
            <a href="{{ route('login') }}">Sign in &rarr;</a>
          </p>
        </form>
      </div>
    </div>

  </div>

  <div class="auth-footer">
    <p><strong>Municipality of Daet</strong> &nbsp;&middot;&nbsp; Camarines Norte &nbsp;&middot;&nbsp; Official Transparency Portal</p>
    <span class="footer-seal">Republic Act 6713 &nbsp;&middot;&nbsp; FOI Compliant</span>
  </div>
</div>

<script>
function checkStrength(val) {
  const wrap = document.getElementById('ps-wrap');
  const label = document.getElementById('ps-label');
  const bars = [1,2,3,4].map(i => document.getElementById('pb'+i));
  if (!val) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';
  let s = 0;
  if (val.length >= 8) s++;
  if (/[A-Z]/.test(val)) s++;
  if (/[0-9]/.test(val)) s++;
  if (/[^A-Za-z0-9]/.test(val)) s++;
  const cls = s <= 1 ? 'weak' : s <= 2 ? 'fair' : 'strong';
  const lbl = s <= 1 ? 'Weak' : s <= 2 ? 'Fair' : s === 3 ? 'Good' : 'Strong';
  bars.forEach((b,i) => { b.className = 'ps-bar'; if(i < s) b.classList.add(cls); });
  label.textContent = lbl;
  label.style.color = s <= 1 ? '#ef4444' : s <= 2 ? '#f59e0b' : '#3FCB6F';
}
</script>

@endsection
