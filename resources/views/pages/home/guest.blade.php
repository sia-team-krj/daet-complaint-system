@extends($mainLayout)
@section('title', __('landing.meta_title'))

@push('styles')
    @include('partials.public-ui')
@endpush

@php
    $dashboardRoute = auth()->user()?->homeRouteName() ?? 'complaints.index';
@endphp

@section('content')

<style>
  /* ══════════════════════════════════════════════════════════════
     Guest landing page — “official but human”. Inter only, tokens
     only, no cream, no serif, no eyebrow labels.
     ══════════════════════════════════════════════════════════════ */
  .lp-root { font-family: var(--font-sans); background: var(--bg); width: 100%; }
  .lp-root *, .lp-root *::before, .lp-root *::after { box-sizing: border-box; }

  /* ── Buttons ─────────────────────────────────────────────────── */
  .lp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 12px 24px;
    border-radius: 14px;
    font-family: var(--font-sans);
    font-size: 16px;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-decoration: none;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
  }
  .lp-btn:focus-visible { outline: none; box-shadow: var(--ring); }
  .lp-btn--primary {
    background: var(--gold-500);
    color: var(--navy-900);
    box-shadow: 0 2px 4px rgba(11, 26, 51, 0.12), 0 12px 32px -12px rgba(212, 169, 71, 0.55);
  }
  .lp-btn--primary:hover { background: var(--gold-300); transform: translateY(-1px); box-shadow: 0 2px 4px rgba(11, 26, 51, 0.14), 0 16px 36px -12px rgba(212, 169, 71, 0.6); }
  .lp-btn--secondary {
    background: transparent;
    color: var(--surface);
    border: 1.5px solid rgba(255, 255, 255, 0.35);
  }
  .lp-btn--secondary:hover { border-color: rgba(255, 255, 255, 0.6); color: var(--surface); }
  @media (max-width: 767px) {
    .lp-btn { min-height: 48px; width: 100%; font-size: 16px; }
  }

  /* ── Hero ────────────────────────────────────────────────────── */
  .lp-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 62%, var(--navy-600) 100%);
    /* Design-system §4 banner: navbar offset inside the navy, 16px visible gap */
    padding: calc(var(--navbar-height, 64px) + 16px) 0 24px;
  }
  .lp-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.035) 0 1px,
      transparent 1px 14px
    );
    pointer-events: none;
  }
  .lp-hero__inner {
    position: relative;
    width: 100%;
    max-width: 1040px;
    margin: 0 auto;
    padding: 0 24px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
  }

  @media (min-width: 1024px) {
    .lp-hero__inner {
      grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
      gap: 48px;
      align-items: center;
      padding: 0 24px;
    }
  }

  .lp-hero__copy { max-width: 560px; padding-bottom: 24px; }
  @media (min-width: 1024px) { .lp-hero__copy { padding-bottom: 0; } }

  .lp-h1 {
    margin: 0 0 16px;
    font-size: 34px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--surface);
  }
  @media (min-width: 1024px) { .lp-h1 { font-size: 52px; } }
  .lp-h1__line, .lp-h1__gold { display: block; }
  .lp-h1__gold { color: var(--gold-300); }

  .lp-hero__sub {
    margin: 0 0 24px;
    font-size: 17px;
    line-height: 1.55;
    color: var(--navy-100);
    max-width: 520px;
  }

  .lp-hero__ctas { display: flex; flex-wrap: wrap; gap: 12px; }
  .lp-hero__ctas .lp-btn--secondary { flex: 0 0 auto; }
  @media (max-width: 767px) {
    .lp-hero__ctas { flex-direction: column; }
    .lp-hero__ctas .lp-btn--secondary { width: 100%; }
  }

  .lp-hero__reassurance {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 16px 0 0;
    font-size: 14px;
    line-height: 1.4;
    color: var(--navy-100);
  }
  .lp-hero__reassurance svg { flex: 0 0 auto; color: var(--gold-300); }

  /* ── Phone mockup (HTML/CSS, hidden < 1024px) ────────────────── */
  .lp-phone { display: none; }
  @media (min-width: 1024px) {
    .lp-phone {
      display: block;
      position: relative;
      justify-self: center;
      height: 400px;
      width: auto;
      aspect-ratio: 9 / 18.5;
      margin-bottom: 0;
      border: 10px solid var(--navy-950);
      border-radius: 44px;
      background: var(--navy-950);
      box-shadow: 0 24px 64px -12px rgba(2, 6, 18, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.06);
      overflow: hidden;
    }
    .lp-phone::after {
      content: '';
      position: absolute;
      top: 10px;
      left: 50%;
      transform: translateX(-50%);
      width: 96px;
      height: 16px;
      border-radius: 999px;
      background: var(--navy-700);
    }
  }
  .lp-phone__screen {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 38px 12px 12px;
    background: var(--bg);
    border-radius: 34px;
    overflow: hidden;
  }
  .lp-phone__appbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 12px;
    background: var(--navy-800);
    color: var(--surface);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }
  .lp-phone__appbar img { width: 16px; height: 16px; }
  .lp-phone__skeleton { height: 8px; border-radius: 4px; background: var(--line-soft); }
  .lp-phone__skeleton--w60 { width: 60%; }
  .lp-phone__skeleton--w80 { width: 80%; }

  /* ── Sample tracker card (shared markup) ─────────────────────── */
  .lp-tracker {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    padding: 12px;
    border-radius: 14px;
    background: var(--surface);
    border: 1px solid var(--line-soft);
    box-shadow: var(--shadow-card);
  }
  .lp-tracker__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
  .lp-tracker__chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: var(--radius-pill);
    background: var(--navy-50);
    color: var(--navy-700);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .lp-tracker__complaint { display: flex; align-items: center; gap: 10px; }
  .lp-tracker__title { margin: 0; font-size: 13.5px; font-weight: 600; line-height: 1.35; color: var(--text); }
  .lp-tracker__status { display: flex; }
  .lp-tracker .progress { width: 100%; }
  .lp-phone__screen .ticket-id { font-size: 10px; }
  @media (min-width: 1024px) {
    .lp-tracker { gap: 14px; padding: 14px; }
  }

  /* ── Generic section layout ──────────────────────────────────── */
  .lp-section { padding: 32px 0; background: var(--bg); }
  @media (min-width: 1024px) { .lp-section { padding: 48px 0; } }

  .lp-h2 {
    margin: 0 0 8px;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.02em;
    color: var(--navy-900);
  }
  @media (min-width: 1024px) { .lp-h2 { font-size: 32px; } }

  .lp-section__sub {
    margin: 0 0 24px;
    font-size: 16px;
    line-height: 1.55;
    color: var(--muted);
    max-width: 560px;
  }

  /* ── Proof strip (white cards overlapping the hero) ──────────── */
  .lp-strip { background: transparent; }
  .lp-strip .tr-stats { margin-top: -32px; }
  .lp-strip__empty {
    margin-top: -32px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 24px;
    background: var(--surface);
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    font-size: 16px;
    color: var(--muted);
    text-align: center;
  }
  .lp-strip__empty svg { flex: 0 0 auto; color: var(--faint); }
  .lp-strip__stats-wrap { background: var(--bg); }
  @media (min-width: 900px) {
    .lp-strip .tr-stats { grid-template-columns: repeat(5, 1fr); }
    .lp-strip .tr-stats.tr-stats--four { grid-template-columns: repeat(4, 1fr); }
  }
  @media (max-width: 899px) {
    .lp-strip .tr-stats { grid-template-columns: repeat(2, 1fr); }
    .lp-strip .tr-stat--wide { grid-column: 1 / -1; }
  }
  .tr-stat__value--tnum { font-variant-numeric: tabular-nums lining-nums; }

  /* ── Category tiles ──────────────────────────────────────────── */
  .lp-tiles {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  @media (min-width: 768px) { .lp-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; } }

  .lp-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 80px;
    padding: 12px 16px;
    background: var(--surface);
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    text-decoration: none;
    color: var(--text);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .lp-tile:hover { transform: translateY(-1px); box-shadow: var(--shadow-lift); border-color: var(--line); }
  .lp-tile:focus-visible { outline: none; box-shadow: var(--ring); }
  .lp-tile .cat-icon { width: 44px; height: 44px; }
  .lp-tile .cat-icon svg { width: 22px; height: 22px; }
  .lp-tile__label { font-size: 15px; font-weight: 600; line-height: 1.3; }

  /* ── Process steps (id="process") ────────────────────────────── */
  .lp-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
    position: relative;
  }
  .lp-step {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 0 0 24px;
  }
  .lp-step:last-child { padding-bottom: 8px; }
  .lp-step::before {
    content: '';
    position: absolute;
    top: 44px;
    bottom: 0;
    left: 21px;
    width: 2px;
    background: var(--line);
  }
  .lp-step:last-child::before { display: none; }

  .lp-step__marker {
    flex: 0 0 auto;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    font-weight: 800;
    color: var(--surface);
    font-variant-numeric: tabular-nums;
    position: relative;
    z-index: 1;
  }
  .lp-step--submitted   .lp-step__marker { background: var(--status-submitted-dot); }
  .lp-step--verified    .lp-step__marker { background: var(--status-review-dot); }
  .lp-step--assigned    .lp-step__marker { background: var(--status-review-dot); }
  .lp-step--in_progress .lp-step__marker { background: var(--status-progress-dot); }
  .lp-step--resolved    .lp-step__marker { background: var(--status-resolved-dot); }

  .lp-step__name {
    display: block;
    font-size: 17px;
    font-weight: 700;
    color: var(--navy-900);
    margin-bottom: 2px;
  }
  .lp-step__desc { margin: 0; font-size: 15px; line-height: 1.5; color: var(--muted); max-width: 460px; }

  @media (min-width: 1024px) {
    .lp-steps { flex-direction: row; align-items: stretch; gap: 0; }
    .lp-step { flex: 1 1 0; flex-direction: column; align-items: center; text-align: center; gap: 0; padding: 0 8px; }
    .lp-step::before {
      top: 21px;
      bottom: auto;
      left: 0;
      width: 100%;
      height: 2px;
    }
    .lp-step:first-child::before { display: none; }
    .lp-step__marker { margin-bottom: 12px; }
    .lp-step__name { font-size: 16px; }
    .lp-step__desc { font-size: 15px; }
    .lp-step__body { display: flex; flex-direction: column; align-items: center; }
  }

  .lp-process-foot { margin-top: 24px; }
  .lp-process-mobile { margin-top: 24px; max-width: 380px; margin-left: auto; margin-right: auto; }
  @media (min-width: 1024px) { .lp-process-mobile { display: none; } }

  /* ── Text links (navy, 44px target) ──────────────────────────── */
  .lp-textlink {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--navy-700);
    text-decoration: none;
    text-underline-offset: 3px;
  }
  .lp-textlink:hover { text-decoration: underline; }
  .lp-textlink:focus-visible { outline: none; box-shadow: var(--ring); border-radius: 4px; }

  /* ── Happening in Daet (status bar + rows) ────────────────────── */
  .lp-happening {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    margin-top: 8px;
  }
  @media (min-width: 1024px) { .lp-happening { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 32px; align-items: start; } }

  .lp-breakdown {
    display: flex;
    width: 100%;
    height: 14px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--line-soft);
    margin: 16px 0;
  }
  .lp-breakdown__seg { display: block; height: 100%; }
  .lp-breakdown__seg--submitted    { background: var(--status-submitted-dot); }
  .lp-breakdown__seg--under_review { background: var(--status-review-dot); }
  .lp-breakdown__seg--in_progress  { background: var(--status-progress-dot); }
  .lp-breakdown__seg--resolved     { background: var(--status-resolved-dot); }
  .lp-breakdown__seg--rejected     { background: var(--status-rejected-dot); }
  .lp-breakdown__seg--closed       { background: var(--status-closed-dot); }

  .lp-breakdown__legend { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 10px 16px; }
  .lp-breakdown__legend li { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: var(--muted); font-variant-numeric: tabular-nums; }
  .lp-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
  .lp-dot--submitted    { background: var(--status-submitted-dot); }
  .lp-dot--under_review { background: var(--status-review-dot); }
  .lp-dot--in_progress  { background: var(--status-progress-dot); }
  .lp-dot--resolved     { background: var(--status-resolved-dot); }
  .lp-dot--rejected     { background: var(--status-rejected-dot); }
  .lp-dot--closed       { background: var(--status-closed-dot); }

  .lp-report-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
  .lp-row {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 60px;
    padding: 8px 12px;
    background: var(--surface);
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    text-decoration: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .lp-row:hover { transform: translateY(-1px); box-shadow: var(--shadow-lift); }
  .lp-row:focus-visible { outline: none; box-shadow: var(--ring); }
  .lp-row__text { min-width: 0; flex: 1 1 auto; }
  .lp-row__title { display: block; font-size: 14px; font-weight: 600; line-height: 1.35; color: var(--text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .lp-row__meta { display: block; margin-top: 3px; font-size: 13px; color: var(--muted); overflow-wrap: anywhere; }
  @media (min-width: 768px) { .lp-row__title { font-size: 15px; } }

  /* ── Why you can trust it (id="features") + FAQ ──────────────── */
  .lp-trust { display: grid; grid-template-columns: 1fr; gap: 12px; }
  @media (min-width: 768px) { .lp-trust { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; } }

  .lp-trust-card {
    background: var(--surface);
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    padding: 16px;
  }
  .lp-trust-card__icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--navy-50);
    color: var(--navy-700);
    margin-bottom: 12px;
  }
  .lp-trust-card__title { margin: 0 0 6px; font-size: 17px; font-weight: 700; color: var(--navy-900); }
  .lp-trust-card__body { margin: 0; font-size: 15px; line-height: 1.55; color: var(--muted); }

  .lp-faq { margin-top: 32px; border: 1px solid var(--line-soft); border-radius: var(--radius-lg); background: var(--surface); box-shadow: var(--shadow-card); overflow: hidden; }
  .lp-faq__item + .lp-faq__item { border-top: 1px solid var(--line-soft); }
  .lp-faq__question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 48px;
    padding: 8px 16px;
    font-size: 16px;
    font-weight: 600;
    color: var(--navy-900);
    cursor: pointer;
    list-style: none;
  }
  .lp-faq__question::-webkit-details-marker { display: none; }
  .lp-faq__question:hover { background: var(--navy-50); }
  .lp-faq__question:focus-visible { outline: none; box-shadow: var(--ring-inset); }
  .lp-faq__icon { flex: 0 0 auto; color: var(--muted); transition: transform 0.18s ease; }
  .lp-faq__item[open] .lp-faq__icon { transform: rotate(45deg); }
  .lp-faq__answer { padding: 0 18px 16px; font-size: 16px; line-height: 1.6; color: var(--muted); border-top: none; }
  .lp-faq__answer p { margin: 0; }

  /* ── Final CTA band ──────────────────────────────────────────── */
  .lp-final {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 100%);
    padding: 48px 0;
  }
  .lp-final::before {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.035) 0 1px, transparent 1px 14px);
    pointer-events: none;
  }
  .lp-final__inner { position: relative; max-width: 1040px; margin: 0 auto; padding: 0 24px; text-align: center; }
  @media (max-width: 767px) { .lp-final__inner { padding: 0 16px; } }
  .lp-final__title { margin: 0 0 8px; font-size: 26px; font-weight: 800; line-height: 1.2; letter-spacing: -0.02em; color: var(--surface); }
  @media (min-width: 1024px) { .lp-final__title { font-size: 34px; } }
  .lp-final__sub { margin: 0 auto 24px; font-size: 16px; color: var(--navy-100); max-width: 480px; }
  .lp-final .lp-btn { min-width: 240px; }
  @media (max-width: 767px) { .lp-final .lp-btn { width: 100%; min-width: 0; } }

  /* ── Sticky mobile CTA bar (< 768px) ─────────────────────────── */
  .lp-sticky-cta {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--z-sticky-cta, 800);
    display: none;
    align-items: center;
    padding: 6px 12px calc(6px + env(safe-area-inset-bottom));
    background: var(--surface);
    border-top: 1px solid var(--line-soft);
    box-shadow: 0 -4px 24px -8px rgba(11, 26, 51, 0.22);
    transition: transform 0.22s ease, visibility 0.22s ease;
  }
  @media (max-width: 767px) { .lp-sticky-cta { display: flex; } }
  .lp-sticky-cta .lp-btn { width: 100%; min-height: 48px; }
  .lp-sticky-cta--hidden {
    transform: translateY(110%);
    visibility: hidden;
    pointer-events: none;
  }
  body.mobile-menu-open .lp-sticky-cta {
    transform: translateY(110%);
    visibility: hidden;
    pointer-events: none;
  }

  /* ── Bottom padding so the bar never covers the footer ───────── */
  @media (max-width: 767px) {
    .site-footer__inner { padding-bottom: calc(76px + env(safe-area-inset-bottom) + 12px); }
  }

  /* ── Motion ──────────────────────────────────────────────────── */
  @keyframes lp-fade-up {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .lp-fu { animation: lp-fade-up 0.2s ease both; }
  @media (prefers-reduced-motion: reduce) {
    .lp-fu, .lp-btn, .lp-tile, .lp-row, .lp-faq__icon, .lp-sticky-cta { animation: none !important; transition: none !important; }
    .lp-tile:hover, .lp-row:hover, .lp-btn:hover { transform: none; }
  }
</style>

<div class="lp-root">

  {{-- ════════════ HERO (navy gradient, no watermark, no serif) ════════════ --}}
  <header class="lp-hero">
    <div class="tr-container lp-hero__inner">
      <div class="lp-hero__copy">
        <h1 class="lp-h1 lp-fu">
          <span class="lp-h1__line">{{ __('landing.h1_line_1') }}</span>
          <span class="lp-h1__gold">{{ __('landing.h1_line_2') }}</span>
        </h1>

        <p class="lp-hero__sub lp-fu">{{ __('landing.subtitle') }}</p>

        <div class="lp-hero__ctas lp-fu">
          @auth
            <a href="{{ route($dashboardRoute) }}" class="lp-btn lp-btn--primary" data-sticky-anchor>
              {{ __('landing.go_to_dashboard') }}
            </a>
            <a href="{{ route('transparency') }}" wire:navigate class="lp-btn lp-btn--secondary">
              {{ __('landing.see_all_reports') }}
            </a>
          @else
            <a href="{{ route('register') }}" wire:navigate class="lp-btn lp-btn--primary" data-sticky-anchor>
              {{ __('landing.primary_cta') }}
            </a>
            <a href="{{ route('login') }}" wire:navigate class="lp-btn lp-btn--secondary">
              {{ __('landing.secondary_cta') }}
            </a>
          @endauth
        </div>

        <p class="lp-hero__reassurance lp-fu">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-3.58 8-10V5l-8-3-8 3v7c0 6.42 8 10 8 10z"/><polyline points="9 12 11.5 14.5 15 11"/></svg>
          {{ __('landing.reassurance') }}
        </p>
      </div>

      {{-- Desktop only: HTML/CSS phone frame with the sample tracker card --}}
      <div class="lp-phone" aria-hidden="true">
        <div class="lp-phone__screen">
          <div class="lp-phone__appbar">
            <img src="/images/lgulogo.webp" alt="" width="16" height="16" aria-hidden="true">
            <span>Daet Listens</span>
          </div>
          <div class="lp-phone__skeleton lp-phone__skeleton--w60"></div>
          @include('pages.home.partials.sample-tracker')
          <div class="lp-phone__skeleton lp-phone__skeleton--w80"></div>
        </div>
      </div>
    </div>
  </header>

  {{-- ════════════ LIVE PROOF STRIP ════════════ --}}
  <div class="lp-strip lp-strip__stats-wrap">
    <div class="tr-container">
      @if($stats['total'] === 0)
        <div class="lp-strip__empty" role="status">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          {{ __('landing.empty_public') }}
        </div>
      @else
        <div class="tr-stats{{ $stats['avgDays'] === null ? ' tr-stats--four' : '' }}" role="list" aria-label="{{ __('landing.stats_aria') }}">
          <div class="tr-stat" role="listitem">
            <span class="tr-stat__icon tr-stat__icon--navy">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </span>
            <span>
              <span class="tr-stat__value tr-stat__value--tnum">{{ $stats['total'] }}</span>
              <span class="tr-stat__label">{{ __('landing.stat_filed') }}</span>
            </span>
          </div>
          <div class="tr-stat" role="listitem">
            <span class="tr-stat__icon tr-stat__icon--green">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
            <span>
              <span class="tr-stat__value tr-stat__value--tnum">{{ $stats['resolved'] }}</span>
              <span class="tr-stat__label">{{ __('landing.stat_resolved') }}</span>
            </span>
          </div>
          <div class="tr-stat" role="listitem">
            <span class="tr-stat__icon tr-stat__icon--amber">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
            </span>
            <span>
              <span class="tr-stat__value tr-stat__value--tnum">{{ $stats['in_progress'] }}</span>
              <span class="tr-stat__label">{{ __('landing.stat_in_progress') }}</span>
            </span>
          </div>
          <div class="tr-stat" role="listitem">
            <span class="tr-stat__icon tr-stat__icon--sky">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M6 21V5l6-3 6 3v16"/><path d="M9 9h1"/><path d="M14 9h1"/><path d="M9 13h1"/><path d="M14 13h1"/><path d="M9 17h1"/><path d="M14 17h1"/></svg>
            </span>
            <span>
              <span class="tr-stat__value tr-stat__value--tnum">{{ $stats['offices'] }}</span>
              <span class="tr-stat__label">{{ __('landing.stat_offices') }}</span>
            </span>
          </div>
          @if($stats['avgDays'] !== null)
            <div class="tr-stat tr-stat--wide" role="listitem">
              <span class="tr-stat__icon tr-stat__icon--amber">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
              </span>
              <span>
                <span class="tr-stat__value tr-stat__value--tnum">{{ $stats['avgDays'] }}</span>
                <span class="tr-stat__label">{{ __('landing.stat_avg') }}</span>
              </span>
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>

  {{-- ════════════ WHAT CAN YOU REPORT? ════════════ --}}
  <section class="lp-section" aria-labelledby="lp-categories-heading">
    <div class="tr-container">
      <h2 class="lp-h2" id="lp-categories-heading">{{ __('landing.categories_title') }}</h2>
      <p class="lp-section__sub">{{ __('landing.categories_subtitle') }}</p>

      @php
        $tiles = [
            ['category' => 'road_damage', 'label' => __('landing.tile_road_damage')],
            ['category' => 'flooding', 'label' => __('landing.tile_flooding')],
            ['category' => 'streetlight', 'label' => __('landing.tile_streetlight')],
            ['category' => 'garbage', 'label' => __('landing.tile_garbage')],
            ['category' => 'noise_complaint', 'label' => __('landing.tile_noise')],
            ['category' => 'others', 'label' => __('landing.tile_others')],
        ];
        $tileHref = auth()->check() ? route('complaints.create') : route('register');
      @endphp
      <div class="lp-tiles">
        @foreach($tiles as $tile)
          <a class="lp-tile" href="{{ $tileHref }}" wire:navigate>
            @include('pages.transparency.partials.category-icon', ['category' => $tile['category']])
            <span class="lp-tile__label">{{ $tile['label'] }}</span>
          </a>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ════════════ WHAT HAPPENS AFTER YOU FILE (id="process") ════════════ --}}
  <section class="lp-section" id="process" style="background: var(--surface); border-top: 1px solid var(--line-soft); border-bottom: 1px solid var(--line-soft);" aria-labelledby="lp-process-heading">
    <div class="tr-container">
      <h2 class="lp-h2" id="lp-process-heading">{{ __('landing.process_title') }}</h2>
      <p class="lp-section__sub">{{ __('landing.process_subtitle') }}</p>

      @php
        $steps = [
            ['key' => 'submitted',   'name' => __('dashboard.steps.0'), 'body' => __('landing.step_1_body')],
            ['key' => 'verified',    'name' => __('dashboard.steps.1'), 'body' => __('landing.step_2_body')],
            ['key' => 'assigned',    'name' => __('dashboard.steps.2'), 'body' => __('landing.step_3_body')],
            ['key' => 'in_progress', 'name' => __('dashboard.steps.3'), 'body' => __('landing.step_4_body')],
            ['key' => 'resolved',    'name' => __('dashboard.steps.4'), 'body' => __('landing.step_5_body')],
        ];
      @endphp
      <ol class="lp-steps">
        @foreach($steps as $index => $step)
          <li class="lp-step lp-step--{{ $step['key'] }}">
            <span class="lp-step__marker" aria-hidden="true">{{ $index + 1 }}</span>
            <div class="lp-step__body">
              <span class="lp-step__name">{{ $step['name'] }}</span>
              <p class="lp-step__desc">{{ $step['body'] }}</p>
            </div>
          </li>
        @endforeach
      </ol>

      {{-- Mobile only: the same sample card the desktop hero shows --}}
      <div class="lp-process-mobile">
        @include('pages.home.partials.sample-tracker')
      </div>

      <div class="lp-process-foot">
        <a class="lp-textlink" href="{{ route('transparency') }}" wire:navigate>
          {{ __('landing.transparency_link') }}
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    </div>
  </section>

  {{-- ════════════ HAPPENING IN DAET (live) ════════════ --}}
  @if($stats['total'] > 0)
    <section class="lp-section" aria-labelledby="lp-live-heading">
      <div class="tr-container">
        <h2 class="lp-h2" id="lp-live-heading">{{ __('landing.live_title') }}</h2>

        <div class="lp-happening">
          <div>
            @if(count($statusBreakdown))
              <div class="lp-breakdown" role="img" aria-label="{{ __('landing.status_breakdown_aria') }}">
                @foreach($statusBreakdown as $segment)
                  <span class="lp-breakdown__seg lp-breakdown__seg--{{ $segment['key'] }}" style="width: {{ $segment['share'] }}%" title="{{ $segment['label'] }} · {{ $segment['count'] }}"></span>
                @endforeach
              </div>
              <ul class="lp-breakdown__legend">
                @foreach($statusBreakdown as $segment)
                  <li><span class="lp-dot lp-dot--{{ $segment['key'] }}" aria-hidden="true"></span>{{ $segment['label'] }} · {{ $segment['count'] }}</li>
                @endforeach
              </ul>
            @endif
          </div>

          <div>
            <ul class="lp-report-list">
              @foreach($recentReports as $report)
                <li>
                  <a class="lp-row" href="{{ $report['url'] }}" wire:navigate>
                    @include('pages.transparency.partials.category-icon', ['category' => $report['category']])
                    <span class="lp-row__text">
                      <span class="lp-row__title">{{ $report['title'] }}</span>
                      <span class="lp-row__meta">{{ $report['barangay_label'] }}@if($report['relative_date']) · {{ $report['relative_date'] }}@endif</span>
                    </span>
                    <span class="status-pill {{ $report['status_key'] }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $report['status_label'] }}</span>
                  </a>
                </li>
              @endforeach
            </ul>

            <div class="lp-process-foot">
              <a class="lp-textlink" href="{{ route('transparency') }}" wire:navigate>
                {{ __('landing.see_all_reports') }}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- ════════════ WHY YOU CAN TRUST IT (id="features") ════════════ --}}
  <section class="lp-section" id="features" style="background: var(--surface); border-top: 1px solid var(--line-soft);" aria-labelledby="lp-trust-heading">
    <div class="tr-container">
      <h2 class="lp-h2" id="lp-trust-heading">{{ __('landing.trust_title') }}</h2>

      <div class="lp-trust" style="margin-top: 16px;">
        @foreach([
            ['icon' => 'lock', 'title' => __('landing.private_title'), 'body' => __('landing.private_body')],
            ['icon' => 'pulse', 'title' => __('landing.tracked_title'), 'body' => __('landing.tracked_body')],
            ['icon' => 'megaphone', 'title' => __('landing.public_title'), 'body' => __('landing.public_body')],
        ] as $card)
          <div class="lp-trust-card">
            <span class="lp-trust-card__icon">
              @if($card['icon'] === 'lock')
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              @elseif($card['icon'] === 'pulse')
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              @else
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l18-5-5 18-2.5-7.5L3 11z"/></svg>
              @endif
            </span>
            <h3 class="lp-trust-card__title">{{ $card['title'] }}</h3>
            <p class="lp-trust-card__body">{{ $card['body'] }}</p>
          </div>
        @endforeach
      </div>

      {{-- FAQ — emergency question intentionally absent: no LGU-confirmed emergency number yet (see report). --}}
      <div class="lp-faq">
        @foreach([
            ['q' => __('landing.faq_1_q'), 'a' => __('landing.faq_1_a')],
            ['q' => __('landing.faq_2_q'), 'a' => __('landing.faq_2_a')],
            ['q' => __('landing.faq_3_q'), 'a' => __('landing.faq_3_a')],
            ['q' => __('landing.faq_5_q'), 'a' => __('landing.faq_5_a')],
        ] as $item)
          <details class="lp-faq__item">
            <summary class="lp-faq__question">
              {{ $item['q'] }}
              <svg class="lp-faq__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </summary>
            <div class="lp-faq__answer"><p>{{ $item['a'] }}</p></div>
          </details>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ════════════ FINAL CTA ════════════ --}}
  <section class="lp-final" aria-labelledby="lp-final-heading">
    <div class="lp-final__inner">
      <h2 class="lp-final__title" id="lp-final-heading">{{ __('landing.final_title') }}</h2>
      <p class="lp-final__sub">{{ __('landing.final_sub') }}</p>
      @auth
        <a href="{{ route($dashboardRoute) }}" class="lp-btn lp-btn--primary" data-sticky-anchor>{{ __('landing.go_to_dashboard') }}</a>
      @else
        <a href="{{ route('register') }}" wire:navigate class="lp-btn lp-btn--primary" data-sticky-anchor>{{ __('landing.primary_cta') }}</a>
      @endauth
    </div>
  </section>

  {{-- ════════════ STICKY MOBILE CTA (< 768px only) ════════════ --}}
  @guest
    <div class="lp-sticky-cta lp-sticky-cta--hidden">
      <a href="{{ route('register') }}" wire:navigate class="lp-btn lp-btn--primary">{{ __('landing.primary_cta') }}</a>
    </div>
  @endguest

</div>

@push('scripts')
<script>
(function () {
  var bar = document.querySelector('.lp-sticky-cta');
  if (!bar || !('IntersectionObserver' in window)) {
    if (bar) bar.classList.remove('lp-sticky-cta--hidden');
    return;
  }
  var heroAnchor = document.querySelector('.lp-hero [data-sticky-anchor]');
  var finalAnchor = document.querySelector('.lp-final [data-sticky-anchor]');
  var heroVisible = true;
  var finalVisible = false;
  var update = function () {
    bar.classList.toggle('lp-sticky-cta--hidden', heroVisible || finalVisible);
  };
  if (heroAnchor) {
    new IntersectionObserver(function (entries) {
      heroVisible = entries[0].isIntersecting;
      update();
    }, { threshold: 0 }).observe(heroAnchor);
  } else {
    heroVisible = false;
  }
  if (finalAnchor) {
    new IntersectionObserver(function (entries) {
      finalVisible = entries[0].isIntersecting;
      update();
    }, { threshold: 0 }).observe(finalAnchor);
  }
  update();
})();
</script>
@endpush
@endsection
