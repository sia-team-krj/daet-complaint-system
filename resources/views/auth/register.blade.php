@extends('layouts.guest')
@section('title', __('auth_ui.titles.register'))
@section('content')

<style>

  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');

  :root {
    --navy:        #142D4F;
    --navy-mid:    #1B3A61;
    --gold:        #C9A84C;
    --gold-light:  #E2C06A;
    --gold-pale:   rgba(201,168,76,0.12);
    --white:       #ffffff;
    --text-dim:    rgba(255,255,255,0.55);
    --border-gold: rgba(201,168,76,0.20);
  }

  .auth-root, .auth-root * { box-sizing: border-box; }

  /*
   * Register scrolls the same way the home page does: a static, auto-height
   * wrapper and normal document scrolling. It used to be a `position: fixed`
   * panel with its own scrollbar on desktop that switched to document
   * scrolling on mobile, and that split is what made the form unreachable on a
   * phone. One strategy at every width cannot get trapped.
   *
   * The navbar is position:fixed at every width, hence the top padding.
   */
  .auth-root {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    font-family: 'DM Sans', sans-serif;
    position: relative;
    background: var(--navy);
    overflow: visible;
    padding-top: calc(var(--navbar-height) + 8px);
    /* Stops the viewport jumping when the barangay suggestion list is inserted
       above the field. Still needed now that the document scrolls. */
    overflow-anchor: none;
  }

  /* Clips only the decorative glows/watermark. The glows are wider than the
     viewport by design (right: -8%), so without this they extend the document
     sideways and expose a white strip beside the full-bleed form panel. */
  .auth-decorations {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
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
  .auth-wm {
    position: absolute; bottom: -4%; right: -1%;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 18vw, 280px); font-weight: 700;
    color: rgba(255,255,255,0.018); line-height: 1;
    pointer-events: none; user-select: none; letter-spacing: -0.02em;
  }

  /* Two-column grid - fills the first screen, then grows with the form */
  .auth-layout {
    position: relative; z-index: 2;
    width: 100%;
    min-height: calc(100svh - var(--navbar-height) - 8px);
    display: grid; grid-template-columns: 1fr 1fr;
  }

  /* ─── Brand panel (LEFT) ─── */
  .auth-brand {
    display: flex; flex-direction: column; justify-content: center; align-items:center;
    padding: clamp(40px, 5vh, 80px) clamp(32px, 4vw, 64px) clamp(40px, 5vh, 80px) clamp(40px, 5vw, 80px);
    position: relative;
    /* Sticks below the fixed navbar while the form scrolls past it. */
    position: sticky; top: calc(var(--navbar-height) + 8px); align-self: start;
    min-height: calc(100svh - var(--navbar-height) - 8px);
  }

  .auth-badge {
    display: inline-flex; align-items: center; gap: 10px;
    border: 1px solid var(--border-gold); border-radius: 3px;
    padding: 7px 16px; margin-bottom: clamp(20px, 3vh, 36px);
    font-size: 10px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase;
    color: rgba(201,168,76,0.8); background: rgba(201,168,76,0.06); width: fit-content;
  }

  .auth-brand-title {
    text-align:center;
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
    justify-content: flex-start; align-items: center;
    width: 100%;
    min-width: 0;
    padding: clamp(40px, 5vh, 72px) clamp(40px, 5vw, 80px) clamp(48px, 6vh, 80px) clamp(32px, 4vw, 64px);
  }

  .auth-form-wrap {
    width: 100%;
    max-width: 440px;
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
  .field-label { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(255,255,255,0.55); margin-bottom: 7px; }
  .field-label .required { color: var(--gold); margin-left: 3px; font-weight: 700; }
  .field-wrap { position: relative; }
  .field-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(201,168,76,0.85); pointer-events: none; transition: color 0.2s; }
  .field-wrap:focus-within .field-icon { color: var(--gold-light); }
  .field-input {
    width: 100%;
    background: #1F3D60;
    border: 1px solid rgba(255,255,255,0.22);
    border-radius: 4px;
    padding: 12px 14px 12px 42px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: #ffffff;
    caret-color: #ffffff;
    outline: none;
    transition: border-color 0.22s, background 0.22s, box-shadow 0.22s;
  }
  .field-input::placeholder { color: rgba(255,255,255,0.55); opacity: 1; }
  .field-input:hover { border-color: rgba(255,255,255,0.34); }
  .field-input:focus {
    border-color: #D4A947;
    outline: none;
    box-shadow: 0 0 0 3px rgba(212,169,71,0.2);
  }
  .field-underline { position: absolute; bottom: 0; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); transform: scaleX(0); transform-origin: left; transition: transform 0.35s ease; border-radius: 0 0 4px 4px; }
  .field-input:focus ~ .field-underline { transform: scaleX(1); }

  /* Terms */
  .field-terms { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 22px; cursor: pointer; }
  .field-terms input[type="checkbox"] { appearance: none; width: 14px; height: 14px; flex-shrink: 0; border: 1px solid rgba(201,168,76,0.35); border-radius: 2px; background: transparent; cursor: pointer; margin-top: 1px; transition: background 0.18s, border-color 0.18s; position: relative; }
  .field-terms input[type="checkbox"]:checked { background: var(--gold); border-color: var(--gold); }
  .field-terms input[type="checkbox"]:checked::after { content: ''; position: absolute; top: 1px; left: 3.5px; width: 4px; height: 7px; border: 1.5px solid var(--navy); border-top: none; border-left: none; transform: rotate(45deg); }
  .field-terms-label { font-size: 11.5px; color: rgba(255,255,255,0.35); line-height: 1.65; }
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

  /* Static, not fixed: a fixed footer would sit over the bottom of the form
     and read as "there is more below but you cannot reach it". */

  @keyframes fadeUp{from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);}}
  .fu{animation:fadeUp 0.7s cubic-bezier(.22,.68,0,1) both;}
  .d1{animation-delay:0.04s;}.d2{animation-delay:0.16s;}
  .d3{animation-delay:0.28s;}.d4{animation-delay:0.40s;}
  .d5{animation-delay:0.52s;}

  /*
   * The barangay suggestion list is an absolutely-positioned popup that
   * overflows its field-group. Every .fu group keeps its fadeUp transform
   * (fill-mode: both), and a retained transform - even translateY(0) - makes
   * each group its own stacking context. Stacking contexts at the same level
   * paint in DOM order, so the password group painted OVER this group's
   * entire popup: only the top sliver of the first suggestion (the part
   * overlapping this group's status line) received clicks and taps, while
   * every other suggestion sat behind the password field's box. Elevating
   * this one group above its siblings restores pointer access to the whole
   * list. The list drops downward, and the whole form lives inside
   * .auth-layout's z-index: 2 context under the fixed navbar's z-index: 50,
   * so this cannot cover the navbar.
   */
  .field-group.has-suggestions {
    position: relative;
    z-index: 30;
  }

  /* Tablet */
  @media (max-width: 1100px) and (min-width: 769px) {
    .auth-brand { padding: 40px 32px 40px 40px; min-height: auto; }
    .auth-form-panel { padding: 40px 40px 80px 32px; }
  }

  /* Mobile */
  @media (max-width: 768px) {
    /* Only the layout changes here. Scrolling is not re-specified at any
       breakpoint: .auth-root scrolls the document at every width, exactly like
       .home-root on the landing page. A width-dependent scroll strategy is what
       broke this form on phones in the first place. */
    .auth-layout { grid-template-columns: 1fr; min-height: 0; }
    .auth-brand { display: none; }
    .auth-form-panel { padding: 48px 20px 90px; justify-content: flex-start; }
    .auth-wm { display: none; }
  }

  @media (max-width: 480px) {
    .auth-form-panel { padding: 36px 20px 90px; }
    .field-row-2 { grid-template-columns: 1fr; }
  }
</style>

<div class="auth-root">
  <div class="auth-decorations">
    <div class="auth-bg"></div>
    <div class="auth-glow-tr"></div>
    <div class="auth-glow-bl"></div>
    <div class="auth-wm">DAET</div>
  </div>

  <div class="auth-layout">

    {{-- LEFT: Brand --}}
    <div class="auth-brand">
      <div class="auth-badge fu d1">
        {{ __('auth_ui.common.official_portal') }}
      </div>
      <h2 class="auth-brand-title fu d2">
        {{ __('auth_ui.register.join') }}<br>
        <span class="gold-line">{{ __('auth_ui.register.daet_listens') }}</span>
      </h2>
      <div class="auth-brand-divider fu d2"></div>
      <p class="auth-brand-desc fu d3">
        {{ __('auth_ui.register.description') }}
      </p>
      <div class="auth-benefits fu d4">
        <div class="auth-benefit">
          <div class="auth-benefit-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
          <div><div class="auth-benefit-title">{{ __('auth_ui.register.benefit_tracking_title') }}</div><div class="auth-benefit-desc">{{ __('auth_ui.register.benefit_tracking_desc') }}</div></div>
        </div>
        <div class="auth-benefit">
          <div class="auth-benefit-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          <div><div class="auth-benefit-title">{{ __('auth_ui.register.benefit_secure_title') }}</div><div class="auth-benefit-desc">{{ __('auth_ui.register.benefit_secure_desc') }}</div></div>
        </div>
        <div class="auth-benefit">
          <div class="auth-benefit-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <div><div class="auth-benefit-title">{{ __('auth_ui.register.benefit_accountability_title') }}</div><div class="auth-benefit-desc">{{ __('auth_ui.register.benefit_accountability_desc') }}</div></div>
        </div>
      </div>
    </div>

    {{-- RIGHT: Form --}}
    <div class="auth-form-panel">
      <div class="auth-form-wrap">

        <div class="auth-form-eyebrow fu d1">{{ __('auth_ui.register.eyebrow') }}</div>
        <h1 class="auth-form-heading fu d2">{{ __('auth_ui.register.heading') }}</h1>
        <p class="auth-form-sub fu d2">
          {{ __('auth_ui.register.already_have') }}
          <a href="{{ route('login') }}" wire:navigate>{{ __('auth_ui.register.sign_in_here') }}</a>
        </p>

        @if ($errors->any())
          <div class="auth-alert alert-error fu">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
          </div>
        @endif

        <form data-loading-submit method="POST" action="{{ route('register') }}">
          @csrf

          <div class="field-row-2 fu d3">
            <div class="field-group">
              <label class="field-label" for="first_name">{{ __('auth_ui.register.first_name') }} <span class="required">*</span></label>
              <div class="field-wrap">
                <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input id="first_name" name="first_name" type="text" class="field-input" placeholder="{{ __('auth_ui.register.first_name_placeholder') }}" value="{{ old('first_name') }}" required>
                <div class="field-underline"></div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label" for="last_name">{{ __('auth_ui.register.last_name') }} <span class="required">*</span></label>
              <div class="field-wrap">
                <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <input id="last_name" name="last_name" type="text" class="field-input" placeholder="{{ __('auth_ui.register.last_name_placeholder') }}" value="{{ old('last_name') }}" required>
                <div class="field-underline"></div>
              </div>
            </div>
          </div>

          <div class="field-group fu d3">
            <label class="field-label" for="email">{{ __('auth_ui.common.email') }} <span class="required">*</span></label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
              <input id="email" name="email" type="email" class="field-input" placeholder="{{ __('auth_ui.common.email_placeholder') }}" value="{{ old('email') }}" required>
              <div class="field-underline"></div>
            </div>
          </div>

          <div class="field-group fu d3">
            <label class="field-label" for="phone">{{ __('auth_ui.register.contact_number') }}</label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 6.29 6.29l.91-.82a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
              <input id="phone" name="phone" type="tel" class="field-input" placeholder="{{ __('auth_ui.register.phone_placeholder') }}" value="{{ old('phone') }}">
              <div class="field-underline"></div>
            </div>
          </div>

          <div class="field-group has-suggestions fu d4">
            <label class="field-label" for="barangay">{{ __('auth_ui.register.barangay') }}</label>
            <div class="field-wrap" data-barangay-search data-suggestions="barangay-suggestions" data-status="barangay-status">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
              <input
                id="barangay"
                name="barangay"
                type="search"
                class="field-input"
                placeholder="{{ __('auth_ui.register.barangay_placeholder') }}"
                value="{{ old('barangay') }}"
                role="combobox"
                aria-autocomplete="list"
                aria-haspopup="listbox"
                aria-expanded="false"
                aria-controls="barangay-suggestions"
                aria-describedby="barangay-status"
                autocomplete="off"
                spellcheck="false"
                data-barangay-input
              >
              <div class="field-underline"></div>
              <ul id="barangay-suggestions" class="barangay-suggestions" role="listbox" aria-label="{{ __('auth_ui.register.barangay_suggestions') }}" hidden>
                @foreach (App\Models\User::BARANGAYS as $barangay)
                  <li
                    id="barangay-option-{{ $loop->index }}"
                    class="barangay-suggestion"
                    role="option"
                    aria-selected="false"
                    data-search-value="{{ $barangay }}"
                  >{{ $barangay }}</li>
                @endforeach
              </ul>
            </div>
            <span id="barangay-status" class="barangay-search-status" aria-live="polite">{{ __('auth_ui.register.barangay_status') }}</span>
          </div>

          <div class="field-group fu d4">
            <label class="field-label" for="password">{{ __('auth_ui.common.password') }} <span class="required">*</span></label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input id="password" name="password" type="password" class="field-input" placeholder="{{ __('auth_ui.register.password_placeholder') }}" required autocomplete="new-password">
              <div class="field-underline"></div>
            </div>
            @include('auth.partials.password-requirements', ['passwordTarget' => 'password'])
          </div>

          <div class="field-group fu d4">
            <label class="field-label" for="password_confirmation">{{ __('auth_ui.common.confirm_password') }} <span class="required">*</span></label>
            <div class="field-wrap">
              <span class="field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
              <input id="password_confirmation" name="password_confirmation" type="password" class="field-input" placeholder="{{ __('auth_ui.register.confirm_password_placeholder') }}" required autocomplete="new-password">
              <div class="field-underline"></div>
            </div>
          </div>

          <label class="field-terms fu d4">
            {{--
                target="_blank" so reading the policy does not discard the form the
                resident has already filled in. rel="noopener" because the target is
                a new browsing context.
            --}}
            <input type="checkbox" name="terms" required>
            <span class="field-terms-label">{{ __('auth_ui.register.terms_prefix') }} <span class="required">*</span> <a href="{{ route('terms') }}" target="_blank" rel="noopener">{{ __('auth_ui.register.terms') }}</a> {{ __('auth_ui.register.and') }} <a href="{{ route('privacy') }}" target="_blank" rel="noopener">{{ __('auth_ui.register.privacy') }}</a> {{ __('auth_ui.register.terms_suffix') }}</span>
          </label>

          <button type="submit" class="btn-submit fu d5">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            {{ __('auth_ui.common.create_my_account') }}
          </button>

          <div class="auth-or fu d5">
            <div class="auth-or-line"></div><span class="auth-or-text">{{ __('auth_ui.common.or') }}</span><div class="auth-or-line"></div>
          </div>

          <p class="auth-alt-link fu d5">
            {{ __('auth_ui.register.already_have') }}
            <a href="{{ route('login') }}">{{ __('auth_ui.register.sign_in_link') }}</a>
          </p>
        </form>
      </div>
    </div>

  </div>

  {{-- Shared footer: carries the Terms and Conditions and the Privacy Policy. --}}
  @include('layouts.partials.site-footer')
</div>

@endsection

{{-- This page renders the shared footer inside its own full-height wrapper, so
     the layout must not add a second one. See layouts/app.blade.php. --}}
@section('site-footer')
    {{-- Ownership marker only. The footer is rendered above, inside this page's
         own layout wrapper. This section exists purely to tell the shell layout
         not to add a second one, and it must contain something: `@hasSection`
         compiles to a non-empty content check, so an empty section is ignored. --}}
    <span hidden aria-hidden="true"></span>
@endsection