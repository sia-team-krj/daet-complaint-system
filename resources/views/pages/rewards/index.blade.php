@extends($mainLayout)

@section('content')
<div class="home-root" style="font-family: 'DM Sans', sans-serif;">
    <section class="hero min-h-[75svh]">
        <div class="hero-bar"></div>
        <div class="hero-glow"></div>
        <div class="hero-wm" style="font-family: 'Cormorant Garamond', serif;">MERIT</div>

        <div class="hero-inner !grid-cols-1 !text-center">
            <div class="fu d1">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    Citizen Engagement Program
                </div>

                <h1 class="hero-title" style="font-family: 'Cormorant Garamond', serif;">
                    Civic Merit <span class="gold-line" style="font-family: 'Cormorant Garamond', serif; font-style: italic;">& Recognition</span>
                </h1>

                <div class="hero-divider mx-auto"></div>

                <p class="hero-desc mx-auto !max-w-3xl !text-[#ffffff]/80">
                    Engagement is the heart of smart governance. By reporting <span class="text-[var(--gold)] font-bold">damaged roads, poor drainage, or utility failures</span>, you help build a better community. Validated contributions earn Merit Points redeemable through our local partners.
                </p>

                {{-- Feature Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-[1px] bg-[var(--border-gold)] border border-[var(--border-gold)] rounded-sm overflow-hidden mt-12 fu d3">

                    {{-- Card 1: Earn Points --}}
                    <div class="group relative bg-[var(--navy-mid)] p-10 text-left transition-all duration-500 hover:bg-[var(--navy-light)]">
                        <div class="absolute left-0 top-0 w-[2px] h-full bg-gradient-to-b from-[var(--gold)] to-transparent transform scale-y-0 group-hover:scale-y-100 transition-transform origin-top duration-500 ease-out"></div>
                        <div class="text-[var(--gold)] mb-4"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                        <h3 class="text-xl text-white mb-2" style="font-family: 'Cormorant Garamond', serif;">Earn Points</h3>
                        <p class="text-[var(--text-dim)] text-sm leading-relaxed">Submit reports with geotagged proof to accumulate civic merit.</p>
                    </div>

                    {{-- Card 2: Local Incentives --}}
                    <div class="group relative bg-[var(--navy-mid)] p-10 text-left transition-all duration-500 hover:bg-[var(--navy-light)]">
                        <div class="absolute left-0 top-0 w-[2px] h-full bg-gradient-to-b from-[var(--gold)] to-transparent transform scale-y-0 group-hover:scale-y-100 transition-transform origin-top duration-500 ease-out"></div>
                        <div class="text-[var(--gold)] mb-4"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
                        <h3 class="text-xl text-white mb-2" style="font-family: 'Cormorant Garamond', serif;">Local Partners</h3>
                        <p class="text-[var(--text-dim)] text-sm leading-relaxed">Redeem your points at participating businesses in Camarines Norte.</p>
                    </div>

                    {{-- Card 3: Track Status --}}
                    <div class="group relative bg-[var(--navy-mid)] p-10 text-left transition-all duration-500 hover:bg-[var(--navy-light)]">
                        <div class="absolute left-0 top-0 w-[2px] h-full bg-gradient-to-b from-[var(--gold)] to-transparent transform scale-y-0 group-hover:scale-y-100 transition-transform origin-top duration-500 ease-out"></div>
                        <div class="text-[var(--gold)] mb-4"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                        <h3 class="text-xl text-white mb-2" style="font-family: 'Cormorant Garamond', serif;">Track Status</h3>
                        <p class="text-[var(--text-dim)] text-sm leading-relaxed">Monitor your merit points and redemption history via your dashboard.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Roadmap/Notice Section --}}
    <section class="process !bg-[var(--cream)] py-20">
        <div class="max-w-5xl mx-auto px-10">
            <div class="bg-white border border-[var(--border-navy)] p-16 text-center shadow-xl relative overflow-hidden fu d5 group">
                <div class="absolute left-0 top-0 w-[4px] h-full bg-gradient-to-b from-[var(--gold)] to-transparent transform scale-y-0 transition-transform origin-top duration-700"></div>

                <div class="text-[var(--gold)] text-[10px] font-bold tracking-[0.2em] uppercase mb-6" style="font-family: 'DM Sans', sans-serif;">Implementation Roadmap</div>
                <h2 class="text-4xl text-[var(--navy)] mb-6" style="font-family: 'Cormorant Garamond', serif;">Program Development Notice</h2>
                <p class="text-[var(--navy)]/70 max-w-2xl mx-auto mb-10 text-lg">
                    The Citizen Rewards framework is currently in the <span class="font-bold">Legislative Planning Phase</span> to ensure sustainable integration with local business partners.
                </p>

                <div class="flex flex-col md:flex-row items-center justify-center gap-12 pt-8 border-t border-[var(--border-navy)]/10">
                    <div class="flex items-center gap-3">
                        <svg class="text-[var(--gold)]" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        <span class="text-xs font-bold uppercase tracking-widest text-[var(--navy)]">Policy Drafting</span>
                    </div>
                    <div class="flex items-center gap-3 opacity-40">
                        <svg class="text-[var(--navy)]" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
                        <span class="text-xs font-bold uppercase tracking-widest text-[var(--navy)]">Partner Integration</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="home-footer" style="font-family: 'DM Sans', sans-serif;">
        <p>© 2026 <strong>LGU Daet</strong> · Citizen Engagement Portal</p>
    </footer>
</div>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600&display=swap');

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
    --border-gold: rgba(201,168,76,0.2);
    --border-navy: rgba(11,31,58,0.08);
  }

  * { box-sizing: border-box; }
  .home-root { font-family: 'DM Sans', sans-serif; width: 100%; overflow-x: hidden; }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     HERO
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  .hero {
    position: relative;
    background: var(--navy);
    min-height: 100svh;
    padding-top: 64px;
    display: flex;
    align-items: center;
    overflow: hidden;
  }

  /* Subtle diagonal line texture */
  .hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
      repeating-linear-gradient(
        -45deg,
        transparent,
        transparent 40px,
        rgba(201,168,76,0.025) 40px,
        rgba(201,168,76,0.025) 41px
      );
    pointer-events: none;
  }

  .hero-glow {
    position: absolute; top: -15%; right: -8%;
    width: 55vw; height: 55vw;
    max-width: 680px; max-height: 680px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%);
    pointer-events: none;
  }

  .hero-glow-left {
    position: absolute; bottom: -20%; left: -10%;
    width: 40vw; height: 40vw;
    max-width: 500px; max-height: 500px;
    background: radial-gradient(ellipse, rgba(26,53,96,0.5) 0%, transparent 70%);
    pointer-events: none;
  }

  /* Left gold accent bar */
  .hero-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold) 0%, rgba(201,168,76,0.1) 100%);
  }

  /* Watermark */
  .hero-wm {
    position: absolute; bottom: -8%; right: -2%;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 22vw, 260px);
    font-weight: 700;
    color: rgba(255,255,255,0.02);
    line-height: 1; pointer-events: none; user-select: none; white-space: nowrap;
    letter-spacing: -0.02em;
  }

  .hero-inner {
    position: relative; z-index: 2;
    width: 100%; max-width: 1280px; margin: 0 auto;
    padding: 56px 40px 72px;
    display: grid;
    grid-template-columns: 55fr 45fr;
    gap: 80px;
    align-items: center;
  }

  /* Official badge */
  .hero-badge {
    display: inline-flex; align-items: center; gap: 10px;
    border: 1px solid var(--border-gold);
    border-radius: 3px;
    padding: 7px 16px;
    font-family: 'DM Sans', sans-serif;
    font-size: 10px; font-weight: 600;
    letter-spacing: 0.16em; text-transform: uppercase;
    color: rgba(201,168,76,0.8);
    margin-bottom: 28px;
    background: rgba(201,168,76,0.06);
  }

  .badge-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--gold); flex-shrink: 0;
    animation: dot-pulse 2.4s infinite;
  }
  @keyframes dot-pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.3; transform:scale(0.6); }
  }

  /* Title */
  .hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(38px, 5.5vw, 72px);
    font-weight: 700;
    line-height: 1.05;
    color: var(--white);
    margin-bottom: 24px;
    letter-spacing: -0.01em;
  }
  .hero-title .gold-line {
    color: var(--gold);
    font-style: italic;
    display: block;
  }

  /* Divider */
  .hero-divider {
    width: 48px; height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    margin-bottom: 24px;
  }

  /* Description */
  .hero-desc {
    font-size: 15px; line-height: 1.8;
    color: var(--text-dim);
    font-weight: 300;
    max-width: 460px;
    margin-bottom: 44px;
  }

  /* CTA row */
  .hero-ctas { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }

  .btn-primary {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
    color: var(--navy);
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
    padding: 15px 32px; border-radius: 4px;
    text-decoration: none; border: none; cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 24px rgba(201,168,76,0.32);
  }
  .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(201,168,76,0.48); }
  .btn-primary svg { flex-shrink: 0; }

  .btn-secondary {
    display: inline-flex; align-items: center; gap: 10px;
    background: transparent;
    color: rgba(255,255,255,0.7);
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 500;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 15px 28px; border-radius: 4px;
    border: 1px solid rgba(255,255,255,0.15);
    text-decoration: none;
    transition: border-color 0.2s, color 0.2s, transform 0.2s;
  }
  .btn-secondary:hover { border-color: rgba(255,255,255,0.38); color: #fff; transform: translateY(-2px); }

  /* Stats panel */
  .stats-panel {
    display: flex; flex-direction: column; gap: 1px;
    border: 1px solid var(--border-gold);
    border-radius: 6px;
    overflow: hidden;
    background: var(--border-gold);
  }

  .stats-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 1px;
  }

  .stat-cell {
    background: rgba(9, 25, 48, 0.94);
    padding: 26px 22px;
    transition: background 0.2s;
    position: relative;
  }
  .stat-cell::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 2px; height: 0;
    background: var(--gold);
    transition: height 0.35s ease;
  }
  .stat-cell:hover { background: rgba(18,41,77,0.96); }
  .stat-cell:hover::before { height: 100%; }

  .stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(30px, 3.5vw, 46px);
    font-weight: 700; color: var(--gold);
    line-height: 1; margin-bottom: 5px;
    letter-spacing: -0.01em;
  }
  .stat-lbl {
    font-size: 10px; font-weight: 600;
    letter-spacing: 0.13em; text-transform: uppercase;
    color: rgba(255,255,255,0.38);
  }

  .stats-footer {
    background: rgba(6,17,35,0.97);
    padding: 14px 22px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
  }
  .stats-footer-text {
    font-size: 10.5px; letter-spacing: 0.07em;
    color: rgba(255,255,255,0.28);
    text-transform: uppercase;
  }
  .stats-footer-text strong { color: rgba(201,168,76,0.5); }
  .live-indicator {
    display: flex; align-items: center; gap: 6px;
    font-size: 9.5px; letter-spacing: 0.1em;
    text-transform: uppercase; color: rgba(255,255,255,0.25);
  }
  .live-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: #3FCB6F;
    box-shadow: 0 0 0 0 rgba(63,203,111,0.5);
    animation: live-pulse 2s infinite;
  }
  @keyframes live-pulse {
    0% { box-shadow: 0 0 0 0 rgba(63,203,111,0.5); }
    70% { box-shadow: 0 0 0 6px rgba(63,203,111,0); }
    100% { box-shadow: 0 0 0 0 rgba(63,203,111,0); }
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     SECTION COMMONS
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  .section-wrap { max-width: 1280px; margin: 0 auto; }

  .section-eyebrow {
    display: inline-flex; align-items: center; gap: 12px;
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.18em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 14px;
  }
  .section-eyebrow::before {
    content: '';
    width: 24px; height: 1px; background: var(--gold);
  }

  .section-heading {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 3.5vw, 46px);
    font-weight: 700; color: var(--navy);
    line-height: 1.12; max-width: 540px;
    margin-bottom: 14px; letter-spacing: -0.01em;
  }

  .section-sub {
    font-size: 14.5px; color: var(--text-muted);
    max-width: 500px; line-height: 1.75;
    margin-bottom: 56px; font-weight: 300;
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     PROCESS SECTION
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  .process {
    background: var(--cream);
    padding: 104px 40px;
    position: relative;
  }
  .process::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--navy) 0%, var(--gold) 50%, transparent 100%);
  }

  .steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    position: relative;
  }

  /* Connecting line */
  .steps::after {
    content: ''; position: absolute;
    top: 33px; left: 12.5%; width: 75%;
    height: 1px;
    background: linear-gradient(90deg,
      transparent 0%,
      rgba(201,168,76,0.3) 10%,
      var(--gold) 50%,
      rgba(201,168,76,0.3) 90%,
      transparent 100%
    );
  }

  .step {
    position: relative; z-index: 1;
    padding: 0 20px;
    text-align: center;
  }

  .step-num {
    width: 66px; height: 66px; border-radius: 50%;
    background: var(--navy);
    border: 1.5px solid var(--gold);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px; font-weight: 700; color: var(--gold);
    letter-spacing: -0.01em;
    transition: transform 0.28s, box-shadow 0.28s, background 0.28s;
    position: relative;
  }
  .step:hover .step-num {
    transform: scale(1.1);
    background: var(--gold);
    color: var(--navy);
    box-shadow: 0 0 0 10px rgba(201,168,76,0.08), 0 8px 24px rgba(201,168,76,0.2);
  }

  .step-icon {
    width: 36px; height: 36px; margin: 0 auto 14px;
    color: var(--gold);
    opacity: 0.7;
    transition: opacity 0.2s;
  }
  .step:hover .step-icon { opacity: 1; }

  .step-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 17px; font-weight: 700;
    color: var(--navy); margin-bottom: 8px;
  }
  .step-desc {
    font-size: 12.5px; color: var(--text-muted);
    line-height: 1.7;
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     FEATURES SECTION
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  .features {
    background: var(--cream-dark);
    padding: 104px 40px;
    position: relative;
  }
  .features::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 1px; background: rgba(201,168,76,0.18);
  }

  .features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1px;
    background: rgba(201,168,76,0.12);
    border: 1px solid rgba(201,168,76,0.12);
    border-radius: 8px; overflow: hidden;
  }

  .feature-card {
    background: var(--white);
    padding: 36px 30px;
    transition: background 0.22s;
    position: relative;
  }
  .feature-card::after {
    content: ''; position: absolute;
    bottom: 0; left: 30px; right: 30px;
    height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    transform: scaleX(0); transform-origin: left;
    transition: transform 0.35s ease;
  }
  .feature-card:hover { background: #fdfaf4; }
  .feature-card:hover::after { transform: scaleX(1); }

  .feature-icon-wrap {
    width: 44px; height: 44px;
    border: 1px solid var(--border-gold);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 20px;
    color: var(--gold);
    background: var(--gold-pale);
    transition: background 0.2s, border-color 0.2s;
  }
  .feature-card:hover .feature-icon-wrap {
    background: rgba(201,168,76,0.18);
    border-color: rgba(201,168,76,0.4);
  }

  .feature-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px; font-weight: 700;
    color: var(--navy); margin-bottom: 8px;
  }
  .feature-desc {
    font-size: 13px; color: var(--text-muted); line-height: 1.7;
    font-weight: 300;
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     CTA BANNER
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  .cta-banner {
    background: var(--navy);
    padding: 80px 40px;
    position: relative; overflow: hidden;
  }
  .cta-banner::before {
    content: ''; position: absolute; inset: 0;
    background:
      repeating-linear-gradient(
        -45deg,
        transparent, transparent 40px,
        rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
      );
  }
  .cta-inner {
    position: relative; z-index: 1;
    max-width: 1280px; margin: 0 auto;
    display: flex; align-items: center;
    justify-content: space-between; gap: 40px;
    flex-wrap: wrap;
  }
  .cta-label {
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.18em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 12px;
    display: flex; align-items: center; gap: 10px;
  }
  .cta-label::before { content: ''; width: 20px; height: 1px; background: var(--gold); }
  .cta-text h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(24px, 3.2vw, 38px);
    font-weight: 700; color: var(--white);
    line-height: 1.1; margin-bottom: 10px;
  }
  .cta-text p {
    font-size: 14px; color: rgba(255,255,255,0.46);
    max-width: 460px; line-height: 1.7; font-weight: 300;
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     FOOTER STRIP
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  .home-footer {
    background: #060f1e;
    border-top: 1px solid rgba(201,168,76,0.12);
    padding: 22px 40px;
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap;
    gap: 10px;
  }
  .home-footer p {
    font-size: 11px; color: rgba(255,255,255,0.22);
    letter-spacing: 0.06em;
  }
  .home-footer strong { color: rgba(201,168,76,0.45); }
  .footer-seal {
    font-size: 10px; letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.15);
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     ANIMATIONS
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .fu   { animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) both; }
  .d1   { animation-delay: 0.04s; }
  .d2   { animation-delay: 0.16s; }
  .d3   { animation-delay: 0.28s; }
  .d4   { animation-delay: 0.40s; }
  .d5   { animation-delay: 0.52s; }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     RESPONSIVE
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  @media (max-width: 1100px) {
    .hero-inner { grid-template-columns: 1fr; gap: 48px; padding: 48px 32px 64px; }
    .hero-desc { max-width: 100%; }
    .stats-panel { max-width: 520px; }
    .process, .features { padding: 80px 32px; }
    .steps { grid-template-columns: 1fr 1fr; gap: 48px; }
    .steps::after { display: none; }
    .features-grid { grid-template-columns: 1fr 1fr; }
    .cta-banner { padding: 64px 32px; }
  }

  @media (max-width: 768px) {
    .hero { min-height: auto; }
    .hero-inner { padding: 40px 20px 56px; gap: 40px; }
    .hero-ctas { flex-direction: column; }
    .btn-primary, .btn-secondary { justify-content: center; width: 100%; }
    .process, .features { padding: 64px 20px; }
    .features-grid { grid-template-columns: 1fr; border-radius: 6px; }
    .cta-banner { padding: 56px 20px; }
    .cta-inner { flex-direction: column; }
    .cta-inner .btn-primary { width: 100%; max-width: 360px; justify-content: center; }
    .cta-text p { max-width: 100%; }
    .home-footer { flex-direction: column; text-align: center; padding: 20px; }
  }

  @media (max-width: 540px) {
    .steps { grid-template-columns: 1fr; gap: 36px; }
    .hero-title { font-size: 36px; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    .stat-cell { padding: 20px 16px; }
    .stat-num { font-size: 28px; }
  }
</style>
@endsection
