{{--
    pages/home.blade.php
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    ONE view for the home route (GET /).

    - Guests see: the full Daet Listens landing page (hero, process,
      features, CTA banner) — the old guest.blade.php content.

    - Authenticated users see: the same landing page.
      The difference is the NAVBAR (handled by layouts/app.blade.php)
      and the CTA buttons inside the hero (handled by @auth/@guest below).

    If you want auth users to land on /dashboard instead of /, add
    this to your LoginController or RedirectIfAuthenticated middleware:
        protected $redirectTo = RouteServiceProvider::HOME; // set to '/dashboard'
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
--}}
@extends('layouts.app')
@section('title', 'Daet Listens — Official Complaint Portal')

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');

  :root {
    --navy:        #0B1F3A;
    --navy-mid:    #12294d;
    --navy-light:  #1a3560;
    --gold:        #C9A84C;
    --gold-light:  #E2C06A;
    --gold-pale:   rgba(201,168,76,0.12);
    --cream:       #F5F0E8;
    --cream-dark:  #EDE7D9;
    --white:       #ffffff;
    --text-body:   #4B5563;
    --text-muted:  #6B7280;
    --text-dim:    rgba(255,255,255,0.55);
    --border-gold: rgba(201,168,76,0.20);
    --border-navy: rgba(11,31,58,0.08);
  }

  * { box-sizing: border-box; }
  .home-root { font-family: 'DM Sans', sans-serif; width: 100%; overflow-x: hidden; }

  /* ━━━━━━ HERO ━━━━━━ */
  .hero {
    position: relative;
    background: var(--navy);
    min-height: 100svh;
    padding-top: 64px;
    display: flex;
    align-items: center;
    overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px);
    pointer-events: none;
  }
  .hero-glow      { position: absolute; top: -15%; right: -8%; width: 55vw; height: 55vw; max-width: 680px; max-height: 680px; background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%); pointer-events: none; }
  .hero-glow-left { position: absolute; bottom: -20%; left: -10%; width: 40vw; height: 40vw; max-width: 500px; max-height: 500px; background: radial-gradient(ellipse, rgba(26,53,96,0.5) 0%, transparent 70%); pointer-events: none; }
  .hero-bar       { position: absolute; top: 0; left: 0; width: 3px; height: 100%; background: linear-gradient(180deg, var(--gold) 0%, rgba(201,168,76,0.1) 100%); }
  .hero-wm        { position: absolute; bottom: -8%; right: -2%; font-family: 'Cormorant Garamond', serif; font-size: clamp(80px, 22vw, 260px); font-weight: 700; color: rgba(255,255,255,0.02); line-height: 1; pointer-events: none; user-select: none; white-space: nowrap; letter-spacing: -0.02em; }

  .hero-inner {
    position: relative; z-index: 2;
    width: 100%; max-width: 1280px; margin: 0 auto;
    padding: 56px 40px 72px;
    display: grid; grid-template-columns: 55fr 45fr;
    gap: 80px; align-items: center;
  }

  .hero-badge { display: inline-flex; align-items: center; gap: 10px; border: 1px solid var(--border-gold); border-radius: 3px; padding: 7px 16px; font-size: 10px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; color: rgba(201,168,76,0.8); margin-bottom: 28px; background: rgba(201,168,76,0.06); }
  .badge-dot  { width: 5px; height: 5px; border-radius: 50%; background: var(--gold); flex-shrink: 0; animation: dot-pulse 2.4s infinite; }
  @keyframes dot-pulse { 0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.3;transform:scale(0.6);} }

  .hero-title    { font-family: 'Cormorant Garamond', serif; font-size: clamp(38px, 5.5vw, 72px); font-weight: 700; line-height: 1.05; color: var(--white); margin-bottom: 24px; letter-spacing: -0.01em; }
  .hero-title .gold-line { color: var(--gold); font-style: italic; display: block; }
  .hero-divider  { width: 48px; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); margin-bottom: 24px; }
  .hero-desc     { font-size: 15px; line-height: 1.8; color: var(--text-dim); font-weight: 300; max-width: 460px; margin-bottom: 44px; }
  .hero-ctas     { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }

  .btn-primary   { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%); color: var(--navy); font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; padding: 15px 32px; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 24px rgba(201,168,76,0.32); }
  .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(201,168,76,0.48); }
  .btn-secondary { display: inline-flex; align-items: center; gap: 10px; background: transparent; color: rgba(255,255,255,0.7); font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 500; letter-spacing: 0.07em; text-transform: uppercase; padding: 15px 28px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.15); text-decoration: none; transition: border-color 0.2s, color 0.2s, transform 0.2s; }
  .btn-secondary:hover { border-color: rgba(255,255,255,0.38); color: #fff; transform: translateY(-2px); }

  /* Stats Panel */
  .stats-panel { display: flex; flex-direction: column; gap: 1px; border: 1px solid var(--border-gold); border-radius: 6px; overflow: hidden; background: var(--border-gold); }
  .stats-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; }
  .stat-cell   { background: rgba(9,25,48,0.94); padding: 26px 22px; transition: background 0.2s; position: relative; }
  .stat-cell::before { content: ''; position: absolute; top: 0; left: 0; width: 2px; height: 0; background: var(--gold); transition: height 0.35s ease; }
  .stat-cell:hover { background: rgba(18,41,77,0.96); }
  .stat-cell:hover::before { height: 100%; }
  .stat-num    { font-family: 'Cormorant Garamond', serif; font-size: clamp(30px, 3.5vw, 46px); font-weight: 700; color: var(--gold); line-height: 1; margin-bottom: 5px; letter-spacing: -0.01em; }
  .stat-lbl    { font-size: 10px; font-weight: 600; letter-spacing: 0.13em; text-transform: uppercase; color: rgba(255,255,255,0.38); }
  .stats-footer { background: rgba(6,17,35,0.97); padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .stats-footer-text { font-size: 10.5px; letter-spacing: 0.07em; color: rgba(255,255,255,0.28); text-transform: uppercase; }
  .stats-footer-text strong { color: rgba(201,168,76,0.5); }
  .live-indicator { display: flex; align-items: center; gap: 6px; font-size: 9.5px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.25); }
  .live-dot { width: 5px; height: 5px; border-radius: 50%; background: #3FCB6F; box-shadow: 0 0 0 0 rgba(63,203,111,0.5); animation: live-pulse 2s infinite; }
  @keyframes live-pulse { 0%{box-shadow:0 0 0 0 rgba(63,203,111,0.5);}70%{box-shadow:0 0 0 6px rgba(63,203,111,0);}100%{box-shadow:0 0 0 0 rgba(63,203,111,0);} }

  /* ━━━━━━ SECTION COMMONS ━━━━━━ */
  .section-wrap    { max-width: 1280px; margin: 0 auto; }
  .section-eyebrow { display: inline-flex; align-items: center; gap: 12px; font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: var(--gold); margin-bottom: 14px; }
  .section-eyebrow::before { content: ''; width: 24px; height: 1px; background: var(--gold); }
  .section-heading { font-family: 'Cormorant Garamond', serif; font-size: clamp(28px, 3.5vw, 46px); font-weight: 700; color: var(--navy); line-height: 1.12; max-width: 540px; margin-bottom: 14px; letter-spacing: -0.01em; }
  .section-sub     { font-size: 14.5px; color: var(--text-muted); max-width: 500px; line-height: 1.75; margin-bottom: 56px; font-weight: 300; }

  /* ━━━━━━ PROCESS ━━━━━━ */
  .process { background: var(--cream); padding: 104px 40px; position: relative; }
  .process::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--navy) 0%, var(--gold) 50%, transparent 100%); }
  .steps { display: grid; grid-template-columns: repeat(4,1fr); position: relative; }
  .steps::after { content: ''; position: absolute; top: 33px; left: 12.5%; width: 75%; height: 1px; background: linear-gradient(90deg, transparent, rgba(201,168,76,0.3) 10%, var(--gold) 50%, rgba(201,168,76,0.3) 90%, transparent); }
  .step      { position: relative; z-index: 1; padding: 0 20px; text-align: center; }
  .step-num  { width: 66px; height: 66px; border-radius: 50%; background: var(--navy); border: 1.5px solid var(--gold); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-family: 'Cormorant Garamond', serif; font-size: 22px; font-weight: 700; color: var(--gold); letter-spacing: -0.01em; transition: transform 0.28s, box-shadow 0.28s, background 0.28s; }
  .step:hover .step-num { transform: scale(1.1); background: var(--gold); color: var(--navy); box-shadow: 0 0 0 10px rgba(201,168,76,0.08), 0 8px 24px rgba(201,168,76,0.2); }
  .step-icon  { width: 36px; height: 36px; margin: 0 auto 14px; color: var(--gold); opacity: 0.7; transition: opacity 0.2s; }
  .step:hover .step-icon { opacity: 1; }
  .step-title { font-family: 'Cormorant Garamond', serif; font-size: 17px; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
  .step-desc  { font-size: 12.5px; color: var(--text-muted); line-height: 1.7; }

  /* ━━━━━━ FEATURES ━━━━━━ */
  .features { background: var(--cream-dark); padding: 104px 40px; position: relative; }
  .features::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: rgba(201,168,76,0.18); }
  .features-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: rgba(201,168,76,0.12); border: 1px solid rgba(201,168,76,0.12); border-radius: 8px; overflow: hidden; }
  .feature-card { background: var(--white); padding: 36px 30px; transition: background 0.22s; position: relative; }
  .feature-card::after { content: ''; position: absolute; bottom: 0; left: 30px; right: 30px; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); transform: scaleX(0); transform-origin: left; transition: transform 0.35s ease; }
  .feature-card:hover { background: #fdfaf4; }
  .feature-card:hover::after { transform: scaleX(1); }
  .feature-icon-wrap { width: 44px; height: 44px; border: 1px solid var(--border-gold); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: var(--gold); background: var(--gold-pale); transition: background 0.2s, border-color 0.2s; }
  .feature-card:hover .feature-icon-wrap { background: rgba(201,168,76,0.18); border-color: rgba(201,168,76,0.4); }
  .feature-title { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
  .feature-desc  { font-size: 13px; color: var(--text-muted); line-height: 1.7; font-weight: 300; }

  /* ━━━━━━ CTA BANNER ━━━━━━ */
  .cta-banner { background: var(--navy); padding: 80px 40px; position: relative; overflow: hidden; }
  .cta-banner::before { content: ''; position: absolute; inset: 0; background: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px); }
  .cta-inner  { position: relative; z-index: 1; max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 40px; flex-wrap: wrap; }
  .cta-label  { font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: var(--gold); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
  .cta-label::before { content: ''; width: 20px; height: 1px; background: var(--gold); }
  .cta-text h3 { font-family: 'Cormorant Garamond', serif; font-size: clamp(24px, 3.2vw, 38px); font-weight: 700; color: var(--white); line-height: 1.1; margin-bottom: 10px; }
  .cta-text p  { font-size: 14px; color: rgba(255,255,255,0.46); max-width: 460px; line-height: 1.7; font-weight: 300; }

  /* ━━━━━━ FOOTER STRIP ━━━━━━ */
  .home-footer { background: #060f1e; border-top: 1px solid rgba(201,168,76,0.12); padding: 22px 40px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
  .home-footer p { font-size: 11px; color: rgba(255,255,255,0.22); letter-spacing: 0.06em; }
  .home-footer strong { color: rgba(201,168,76,0.45); }
  .footer-seal { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.15); }

  /* ━━━━━━ ANIMATIONS ━━━━━━ */
  @keyframes fadeUp { from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);} }
  .fu{animation:fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) both;}
  .d1{animation-delay:0.04s;}.d2{animation-delay:0.16s;}.d3{animation-delay:0.28s;}.d4{animation-delay:0.40s;}.d5{animation-delay:0.52s;}

  /* ━━━━━━ RESPONSIVE ━━━━━━ */
  @media(max-width:1100px){
    .hero-inner{grid-template-columns:1fr;gap:48px;padding:48px 32px 64px;}
    .hero-desc{max-width:100%;}.stats-panel{max-width:520px;}
    .process,.features{padding:80px 32px;}
    .steps{grid-template-columns:1fr 1fr;gap:48px;}.steps::after{display:none;}
    .features-grid{grid-template-columns:1fr 1fr;}
    .cta-banner{padding:64px 32px;}
  }
  @media(max-width:768px){
    .hero{min-height:auto;}.hero-inner{padding:40px 20px 56px;gap:40px;}
    .hero-ctas{flex-direction:column;}.btn-primary,.btn-secondary{justify-content:center;width:100%;}
    .process,.features{padding:64px 20px;}
    .features-grid{grid-template-columns:1fr;border-radius:6px;}
    .cta-banner{padding:56px 20px;}.cta-inner{flex-direction:column;}
    .cta-inner .btn-primary{width:100%;max-width:360px;justify-content:center;}
    .cta-text p{max-width:100%;}
    .home-footer{flex-direction:column;text-align:center;padding:20px;}
  }
  @media(max-width:540px){
    .steps{grid-template-columns:1fr;gap:36px;}.hero-title{font-size:36px;}
    .stats-row{grid-template-columns:1fr 1fr;}.stat-cell{padding:20px 16px;}.stat-num{font-size:28px;}
  }
</style>

<div class="home-root">

  {{-- ══════════════════ HERO ══════════════════ --}}
  <section class="hero">
    <div class="hero-glow"></div>
    <div class="hero-glow-left"></div>
    <div class="hero-bar"></div>
    <div class="hero-wm">DAET</div>

    <div class="hero-inner">

      {{-- LEFT: Headline + CTAs --}}
      <div>
        <div class="hero-badge fu d1">
          <span class="badge-dot"></span>
          Official Complaint Portal &nbsp;·&nbsp; LGU Daet, Camarines Norte
        </div>

        <h1 class="hero-title fu d2">
          Your Voice.<br>
          <span class="gold-line">Our Accountability.</span>
        </h1>

        <div class="hero-divider fu d3"></div>

        <p class="hero-desc fu d3">
          A direct, secure, and trackable channel for every Daet resident
          to raise concerns with the local government. Because genuine
          transparency begins with an open door.
        </p>

        <div class="hero-ctas fu d4">
          {{--
            CTA BUTTONS:
            - Logged-in users see "File a Complaint" and "Track My Complaint"
            - Guests see "Get Started" (→ register) and "Login to Track"
            Both states share the same visual style — only the text and route differ.
          --}}
          @auth
            <a href="{{ route('complaints.create') }}" wire:navigate class="btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              File a Complaint
            </a>
            <a href="{{ route('complaints.track') }}" wire:navigate class="btn-secondary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Track My Complaint
            </a>
          @else
            <a href="{{ route('register') }}" wire:navigate class="btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              Get Started
            </a>
            <a href="{{ route('login') }}" wire:navigate class="btn-secondary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
              Login to Track
            </a>
          @endauth
        </div>
      </div>

      {{-- RIGHT: Stats Panel --}}
      <div class="fu d5">
        <div class="stats-panel">
          <div class="stats-row">
            <div class="stat-cell">
              <div class="stat-num">{{ $totalComplaints ?? '0' }}</div>
              <div class="stat-lbl">Total Filed</div>
            </div>
            <div class="stat-cell">
              <div class="stat-num">{{ $resolvedComplaints ?? '0' }}</div>
              <div class="stat-lbl">Resolved</div>
            </div>
          </div>
          <div class="stats-row">
            <div class="stat-cell">
              <div class="stat-num">{{ $pendingComplaints ?? '0' }}</div>
              <div class="stat-lbl">In Progress</div>
            </div>
            <div class="stat-cell">
              <div class="stat-num">{{ $avgDays ?? '—' }}</div>
              <div class="stat-lbl">Avg. Days</div>
            </div>
          </div>
          <div class="stats-footer">
            <span class="stats-footer-text">FY <strong>{{ date('Y') }}</strong></span>
            <div class="live-indicator">
              <span class="live-dot"></span>
              Live data
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  {{-- ══════════════════ PROCESS ══════════════════ --}}
  <section class="process" id="process">
    <div class="section-wrap">
      <div class="section-eyebrow">How It Works</div>
      <h2 class="section-heading">Four Steps from Complaint to Resolution</h2>
      <p class="section-sub">
        Filing a complaint is straightforward and transparent. Our process is designed
        to keep you informed and ensure every concern receives proper attention.
      </p>

      <div class="steps">
        <div class="step">
          <div class="step-num">01</div>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          <div class="step-title">Submit Your Complaint</div>
          <p class="step-desc">Complete the form with your concern, the relevant department, and any supporting documents.</p>
        </div>
        <div class="step">
          <div class="step-num">02</div>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          <div class="step-title">Receive a Reference</div>
          <p class="step-desc">A unique tracking number is issued instantly so you can monitor your complaint's status at any time.</p>
        </div>
        <div class="step">
          <div class="step-num">03</div>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49M7.76 7.76a6 6 0 0 0 0 8.49"/></svg>
          <div class="step-title">LGU Reviews &amp; Acts</div>
          <p class="step-desc">The appropriate office investigates and takes the necessary corrective action within the mandated timeframe.</p>
        </div>
        <div class="step">
          <div class="step-num">04</div>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <div class="step-title">Resolution &amp; Feedback</div>
          <p class="step-desc">You are notified once your complaint is resolved. Your feedback helps us improve public service delivery.</p>
        </div>
      </div>
    </div>
  </section>

  {{-- ══════════════════ FEATURES ══════════════════ --}}
  <section class="features" id="features">
    <div class="section-wrap">
      <div class="section-eyebrow">Why This System</div>
      <h2 class="section-heading">Built for Every Resident of Daet</h2>
      <p class="section-sub">
        Accessible, secure, and built to the highest standards of public accountability —
        regardless of your age or technical experience.
      </p>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon-wrap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          <div class="feature-title">Secure &amp; Confidential</div>
          <p class="feature-desc">Your identity and complaint details are fully protected. All submissions are encrypted and handled with strict confidentiality.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-wrap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <div class="feature-title">Real-Time Tracking</div>
          <p class="feature-desc">Know exactly where your complaint stands — from submission to investigation to final resolution — updated live.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-wrap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
          <div class="feature-title">Routed to the Right Office</div>
          <p class="feature-desc">Complaints are automatically directed to the correct LGU department, eliminating delays and miscommunication.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-wrap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <div class="feature-title">Public Accountability</div>
          <p class="feature-desc">Resolution statistics are published publicly, holding each office accountable to transparent performance benchmarks.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-wrap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></div>
          <div class="feature-title">Any Device, Anywhere</div>
          <p class="feature-desc">File and track complaints from your phone, tablet, or desktop — no app installation required.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-wrap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
          <div class="feature-title">RA 6713 Compliant</div>
          <p class="feature-desc">Fully aligned with the Code of Conduct and Ethical Standards for Public Officials and Employees.</p>
        </div>
      </div>
    </div>
  </section>

  {{-- ══════════════════ CTA BANNER ══════════════════ --}}
  <div class="cta-banner">
    <div class="cta-inner">
      <div class="cta-text">
        <div class="cta-label">Take Action</div>
        <h3>Ready to file a complaint?</h3>
        <p>Join the growing community of Daet residents holding their local government to a higher standard — one verified report at a time.</p>
      </div>
      @auth
        <a href="{{ route('complaints.create') }}" wire:navigate class="btn-primary" style="flex-shrink:0;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          File a Complaint Now
        </a>
      @else
        <a href="{{ route('register') }}" wire:navigate class="btn-primary" style="flex-shrink:0;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          Get Started Free
        </a>
      @endauth
    </div>
  </div>

  {{-- ══════════════════ FOOTER ══════════════════ --}}
  <div class="home-footer">
    <p><strong>Municipality of Daet</strong> &nbsp;·&nbsp; Camarines Norte &nbsp;·&nbsp; Official Transparency Complaint Portal</p>
    <span class="footer-seal">Republic Act 6713 &nbsp;·&nbsp; FOI Compliant</span>
  </div>

</div>
@endsection
