@extends($mainLayout)
@section('title', 'My Dashboard - Daet Listens')
@section('content')

<style>
  .citizen-dashboard {
    --dashboard-navy: #0b1f3a;
    --dashboard-navy-soft: #163258;
    --dashboard-ink: #14263e;
    --dashboard-body: #4d5b6c;
    --dashboard-muted: #6d7886;
    --dashboard-ground: #f4f1eb;
    --dashboard-paper: #fffdf9;
    --dashboard-line: #dfe3e6;
    --dashboard-gold: #c9a84c;
    --dashboard-gold-light: #e2c06a;
    --dashboard-gold-ink: #7a5a13;
    --dashboard-gold-wash: #fbf3d9;
    min-height: calc(100svh - var(--navbar-height));
    padding-top: var(--navbar-height);
    overflow-x: hidden;
    background: var(--dashboard-ground);
    color: var(--dashboard-ink);
    font-family: var(--font-sans, 'DM Sans', sans-serif);
  }

  .citizen-dashboard,
  .citizen-dashboard * {
    box-sizing: border-box;
  }

  .citizen-dashboard a:focus-visible {
    outline: 2px solid var(--dashboard-gold);
    outline-offset: 3px;
  }

  /* Welcome band */
  .dashboard-welcome {
    position: relative;
    overflow: hidden;
    background: var(--dashboard-navy);
    color: #fff;
  }

  .dashboard-welcome::before {
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(
      135deg,
      transparent 0,
      transparent 34px,
      rgba(226, 192, 106, 0.045) 35px,
      rgba(226, 192, 106, 0.045) 36px
    );
    content: '';
    pointer-events: none;
  }

  .dashboard-welcome::after {
    position: absolute;
    right: -120px;
    bottom: -190px;
    width: 440px;
    height: 440px;
    border: 1px solid rgba(226, 192, 106, 0.16);
    border-radius: 50%;
    box-shadow: 0 0 0 28px rgba(226, 192, 106, 0.025), 0 0 0 56px rgba(226, 192, 106, 0.018);
    content: '';
    pointer-events: none;
  }

  .dashboard-welcome-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 36px;
    max-width: 1240px;
    margin: 0 auto;
    padding: 48px 40px 46px;
  }

  .dashboard-welcome-copy {
    max-width: 670px;
  }

  .dashboard-welcome-title {
    max-width: 650px;
    margin: 0;
    color: #fff;
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: clamp(2.55rem, 4.4vw, 3.7rem);
    font-weight: 700;
    letter-spacing: -0.025em;
    line-height: 0.98;
    overflow-wrap: anywhere;
  }

  .dashboard-welcome-title span {
    color: var(--dashboard-gold-light);
  }

  .dashboard-welcome-copy p {
    max-width: 560px;
    margin: 18px 0 0;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    line-height: 1.7;
  }

  .dashboard-welcome-actions {
    display: flex;
    flex: 0 0 232px;
    flex-direction: column;
    gap: 11px;
  }

  .dashboard-primary-action,
  .dashboard-secondary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 46px;
    padding: 12px 18px;
    border-radius: 4px;
    font-size: 0.73rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    line-height: 1.2;
    text-align: center;
    text-decoration: none;
    text-transform: uppercase;
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease, color 180ms ease;
  }

  .dashboard-primary-action {
    background: var(--dashboard-gold);
    color: var(--dashboard-navy);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
  }

  .dashboard-primary-action:hover {
    background: var(--dashboard-gold-light);
    transform: translateY(-2px);
  }

  .dashboard-secondary-action {
    border: 1px solid rgba(255, 255, 255, 0.24);
    color: rgba(255, 255, 255, 0.86);
  }

  .dashboard-secondary-action:hover {
    border-color: rgba(226, 192, 106, 0.7);
    color: #fff;
    transform: translateY(-2px);
  }

  .dashboard-private-line {
    display: flex;
    align-items: center;
    gap: 9px;
    max-width: 1240px;
    margin: 0 auto;
    padding: 12px 40px;
    border-bottom: 1px solid var(--dashboard-line);
    background: rgba(255, 253, 249, 0.66);
    color: var(--dashboard-muted);
    font-size: 0.76rem;
    line-height: 1.5;
  }

  .dashboard-private-line svg {
    flex: 0 0 auto;
    color: var(--dashboard-gold-ink);
  }

  .dashboard-private-line strong {
    color: var(--dashboard-ink);
    font-weight: 700;
  }

  /* Main work area */
  .dashboard-content {
    max-width: 1240px;
    margin: 0 auto;
    padding: 34px 40px 72px;
  }

  .dashboard-alert {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
    padding: 15px 18px;
    border: 1px solid #ead39a;
    border-radius: 6px;
    background: #fff9e9;
    color: #6c5315;
    text-decoration: none;
    transition: border-color 180ms ease, background-color 180ms ease, transform 180ms ease;
  }

  .dashboard-alert:hover {
    border-color: #d5b45d;
    background: #fff7df;
    transform: translateY(-1px);
  }

  .dashboard-alert-icon {
    display: flex;
    flex: 0 0 34px;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #f5e4ae;
    color: #7a5a13;
  }

  .dashboard-alert-copy {
    min-width: 0;
    flex: 1;
  }

  .dashboard-alert-copy strong {
    display: block;
    margin-bottom: 2px;
    color: #5d450d;
    font-size: 0.85rem;
  }

  .dashboard-alert-copy span {
    display: -webkit-box;
    overflow: hidden;
    color: #806b32;
    font-size: 0.78rem;
    line-height: 1.5;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }

  .dashboard-alert-link {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 7px;
    color: #6c5315;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .dashboard-alert-link svg {
    transition: transform 180ms ease;
  }

  .dashboard-alert:hover .dashboard-alert-link svg {
    transform: translateX(3px);
  }

  .dashboard-columns {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    align-items: start;
    gap: 48px;
  }

  .dashboard-reports,
  .dashboard-rail {
    min-width: 0;
  }

  .dashboard-section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 18px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--dashboard-line);
  }

  .dashboard-section-heading h2,
  .dashboard-rail h2 {
    margin: 0;
    color: var(--dashboard-ink);
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    line-height: 1;
  }

  .dashboard-section-heading p {
    max-width: 470px;
    margin: 8px 0 0;
    color: var(--dashboard-muted);
    font-size: 0.82rem;
    line-height: 1.55;
  }

  .dashboard-section-link {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 7px;
    min-height: 44px;
    padding: 8px 0 6px;
    border-bottom: 1px solid rgba(201, 168, 76, 0.55);
    color: var(--dashboard-navy-soft);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-decoration: none;
    text-transform: uppercase;
    transition: color 180ms ease, border-color 180ms ease;
  }

  .dashboard-section-link:hover {
    border-color: var(--dashboard-navy);
    color: var(--dashboard-navy);
  }

  .dashboard-section-link svg {
    transition: transform 180ms ease;
  }

  .dashboard-section-link:hover svg {
    transform: translateX(3px);
  }

  /* Report sheet: one continuous surface, not a grid of cards */
  .dashboard-report-list {
    overflow: hidden;
    border: 1px solid var(--dashboard-line);
    border-radius: 8px;
    background: var(--dashboard-paper);
    box-shadow: 0 12px 30px rgba(20, 38, 62, 0.055);
  }

  .dashboard-report-row {
    position: relative;
    display: grid;
    grid-template-columns: 12px minmax(0, 1fr) auto;
    gap: 18px;
    padding: 24px 24px 23px;
    border-bottom: 1px solid var(--dashboard-line);
    transition: background-color 180ms ease;
  }

  .dashboard-report-row:last-child {
    border-bottom: 0;
  }

  .dashboard-report-row:hover {
    background: #fffcf5;
  }

  .dashboard-report-row--latest {
    background: #fffcf4;
  }

  .dashboard-report-row--latest:hover {
    background: #fff9e9;
  }

  .dashboard-report-marker {
    position: relative;
    display: flex;
    justify-content: center;
    padding-top: 5px;
  }

  .dashboard-report-marker::before {
    position: relative;
    z-index: 1;
    display: block;
    width: 10px;
    height: 10px;
    border: 2px solid var(--dashboard-gold);
    border-radius: 50%;
    background: var(--dashboard-paper);
    content: '';
  }

  .dashboard-report-row:not(:last-child) .dashboard-report-marker::after {
    position: absolute;
    top: 18px;
    bottom: -29px;
    left: 5px;
    width: 1px;
    background: #e6d8aa;
    content: '';
  }

  .dashboard-report-row--latest .dashboard-report-marker::before {
    background: var(--dashboard-gold);
    box-shadow: 0 0 0 4px rgba(201, 168, 76, 0.13);
  }

  .dashboard-report-content {
    min-width: 0;
  }

  .dashboard-report-topline {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
  }

  .dashboard-report-ticket {
    color: var(--dashboard-muted);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .dashboard-report-latest {
    color: var(--dashboard-gold-ink);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .dashboard-report-category {
    color: var(--dashboard-gold-ink);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
  }

  .dashboard-report-category::before {
    margin-right: 8px;
    color: #c7b98e;
    content: '•';
  }

  .dashboard-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.2;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .dashboard-status::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    content: '';
    opacity: 0.75;
  }

  .citizen-dashboard .dashboard-status-submitted {
    border-color: #c9dcfb;
    background: #edf4ff;
    color: #1e40af;
  }

  .citizen-dashboard .dashboard-status-under-review {
    border-color: #ecd7a0;
    background: #fff8df;
    color: #8a5b0b;
  }

  .citizen-dashboard .dashboard-status-in-progress {
    border-color: #c9d7f3;
    background: #eef3ff;
    color: #3156a5;
  }

  .citizen-dashboard .dashboard-status-resolved {
    border-color: #bfe4cb;
    background: #edf9f0;
    color: #17653a;
  }

  .citizen-dashboard .dashboard-status-rejected {
    border-color: #efc5c5;
    background: #fff0f0;
    color: #a12d2d;
  }

  .citizen-dashboard .dashboard-status-closed {
    border-color: #d6dce2;
    background: #f2f4f6;
    color: #56616d;
  }

  .dashboard-report-title {
    margin: 0 0 7px;
    color: var(--dashboard-ink);
    font-size: 1.08rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    line-height: 1.35;
    overflow-wrap: anywhere;
  }

  .dashboard-report-title a {
    color: inherit;
    text-decoration: none;
    transition: color 180ms ease;
  }

  .dashboard-report-title a:hover {
    color: #8a6717;
  }

  .dashboard-report-next {
    max-width: 620px;
    margin: 0 0 13px;
    color: var(--dashboard-body);
    font-size: 0.82rem;
    line-height: 1.55;
  }

  .dashboard-report-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 16px;
    color: var(--dashboard-muted);
    font-size: 0.72rem;
    line-height: 1.45;
  }

  .dashboard-report-meta span {
    display: inline-flex;
    align-items: center;
  }

  .dashboard-report-meta span + span::before {
    width: 3px;
    height: 3px;
    margin-right: 16px;
    border-radius: 50%;
    background: #c4cbd1;
    content: '';
  }

  .dashboard-report-action {
    display: inline-flex;
    align-items: center;
    align-self: center;
    gap: 7px;
    min-height: 44px;
    padding: 8px 0 8px 10px;
    color: var(--dashboard-navy-soft);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-decoration: none;
    text-transform: uppercase;
    white-space: nowrap;
    transition: color 180ms ease;
  }

  .dashboard-report-action:hover {
    color: #8a6717;
  }

  .dashboard-report-action svg {
    transition: transform 180ms ease;
  }

  .dashboard-report-action:hover svg {
    transform: translateX(3px);
  }

  .dashboard-empty {
    padding: 66px 28px 70px;
    border-top: 1px solid var(--dashboard-line);
    border-bottom: 1px solid var(--dashboard-line);
    background: var(--dashboard-paper);
    text-align: center;
  }

  .dashboard-empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    margin: 0 auto 18px;
    border: 1px solid #e6d8aa;
    border-radius: 50%;
    background: var(--dashboard-gold-wash);
    color: var(--dashboard-gold-ink);
  }

  .dashboard-empty h3 {
    margin: 0 0 8px;
    color: var(--dashboard-ink);
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: 1.8rem;
    line-height: 1;
  }

  .dashboard-empty p {
    max-width: 410px;
    margin: 0 auto 22px;
    color: var(--dashboard-muted);
    font-size: 0.84rem;
    line-height: 1.7;
  }

  .dashboard-empty-action {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 44px;
    padding: 12px 20px;
    border-radius: 4px;
    background: var(--dashboard-navy);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-decoration: none;
    text-transform: uppercase;
    transition: background-color 180ms ease, transform 180ms ease;
  }

  .dashboard-empty-action:hover {
    background: var(--dashboard-navy-soft);
    transform: translateY(-2px);
  }

  /* Right rail */
  .dashboard-rail-primary {
    position: relative;
    overflow: hidden;
    padding: 25px 24px 24px;
    border-radius: 8px;
    background: var(--dashboard-navy);
    color: #fff;
  }

  .dashboard-rail-primary::after {
    position: absolute;
    right: -54px;
    bottom: -72px;
    width: 180px;
    height: 180px;
    border: 1px solid rgba(226, 192, 106, 0.23);
    border-radius: 50%;
    content: '';
    pointer-events: none;
  }

  .dashboard-rail-primary > * {
    position: relative;
    z-index: 1;
  }

  .dashboard-rail-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    margin-bottom: 20px;
    border: 1px solid rgba(226, 192, 106, 0.5);
    border-radius: 50%;
    color: var(--dashboard-gold-light);
  }

  .dashboard-rail h2 {
    font-size: 1.72rem;
    line-height: 1.02;
  }

  .dashboard-rail-primary p {
    margin: 12px 0 20px;
    color: rgba(255, 255, 255, 0.68);
    font-size: 0.8rem;
    line-height: 1.65;
  }

  .dashboard-rail-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 8px 0;
    color: var(--dashboard-gold-light);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-decoration: none;
    text-transform: uppercase;
    transition: color 180ms ease;
  }

  .dashboard-rail-link:hover {
    color: #fff;
  }

  .dashboard-rail-link svg {
    transition: transform 180ms ease;
  }

  .dashboard-rail-link:hover svg {
    transform: translateX(3px);
  }

  .dashboard-rail-section {
    padding: 26px 0 25px;
    border-bottom: 1px solid var(--dashboard-line);
  }

  .dashboard-rail-section h2 {
    margin-bottom: 18px;
    font-size: 1.55rem;
  }

  .dashboard-process {
    list-style: none;
    display: grid;
    gap: 18px;
    margin: 0;
    padding: 0;
  }

  .dashboard-process li {
    position: relative;
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    gap: 12px;
  }

  .dashboard-process li:not(:last-child)::after {
    position: absolute;
    top: 31px;
    bottom: -18px;
    left: 13px;
    width: 1px;
    background: #e6d8aa;
    content: '';
  }

  .dashboard-process-number {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 1px solid #ddc77e;
    border-radius: 50%;
    background: var(--dashboard-ground);
    color: var(--dashboard-gold-ink);
    font-size: 0.68rem;
    font-weight: 700;
  }

  .dashboard-process-copy strong {
    display: block;
    margin: 2px 0 4px;
    color: var(--dashboard-ink);
    font-size: 0.8rem;
    font-weight: 700;
  }

  .dashboard-process-copy p {
    margin: 0;
    color: var(--dashboard-muted);
    font-size: 0.74rem;
    line-height: 1.55;
  }

  .dashboard-privacy {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding-top: 24px;
  }

  .dashboard-privacy > svg {
    flex: 0 0 auto;
    margin-top: 2px;
    color: var(--dashboard-gold-ink);
  }

  .dashboard-privacy strong {
    display: block;
    margin-bottom: 5px;
    color: var(--dashboard-ink);
    font-size: 0.78rem;
  }

  .dashboard-privacy p {
    margin: 0;
    color: var(--dashboard-muted);
    font-size: 0.73rem;
    line-height: 1.6;
  }

  .dashboard-transparency-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 44px;
    margin-top: 12px;
    padding: 8px 0;
    color: var(--dashboard-navy-soft);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-decoration: none;
    text-transform: uppercase;
  }

  .dashboard-transparency-link:hover {
    color: #8a6717;
  }

  .dashboard-transparency-link svg {
    transition: transform 180ms ease;
  }

  .dashboard-transparency-link:hover svg {
    transform: translateX(3px);
  }

  .dashboard-pagination {
    padding-top: 18px;
  }

  .dashboard-pagination nav {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 5px;
  }

  .dashboard-pagination nav a,
  .dashboard-pagination nav span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    min-height: 32px;
    padding: 0 9px;
    border: 1px solid var(--dashboard-line);
    border-radius: 4px;
    background: var(--dashboard-paper);
    color: var(--dashboard-body);
    font-size: 0.75rem;
    text-decoration: none;
  }

  .dashboard-pagination nav a:hover {
    border-color: var(--dashboard-gold);
    color: var(--dashboard-navy);
  }

  .dashboard-pagination nav span[aria-current='page'] {
    border-color: var(--dashboard-navy);
    background: var(--dashboard-navy);
    color: #fff;
  }

  .dashboard-pagination nav span[aria-disabled='true'] {
    color: #a7afb7;
  }

  .dashboard-success {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 28px;
    padding: 15px 18px;
    border: 1px solid #b9dfc4;
    border-radius: 6px;
    background: #effaf2;
    color: #21623a;
    font-size: 0.8rem;
    line-height: 1.6;
  }

  .dashboard-success svg {
    flex: 0 0 auto;
    margin-top: 1px;
  }

  .dashboard-success-ticket {
    display: inline-block;
    margin: 0 4px;
    color: #174e2d;
    font-weight: 700;
    letter-spacing: 0.04em;
  }

  @media (max-width: 980px) {
    .dashboard-columns {
      grid-template-columns: minmax(0, 1fr) 270px;
      gap: 30px;
    }

    .dashboard-welcome-inner,
    .dashboard-private-line,
    .dashboard-content {
      padding-right: 32px;
      padding-left: 32px;
    }
  }

  @media (max-width: 760px) {
    .dashboard-welcome-inner {
      align-items: flex-start;
      flex-direction: column;
      gap: 26px;
      padding-top: 38px;
      padding-bottom: 38px;
    }

    .dashboard-welcome-actions {
      width: 100%;
      flex-basis: auto;
      flex-direction: row;
    }

    .dashboard-welcome-actions > * {
      flex: 1;
    }

    .dashboard-columns {
      grid-template-columns: 1fr;
    }

    .dashboard-rail {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: 24px;
    }

    .dashboard-rail-primary {
      grid-column: 1 / -1;
    }

    .dashboard-rail-section {
      padding-top: 0;
    }

    .dashboard-privacy {
      padding-top: 0;
    }
  }

  @media (max-width: 560px) {
    .dashboard-welcome-inner,
    .dashboard-private-line,
    .dashboard-content {
      padding-right: 20px;
      padding-left: 20px;
    }

    .dashboard-welcome-inner {
      padding-top: 34px;
      padding-bottom: 34px;
    }

    .dashboard-welcome-title {
      font-size: 2.65rem;
    }

    .dashboard-welcome-actions {
      flex-direction: column;
    }

    .dashboard-private-line {
      align-items: flex-start;
      padding-top: 11px;
      padding-bottom: 11px;
    }

    .dashboard-content {
      padding-top: 27px;
      padding-bottom: 52px;
    }

    .dashboard-section-heading {
      align-items: flex-start;
      flex-direction: column;
      gap: 13px;
    }

    .dashboard-section-heading h2 {
      font-size: 1.85rem;
    }

    .dashboard-report-row {
      grid-template-columns: 10px minmax(0, 1fr);
      gap: 13px;
      padding: 20px 16px;
    }

    .dashboard-report-row:not(:last-child) .dashboard-report-marker::after {
      bottom: -25px;
    }

    .dashboard-report-action {
      grid-column: 2;
      justify-self: start;
      min-height: 44px;
      padding: 8px 0;
    }

    .dashboard-report-meta {
      gap: 5px 12px;
    }

    .dashboard-report-meta span + span::before {
      margin-right: 12px;
    }

    .dashboard-rail {
      display: block;
    }

    .dashboard-rail-section {
      padding-top: 24px;
    }

    .dashboard-privacy {
      padding-top: 24px;
    }

    .dashboard-alert {
      align-items: flex-start;
      flex-wrap: wrap;
    }

    .dashboard-alert-link {
      margin-left: 48px;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .citizen-dashboard *,
    .citizen-dashboard *::before,
    .citizen-dashboard *::after {
      transition-duration: 0.01ms !important;
    }
  }
</style>

<div class="citizen-dashboard">
  <section class="dashboard-welcome" aria-labelledby="dashboard-welcome-title">
    <div class="dashboard-welcome-inner">
      <div class="dashboard-welcome-copy">
        @php
          $hour = now('Asia/Manila')->hour;
          $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        @endphp
        <h1 class="dashboard-welcome-title" id="dashboard-welcome-title">
          {{ $greeting }}, <span>{{ auth()->user()->first_name }}.</span>
        </h1>
        <p>
          File a concern, follow its progress, and keep every report in one place.
        </p>
      </div>

      <div class="dashboard-welcome-actions">
        <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-primary-action">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
          </svg>
          File a new complaint
        </a>
        <a href="{{ route('complaints.track') }}" wire:navigate class="dashboard-secondary-action">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m20 20-4-4"></path>
          </svg>
          Track a report
        </a>
      </div>
    </div>
  </section>

  <div class="dashboard-private-line">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <rect x="5" y="10" width="14" height="10" rx="2"></rect>
      <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
    </svg>
    <span><strong>Private view:</strong> this dashboard lists reports linked to your account. It is separate from the public transparency register.</span>
  </div>

  <div class="dashboard-content">
    @if (session('success'))
      <div class="dashboard-success" role="status">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>
          Complaint submitted successfully.
          <span class="dashboard-success-ticket">{{ session('new_ticket') }}</span>
          Keep this ticket number for your records.
        </div>
      </div>
    @endif

    @if ($attentionComplaint)
      <a href="{{ route('complaints.show', $attentionComplaint) }}" wire:navigate class="dashboard-alert">
        <span class="dashboard-alert-icon" aria-hidden="true">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 8v4"></path>
            <path d="M12 16h.01"></path>
          </svg>
        </span>
        <span class="dashboard-alert-copy">
          <strong>An LGU review update is available</strong>
          <span>{{ $attentionComplaint->ticket_id }} · {{ $attentionComplaint->title }} has a current status update to review.</span>
        </span>
        <span class="dashboard-alert-link">
          Read update
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"></path>
            <path d="m13 6 6 6-6 6"></path>
          </svg>
        </span>
      </a>
    @endif

    <div class="dashboard-columns">
      <section class="dashboard-reports" aria-labelledby="dashboard-reports-title">
        <div class="dashboard-section-heading">
          <div>
            <h2 id="dashboard-reports-title">Your reports</h2>
            <p>See the latest status and what happens next for each concern you filed.</p>
          </div>
          <a href="{{ route('complaints.index') }}" wire:navigate class="dashboard-section-link">
            Open report center
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"></path>
              <path d="m13 6 6 6-6 6"></path>
            </svg>
          </a>
        </div>

        @if ($complaints->isEmpty())
          <div class="dashboard-empty">
            <div class="dashboard-empty-icon" aria-hidden="true">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
              </svg>
            </div>
            <h3>No reports filed yet</h3>
            <p>
              Have a concern about a public service or facility? Start a report and we will route it to the right LGU department.
            </p>
            <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-empty-action">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
              </svg>
              File your first complaint
            </a>
          </div>
        @else
          @php
            $categoryLabels = collect(\App\Services\DepartmentRouter::getCategoryOptions())
              ->flatten()
              ->pluck('label', 'value');
          @endphp
          <div class="dashboard-report-list">
            @foreach ($complaints as $complaint)
              @php
                $statusEnum = $complaint->statusEnum;
                $reviewEnum = $complaint->reviewStatusEnum;
                $statusValue = $statusEnum->value;
                $reviewValue = $reviewEnum->value;
                $isLatest = $complaints->currentPage() === 1 && $loop->first;
                $categoryLabel = $categoryLabels->get(
                    $complaint->category,
                    ucwords(str_replace('_', ' ', (string) $complaint->category)),
                );
                $createdAt = $complaint->created_at->copy()->setTimezone('Asia/Manila');
                $nextStep = match (true) {
                    $reviewValue === 'needs_information' => 'The LGU marked this report as needing more information. Open it to review the current status.',
                    $reviewValue === 'duplicate' => 'This report is being checked against another report.',
                    $reviewValue === 'rejected' => 'This report was not accepted for further action.',
                    $statusValue === 'Submitted' => 'Your report is waiting for an initial LGU review.',
                    $statusValue === 'Under Review' => 'The assigned team is reviewing your concern.',
                    $statusValue === 'In Progress' => 'The assigned team is working on this concern.',
                    $statusValue === 'Resolved' => 'The assigned team marked this concern as resolved.',
                    $statusValue === 'Closed' => 'This report is now closed.',
                    default => 'Open the report to see the latest updates from the LGU.',
                };
              @endphp

              <article class="dashboard-report-row {{ $isLatest ? 'dashboard-report-row--latest' : '' }}">
                <div class="dashboard-report-marker" aria-hidden="true"></div>
                <div class="dashboard-report-content">
                  <div class="dashboard-report-topline">
                    <span class="dashboard-report-ticket">{{ $complaint->ticket_id }}</span>
                    @if ($isLatest)
                      <span class="dashboard-report-latest">Latest report</span>
                    @endif
                    <span class="dashboard-report-category">{{ $categoryLabel }}</span>
                    <span class="dashboard-status dashboard-status-{{ str($statusValue)->slug() }}">{{ $statusEnum->label() }}</span>
                  </div>
                  <h3 class="dashboard-report-title">
                    <a href="{{ route('complaints.show', $complaint) }}" wire:navigate>{{ $complaint->title }}</a>
                  </h3>
                  <p class="dashboard-report-next">{{ $nextStep }}</p>
                  <div class="dashboard-report-meta">
                    <span>{{ $reviewEnum->label() }}</span>
                    <span>{{ $complaint->department->name ?? 'Awaiting routing' }}</span>
                    <time datetime="{{ $createdAt->toAtomString() }}">Filed {{ $createdAt->format('M j, Y') }}</time>
                  </div>
                </div>
                <a href="{{ route('complaints.show', $complaint) }}" wire:navigate class="dashboard-report-action" aria-label="View report {{ $complaint->ticket_id }}">
                  View report
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14"></path>
                    <path d="m13 6 6 6-6 6"></path>
                  </svg>
                </a>
              </article>
            @endforeach
          </div>

          @if ($complaints->hasPages())
            <div class="dashboard-pagination" aria-label="Report pages">
              {{ $complaints->links() }}
            </div>
          @endif
        @endif
      </section>

      <aside class="dashboard-rail" aria-label="Dashboard help">
        <section class="dashboard-rail-primary">
          <div class="dashboard-rail-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 20h9"></path>
              <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
          </div>
          <h2>Have a concern?</h2>
          <p>Tell us about a public service or facility that needs attention. We will help route your report to the right department.</p>
          <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-rail-link">
            Start a report
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"></path>
              <path d="m13 6 6 6-6 6"></path>
            </svg>
          </a>
        </section>

        <section class="dashboard-rail-section" aria-labelledby="dashboard-process-title">
          <h2 id="dashboard-process-title">What happens next</h2>
          <ol class="dashboard-process">
            <li>
              <span class="dashboard-process-number">1</span>
              <div class="dashboard-process-copy">
                <strong>You submit a report</strong>
                <p>Share the concern, location, and any evidence that helps the LGU understand it.</p>
              </div>
            </li>
            <li>
              <span class="dashboard-process-number">2</span>
              <div class="dashboard-process-copy">
                <strong>The LGU reviews it</strong>
                <p>The appropriate team checks the report and confirms its priority and routing.</p>
              </div>
            </li>
            <li>
              <span class="dashboard-process-number">3</span>
              <div class="dashboard-process-copy">
                <strong>You follow the progress</strong>
                <p>Open your report any time to see status changes and public updates.</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="dashboard-rail-section dashboard-privacy" aria-label="Privacy information">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="5" y="10" width="14" height="10" rx="2"></rect>
            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
          </svg>
          <div>
            <strong>Public transparency is separate</strong>
            <p>Transparency withholds your account name and contact details. Approved public reports may still include the submitted title, description, photos, and broad location.</p>
            <a href="{{ route('transparency') }}" wire:navigate class="dashboard-transparency-link">
              View public transparency
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14"></path>
                <path d="m13 6 6 6-6 6"></path>
              </svg>
            </a>
          </div>
        </section>
      </aside>
    </div>
  </div>
</div>

@endsection
