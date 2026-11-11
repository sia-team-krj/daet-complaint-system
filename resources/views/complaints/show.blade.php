@extends($mainLayout)
@section('title', __('complaint_show.page.title', ['ticket' => $complaint->ticket_id]))
@section('body-class', 'page-complaint-show')
@section('content')


<style>
  *, *::before, *::after { box-sizing: border-box; }

  .show-root {
    font-family: var(--font-sans);
    background: var(--bg);
    min-height: calc(100svh - var(--navbar-height));
  }

  /* ── Breadcrumb ── */
  .breadcrumb-wrap {
    background: var(--navy-900);
    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
    padding: 16px 40px;
  }
  .breadcrumb {
    max-width: 1280px; margin: 0 auto;
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: rgba(255,255,255,0.62);
  }
  .breadcrumb a {
    color: var(--gold-300); text-decoration: none; transition: opacity 0.2s;
  }
  .breadcrumb a:hover { opacity: 0.8; }
  .breadcrumb-sep { opacity: 0.4; }
  .breadcrumb-current { color: rgba(255,255,255,0.85); }

  /* ── Page Header ── */
  .show-header {
    background:
      repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.035) 0 1px, transparent 1px 14px),
      linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 62%, var(--navy-600) 100%);
    position: relative;
    overflow: hidden;
    padding: 32px 40px 40px;
  }
  .show-header::before {
    content: '';
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
    pointer-events: none;
  }
  .show-header-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
  }
  .show-ticket {
    font-family: var(--font-sans);
    font-size: 14px; font-weight: 600;
    color: var(--gold-300); letter-spacing: 0.1em;
    text-transform: uppercase; margin-bottom: 8px;
  }
  .show-title-wrap {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 20px; flex-wrap: wrap;
  }
  .show-title {
    font-family: var(--font-sans);
    font-size: clamp(24px, 3vw, 36px); font-weight: 700;
    color: var(--surface); line-height: 1.15;
    flex: 1;
  }
  .show-status-large {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 700; letter-spacing: 0.1em;
    text-transform: uppercase; padding: 8px 16px; border-radius: 6px;
    white-space: nowrap; flex-shrink: 0;
  }
  .show-status-large::before {
    content: ''; width: 8px; height: 8px; border-radius: 50%;
    background: currentColor; opacity: 0.7;
  }

  .badge-submitted  { background: var(--status-submitted-bg); color: var(--status-submitted-text); border: 1px solid var(--status-submitted-dot); }
  .badge-review     { background: var(--status-progress-bg); color: var(--status-progress-text); border: 1px solid var(--status-progress-dot); }
  .badge-progress   { background: var(--cat-noise-bg); color: var(--cat-noise-fg); border: 1px solid var(--cat-noise-fg); }
  .badge-resolved   { background: var(--status-resolved-bg); color: var(--status-resolved-text); border: 1px solid var(--status-resolved-dot); }
  .badge-rejected   { background: var(--status-rejected-bg); color: var(--status-rejected-text); border: 1px solid var(--status-rejected-dot); }
  .badge-closed     { background: var(--status-closed-bg); color: var(--status-closed-text); border: 1px solid var(--status-closed-dot); }

  /* ── Main Content ── */
  .show-body {
    max-width: 1280px; margin: 0 auto;
    padding: 32px 40px 64px;
  }
  .show-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 28px;
  }

  /* ── Cards ── */
  /* Light surface: this page reads as cream/white, unlike the navy operator
     workspaces, so this block follows the page's own card language. */
  .info-request {
    max-width: 1280px;
    margin: 0 auto 24px;
    padding: 24px;
    background: var(--status-progress-bg);
    border: 1.5px solid rgba(180,83,9,0.4);
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-card);
  }
  .info-request__head { display:flex; align-items:center; gap:12px; }
  .info-request__icon {
    flex: 0 0 auto; width: 44px; height: 44px;
    border-radius: var(--radius-sm);
    background: var(--navy-900); color: var(--surface);
    display: inline-flex; align-items: center; justify-content: center;
  }
  .info-request__icon svg { width: 22px; height: 22px; }
  .info-request__head-copy { min-width: 0; }
  .info-request__kicker {
    margin: 0 0 2px;
    font-size: 10px; font-weight: 800;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--status-progress-text);
  }
  .info-request__lede {
    margin: 6px 0 14px;
    font-size: 13.5px; line-height: 1.55;
    color: var(--navy-900);
  }
  .info-request__title {
    margin: 0;
    font-family: var(--font-sans);
    font-size: 20px; font-weight: 700;
    color: var(--navy-900);
  }
  .info-request__question {
    margin: 0 0 18px;
    padding: 16px 18px;
    background: var(--surface);
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-sm);
  }
  .info-request__asked-by {
    margin: 0 0 6px;
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.09em; text-transform: uppercase;
    color: var(--status-progress-text);
  }
  .info-request__question p:last-child {
    margin: 0;
    font-size: 14px; line-height: 1.65;
    color: var(--navy-900);
  }
  .info-request__thanks {
    margin: 0;
    font-size: 13.5px; line-height: 1.6;
    color: var(--status-resolved-text);
  }
  .info-request__answer {
    margin: 0 0 16px;
    padding: 16px;
    background: var(--surface);
    border: 1px solid var(--line-soft);
    border-radius: 6px;
  }
  .info-request__answer-label {
    margin: 0 0 6px;
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.09em; text-transform: uppercase;
    color: var(--muted);
  }
  .info-request__answer-text {
    margin: 0;
    font-size: 14px; line-height: 1.65;
    color: var(--navy-900);
    overflow-wrap: anywhere;
  }
  .info-request__form {
    display: grid; gap: 16px;
  }
  .info-request__field { display: grid; gap: 7px; }
  .info-request__label {
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.09em; text-transform: uppercase;
    color: var(--muted);
  }
  .info-request__label small {
    font-size: 9px; font-weight: 400; letter-spacing: 0; text-transform: none;
  }
  .info-request__field textarea,
  .info-request__field input[type="file"] {
    width: 100%;
    padding: 11px 13px;
    font-family: inherit; font-size: 13.5px;
    color: var(--navy-900);
    background: var(--surface);
    border: 1px solid rgba(201,168,76,0.45);
    border-radius: 6px;
  }
  .info-request__field textarea { resize: vertical; min-height: 92px; }
  .info-request__field textarea:focus,
  .info-request__field input[type="file"]:focus {
    outline: none;
    border-color: var(--status-progress-dot);
    box-shadow: 0 0 0 3px rgba(180,83,9,0.12);
  }
  .info-request__hint {
    margin: 0;
    font-size: 11.5px; line-height: 1.5;
    color: var(--muted);
  }
  .info-request__error {
    margin: 0;
    font-size: 11.5px; line-height: 1.45;
    color: var(--status-rejected-text);
  }
  .info-request__submit {
    justify-self: start;
    min-height: 42px;
    padding: 12px 22px;
    font-family: inherit; font-size: 12px; font-weight: 700;
    letter-spacing: 0.05em; text-transform: uppercase;
    color: var(--navy-900);
    background: var(--gold-500); color: var(--navy-900);
    border: 0; border-radius: 6px;
    cursor: pointer;
  }
  .info-request__submit:hover { background: var(--gold-300); }
  .info-request__submit:disabled { cursor: not-allowed; opacity: 0.55; }

  .close-report {
    margin-bottom: 16px; padding: 20px 22px;
    border-radius: var(--radius-sm);
    background: var(--status-resolved-bg);
    border: 1.5px solid color-mix(in srgb, var(--status-resolved-dot) 45%, transparent);
    box-shadow: var(--shadow-card);
  }
  .close-report__head { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 10px; }
  .close-report__icon {
    width: 44px; height: 44px; border-radius: 10px; flex: 0 0 auto;
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--navy-900); color: var(--gold-300);
  }
  .close-report__icon svg { width: 22px; height: 22px; }
  .close-report__kicker {
    margin: 0 0 2px; font-size: 10px; font-weight: 800;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--status-resolved-text);
  }
  .close-report__title { margin: 0; font-size: 20px; font-weight: 700; color: var(--navy-900); }
  .close-report__lede { margin: 0 0 14px; font-size: 13.5px; line-height: 1.55; color: var(--navy-900); }
  .close-report__form { margin: 0; }
  .close-report__submit {
    display: inline-flex; align-items: center; justify-content: center; min-height: 44px;
    padding: 0 18px; border: 0; border-radius: 8px; cursor: pointer;
    font-family: inherit; font-size: 13px; font-weight: 700;
    background: var(--gold-500); color: var(--navy-900);
  }
  .close-report__submit:hover { background: var(--gold-300); }
  .close-report__submit:focus-visible { outline: none; box-shadow: var(--ring); }

  .detail-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px; overflow: hidden;
  }
  .detail-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
    display: flex; align-items: center; gap: 10px;
  }
  .detail-card-title {
    font-family: var(--font-sans);
    font-size: 18px; font-weight: 700;
    color: var(--navy-900);
  }
  .detail-card-body {
    padding: 24px;
  }

  /* ── Detail Fields ── */
  .detail-field { margin-bottom: 24px; }
  .detail-field:last-child { margin-bottom: 0; }
  .detail-label {
    font-size: 11px; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 6px;
  }
  .detail-value {
    font-size: 14px; color: var(--muted); line-height: 1.6;
    overflow-wrap: anywhere;
  }
  .detail-value-large {
    font-size: 16px; line-height: 1.7;
  }
  .detail-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    background: var(--status-progress-bg); color: var(--gold-700);
    padding: 5px 12px; border-radius: 20px;
    border: 1px solid rgba(201,168,76,0.25);
  }
  .detail-dept {
    font-size: 14px; font-weight: 600; color: var(--navy-900);
  }
  .details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
  }
  .show-layout > * { min-width: 0; }

  /* ── Resident visibility control ──
     The complaint page is a light surface. The shared visibility-panel styles
     were written for the dark operator workspaces, so keep their structure but
     re-paint this instance with the page's light card language. */
  .show-root .visibility-panel {
    margin: 0 0 24px;
    padding: 20px 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
  }
  .show-root .visibility-panel__title {
    color: var(--navy-900);
    font-family: var(--font-sans);
    font-size: 18px;
  }
  .show-root .visibility-panel__lede {
    margin-top: 7px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
  }
  .show-root .visibility-panel__actions {
    align-items: stretch;
    flex-direction: column;
    gap: 10px;
    margin-top: 16px;
  }
  .show-root .visibility-panel__button {
    width: 100%;
    min-height: 44px;
    padding: 12px 18px;
    color: var(--navy-900);
    font-size: 11.5px;
    line-height: 1.35;
  }
  .show-root .visibility-panel__state {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.55;
  }
  .show-root .visibility-moderation {
    color: var(--muted);
    background: var(--status-progress-bg);
    border-color: rgba(201,168,76,0.32);
    font-size: 12px;
  }
  .show-root .visibility-moderation svg { color: var(--status-progress-text); }
  .show-root .visibility-moderation strong { color: var(--navy-900); font-size: 12.5px; }
  .show-root .visibility-moderation p { color: var(--muted); }
  .show-root .visibility-moderation em { color: var(--navy-900); }

  /* ── Image Gallery ── */
  .image-wrap {
    border-radius: 6px; overflow: hidden;
    border: 1px solid var(--line);
    background: var(--line-soft);
  }
  .image-wrap img {
    width: 100%; height: auto; display: block;
    max-height: 400px; object-fit: contain;
  }
  .image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    padding: 16px;
  }
  .image-gallery .image-wrap {
    min-width: 0;
  }
  .image-gallery .image-wrap img {
    max-height: 320px;
    object-fit: contain;
    background: var(--line-soft);
  }
  .no-image {
    padding: 40px; text-align: center;
    color: var(--muted); font-size: 13px;
  }

  /* ── Map ── */
  .map-container {
    height: 280px; border-radius: 6px;
    border: 1px solid var(--line);
  }
  .no-location {
    padding: 60px 40px; text-align: center;
    color: var(--muted); font-size: 13px;
    background: var(--line-soft); border-radius: 6px;
  }

  /* ── Timeline (now full-width at top on all screens) ── */
  .timeline-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px; overflow: hidden;
  }

  /* ── Section separation ── */
  .updates-top {
    margin-bottom: 28px;
    border-bottom: 1px solid var(--line-soft);
    padding-bottom: 24px;
  }
  .section-divider {
    margin: 24px 0;
    border: none;
    border-top: 1px solid var(--line-soft);
  }

  /* ── Detail cards: light surface with hairline border ── */
  .detail-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px; overflow: hidden;
  }
  .detail-card + .detail-card { margin-top: 16px; }

  .detail-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
    display: flex; align-items: center; gap: 10px;
  }
  .detail-card-title {
    font-family: var(--font-sans);
    font-size: 18px; font-weight: 700;
    color: var(--navy-900);
  }
  .detail-card-body {
    padding: 24px;
  }
  .timeline-header {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
  }
  .timeline-title {
    font-family: var(--font-sans);
    font-size: 18px; font-weight: 700; color: var(--navy-900);
  }
  .timeline-body {
    padding: 24px;
  }
  .timeline {
    position: relative;
    padding-left: 24px;
  }
  .timeline::before {
    content: '';
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: linear-gradient(180deg, var(--gold-500), rgba(201,168,76,0.2));
    border-radius: 3px;
  }
  .timeline-item {
    position: relative; padding-bottom: 28px;
  }
  .timeline-item:last-child { padding-bottom: 0; }
  .timeline-item::before {
    content: '';
    position: absolute; left: -24px; top: 4px;
    width: 11px; height: 11px; margin-left: -4px;
    background: var(--gold-500); border-radius: 50%;
    border: 2px solid var(--surface);
    box-shadow: 0 0 0 2px var(--gold-500);
  }
  .timeline-status {
    font-size: 12px; font-weight: 700;
    color: var(--navy-900); margin-bottom: 4px;
  }
  .timeline-note {
    font-size: 13px; color: var(--muted);
    line-height: 1.5; margin-bottom: 6px;
    overflow-wrap: anywhere;
  }
  .timeline-meta {
    display: flex; flex-wrap: wrap; align-items: center;
    gap: 2px 8px;
    font-size: 11.5px; color: var(--muted);
    line-height: 1.5;
  }
  .timeline-actor {
    font-weight: 600; color: var(--navy-900);
  }
  .timeline-time::before {
    content: '•'; margin-right: 8px; color: var(--gold-300);
  }
  .timeline-evidence {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 8px;
    margin: 10px 0 8px;
  }
  .timeline-evidence a {
    display: block;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 4px;
    background: var(--line-soft);
  }
  .timeline-evidence img {
    display: block;
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
  }

  /* ── Back Button ── */
  .back-btn {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 600; color: var(--muted);
    text-decoration: none; margin-bottom: 20px;
    transition: color 0.2s;
  }
  .back-btn:hover { color: var(--navy-900); }

  /* Animations */
  @keyframes fadeUp { from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);} }
  .fu  { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1) both; }
  .d1  { animation-delay: 0.04s; } .d2 { animation-delay: 0.14s; }
  .d3  { animation-delay: 0.24s; } .d4 { animation-delay: 0.34s; }

  /* Responsive */
  @media (max-width: 1100px) {
    .breadcrumb-wrap { padding: 16px 32px; }
    .show-header { padding: 28px 32px 36px; }
    .show-body { padding: 28px 32px 56px; }
    .show-layout { grid-template-columns: 1fr; }
    .timeline-card { position: static; }
  }
  @media (max-width: 768px) {
    .breadcrumb-wrap { padding: 14px 20px; }
    .show-header { padding: 24px 20px 32px; }
    .show-body { padding: 24px 20px 48px; }
    .show-title-wrap { flex-direction: column; }
    .detail-card-header, .detail-card-body { padding: 16px 20px; }
    .show-root .visibility-panel { padding: 16px 20px; }
    .image-gallery { gap: 12px; padding: 12px; }
    .timeline { padding-left: 20px; }
    .timeline::before { left: 0; }
    .timeline-item::before { left: -20px; margin-left: -3.5px; width: 9px; height: 9px; }
    .timeline-meta { flex-direction: column; align-items: flex-start; gap: 1px; }
    .timeline-time::before { display: none; }
  }
  @media (max-width: 560px) {
    .details-grid { grid-template-columns: 1fr; gap: 16px; }
  }
</style>

<div class="show-root">

  {{-- ── Breadcrumb ── --}}
  <div class="breadcrumb-wrap">
    <nav class="breadcrumb fu d1">
      <a href="{{ route('complaints.index') }}" wire:navigate>My Complaints</a>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">{{ $complaint->ticket_id }}</span>
    </nav>
  </div>

  {{-- ── Page Header ── --}}
  <div class="show-header">
    <div class="show-header-inner">
      <a href="{{ route('complaints.index') }}" wire:navigate class="pcs-back pcs-mobile-only">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
        <span>{{ __('complaint_show.page.back') }}</span>
      </a>
      <div class="pcs-ref pcs-mobile-only">
        <span class="pcs-ref__label">{{ __('complaint_show.page.reference_label') }}</span>
        <span class="pcs-ref__row">
          <code class="pcs-ref__id" data-pcs-ticket>{{ $complaint->ticket_id }}</code>
          <button type="button" class="pcs-copy" data-pcs-copy aria-label="{{ __('complaint_show.page.copy') }}" title="{{ __('complaint_show.page.copy') }}">
            <svg data-pcs-copy-icon width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            <svg data-pcs-copied-icon width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><polyline points="20 6 9 17 4 12"/></svg>
          </button>
        </span>
        <span class="sr-only pcs-copy-status" role="status" aria-live="polite"></span>
      </div>
      <div class="show-ticket fu d2">Complaint Ticket</div>
      <div class="show-title-wrap">
        <h1 class="show-title fu d3">{{ $complaint->title }}</h1>
        @php
          $statusEnum = $complaint->status instanceof \App\Enums\ComplaintStatus
            ? $complaint->status
            : \App\Enums\ComplaintStatus::from($complaint->status);
        @endphp
        <span class="show-status-large {{ $statusEnum->badgeColor() }} fu d4">
          {{ $statusEnum->label() }}
        </span>
        <span class="pcs-visibility pcs-mobile-only pcs-visibility--{{ $complaint->is_public ? 'public' : 'private' }}">
          {{ $complaint->is_public ? __('my_complaints.visibility_public') : __('my_complaints.visibility_private') }}
        </span>
      </div>
      <p class="pcs-meta pcs-mobile-only">
        @if($complaint->department)
          {{ __('complaint_show.page.filed_with_office', ['date' => $complaint->created_at?->locale(app()->getLocale())->translatedFormat('M j, Y'), 'office' => $complaint->department->name]) }}
        @else
          {{ __('complaint_show.page.filed_only', ['date' => $complaint->created_at?->locale(app()->getLocale())->translatedFormat('M j, Y')]) }}
        @endif
      </p>
    </div>
  </div>

  {{-- ── Main Content ── --}}
  <div class="show-body">

    <a href="{{ route('complaints.index') }}" class="back-btn fu d2" wire:navigate>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
      Back to all complaints
    </a>

    @php
      $openRequest = $complaint->informationRequests()->whereIn('status', ['open', 'answered'])->latest('id')->first();
      $pcsPublicLogs = $complaint->logs->where('is_internal', false)->sortByDesc('created_at')->values();
      $pcsOffice = $complaint->department?->name ?? 'LGU';
      $needsInfo = (string) $complaint->review_status === 'needs_information';
      $reviewerNote = trim((string) $complaint->review_notes);
      $needsInfoQuestion = $openRequest?->question
          ?? ($reviewerNote !== '' ? $reviewerNote : __('complaint_show.action.fallback_question'));
      $showNeedsInfo = $openRequest !== null || $needsInfo;

      $pcsPolicy = $complaint->intakePolicy();
      $pcsShowPhotos = (($pcsPolicy['photos_required'] ?? false) || ($pcsPolicy['photos_allowed'] ?? false)) && $complaint->evidence_image_count > 0;
      $pcsShowLocation = $complaint->intakeLocationApplicable() || $complaint->latitude !== null || $complaint->longitude !== null || filled($complaint->barangay) || filled($complaint->address_text);
    @endphp

    @if($showNeedsInfo)
      <section class="info-request fu d2" aria-labelledby="info-request-heading">
        <div class="info-request__head">
          <span class="info-request__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </span>
          <div class="info-request__head-copy">
            <p class="info-request__kicker">{{ __('complaint_show.action.kicker') }}</p>
            <h2 id="info-request-heading" class="info-request__title">
              {{ $openRequest?->isAnswered() ? __('complaint_show.action.title_answered') : __('complaint_show.action.title_open') }}
            </h2>
          </div>
        </div>

        <p class="info-request__lede">{{ __('complaint_show.action.lede') }}</p>

        <blockquote class="info-request__question">
          <p class="info-request__asked-by">
            @if($openRequest?->requester?->full_name)
              {{ __('complaint_show.action.asked_by', ['name' => $openRequest->requester->full_name, 'time' => $openRequest->created_at->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}
            @elseif($openRequest)
              {{ __('complaint_show.action.asked_by_department', ['time' => $openRequest->created_at->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}
            @else
              {{ __('complaint_show.action.from_office') }}
            @endif
          </p>
          <p>{{ $needsInfoQuestion }}</p>
        </blockquote>

        @if($openRequest?->isAnswered())
          <div class="info-request__answer">
            <p class="info-request__answer-label">{{ __('complaint_show.action.reply_label') }}</p>
            <p class="info-request__answer-text">{{ $openRequest->response_text }}</p>
          </div>
        @else
          <form data-loading-submit method="POST" action="{{ route('complaints.information.store', $complaint) }}" enctype="multipart/form-data" class="info-request__form" data-disable-on-submit>
            @csrf
            <div class="info-request__field">
              <label for="response" class="info-request__label">{{ __('complaint_show.action.reply_label') }}</label>
              <textarea name="response" id="response" rows="4" required minlength="10" maxlength="2000" placeholder="{{ __('complaint_show.action.reply_placeholder') }}">{{ old('response') }}</textarea>
              @error('response')<p class="info-request__error">{{ $message }}</p>@enderror
            </div>

            <div class="info-request__field">
              <label for="images" class="info-request__label">{{ __('complaint_show.action.photos_label') }} <small>{{ __('complaint_show.action.photos_optional') }}</small></label>
              <input type="file" name="images[]" id="images" accept="image/jpeg,image/png,image/webp" multiple>
              <p class="info-request__hint">{{ __('complaint_show.action.photos_hint') }}</p>
              @error('images')<p class="info-request__error">{{ $message }}</p>@enderror
              @error('images.*')<p class="info-request__error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="info-request__submit">{{ __('complaint_show.action.submit') }}</button>
          </form>
        @endif
      </section>
    @endif

    @if($complaint->visibilityIsOwnedBy(auth()->user()) && $statusEnum === \App\Enums\ComplaintStatus::Resolved)
      <section class="close-report" aria-labelledby="close-report-heading">
        <div class="close-report__head">
          <span class="close-report__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </span>
          <div>
            <p class="close-report__kicker">{{ __('complaint_show.close.kicker') }}</p>
            <h2 class="close-report__title" id="close-report-heading">{{ __('complaint_show.close.title') }}</h2>
          </div>
        </div>
        <p class="close-report__lede">{{ __('complaint_show.close.lede') }}</p>
        <form data-loading-submit method="POST" action="{{ route('complaints.close', $complaint) }}" class="close-report__form">
          @csrf
          <button type="submit" class="close-report__submit">{{ __('complaint_show.close.submit') }}</button>
        </form>
      </section>
    @endif

    {{-- ── Updates / Timeline — moved to top on all screens ── --}}
    <section class="updates-top" aria-labelledby="updates-top-heading">
      <div class="timeline-card" id="pcs-updates">
        <h2 class="pcs-section-title pcs-mobile-only" id="updates-top-heading"><span class="pcs-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg></span>{{ __('complaint_show.updates.title') }}</h2>
        <div class="timeline-header">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
          <div class="timeline-title">{{ __('complaint_show.updates.staff_responses') }}</div>
        </div>
        <div class="timeline-body">
          @php
            $publicLogs = $complaint->logs->where('is_internal', false)->sortByDesc('created_at');
          @endphp
          @if($publicLogs->isEmpty())
            <div style="text-align: center; padding: 40px 20px; color: var(--muted); font-size: 13px;">
              Your complaint has been received and is being reviewed.
            </div>
          @else
            <div class="timeline">
              @foreach($publicLogs as $log)
                <div class="timeline-item">
                  <div class="timeline-status">
                    @if($log->previous_status && $log->new_status)
                      Status changed: {{ $log->previous_status }} → {{ $log->new_status }}
                    @elseif($log->new_status)
                      Status: {{ $log->new_status }}
                    @endif
                  </div>
                  @if($log->comment)
                    <div class="timeline-note">{{ $log->comment }}</div>
                  @endif

                  @if($log->documentation_images)
                    <div class="timeline-evidence" aria-label="Documentation photos">
                      @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                        <a href="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener">
                          <img src="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="Documentation photo {{ $documentationIndex + 1 }}" loading="lazy">
                        </a>
                      @endforeach
                    </div>
                  @endif

                  <div class="timeline-meta">
                    <span class="timeline-actor">Responded by {{ $complaint->department->name ?? 'LGU' }} Staff</span>
                    <span class="timeline-time">{{ $log->created_at->diffForHumans() }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>

    {{-- Latest-update inset removed: the progress timeline already shows the
         newest staff response, and a duplicate here read as a separate state. --}}

    {{-- Mobile: Progress tracker (5 steps, vertical). Dates reuse complaint
         created_at + status logs; missing dates render "—", never invented. --}}
    <section class="pcs-progress pcs-mobile-only" aria-labelledby="pcs-progress-heading">
      <h2 class="pcs-section-title" id="pcs-progress-heading"><span class="pcs-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></span>{{ __('complaint_show.progress.title') }}</h2>
      @php
        $pcsStatuses = [\App\Enums\ComplaintStatus::Submitted, \App\Enums\ComplaintStatus::UnderReview, \App\Enums\ComplaintStatus::InProgress, \App\Enums\ComplaintStatus::Resolved, \App\Enums\ComplaintStatus::Closed];
        $pcsStatusKey = \Illuminate\Support\Str::slug($statusEnum->value, '_');
        $pcsIsRejected = $statusEnum === \App\Enums\ComplaintStatus::Rejected;
        $pcsIsClosed = $statusEnum === \App\Enums\ComplaintStatus::Closed;
        $pcsCurrentIndex = null;
        if ($pcsIsClosed) {
          $pcsCurrentIndex = 5;
        } elseif ($pcsIsRejected) {
          $pcsRejectedLog = $pcsPublicLogs->reject(fn ($l) => $l->new_status === \App\Enums\ComplaintStatus::Rejected->value)->last();
          $pcsReached = $pcsRejectedLog?->new_status;
          $pcsCurrentIndex = $pcsReached ? (int) array_search($pcsReached, $pcsStatuses, true) : 0;
        } else {
          $idx = (int) array_search($statusEnum, $pcsStatuses, true);
          $pcsCurrentIndex = $idx !== false ? $idx : null;
        }
        $pcsLastDate = null;
        $pcsSteps = [];
        foreach ($pcsStatuses as $idx => $st) {
          $log = $pcsPublicLogs->first(fn ($e) => $e->new_status === $st->value);
          if ($pcsIsRejected) {
            $state = $idx < $pcsCurrentIndex ? 'complete' : ($idx === $pcsCurrentIndex ? 'rejected' : 'upcoming');
          } elseif ($pcsIsClosed) {
            $state = 'complete';
          } else {
            $state = $idx < $pcsCurrentIndex ? 'complete' : ($idx === $pcsCurrentIndex ? 'current' : 'upcoming');
          }
          $raw = $st === \App\Enums\ComplaintStatus::Submitted ? $complaint->created_at : $log?->created_at;
          $formatted = null;
          if ($raw !== null && ($pcsLastDate === null || $raw->greaterThanOrEqualTo($pcsLastDate))) {
            $formatted = $raw;
            $pcsLastDate = $raw;
          }
          $pcsSteps[] = ['status' => $st, 'label' => $st->label(), 'date' => $formatted, 'state' => $state];
        }
        $pcsWhat = match ($pcsStatusKey) {
          'submitted' => __('transparency.show.what_submitted'),
          'under_review' => __('transparency.show.what_under_review'),
          'in_progress' => __('transparency.show.what_in_progress', ['office' => $pcsOffice]),
          'resolved' => __('transparency.show.what_resolved'),
          'closed' => __('transparency.show.what_closed'),
          'rejected' => __('transparency.show.what_rejected'),
          default => '',
        };
        $pcsResolvedDate = null;
        $pcsClosedDate = null;
        foreach ($pcsSteps as $s) {
          if ($s['status'] === \App\Enums\ComplaintStatus::Resolved && $s['date']) $pcsResolvedDate = $s['date'];
          if ($s['status'] === \App\Enums\ComplaintStatus::Closed && $s['date']) $pcsClosedDate = $s['date'];
        }
      @endphp
      <ol class="pcs-tracker">
        @foreach($pcsSteps as $idx => $step)
          <li class="pcs-step pcs-step--{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif>
            <span class="pcs-step__marker" aria-hidden="true">
              @if($step['state'] === 'complete')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 10"/></svg>
              @elseif($step['state'] === 'rejected')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6L6 18"/></svg>
              @elseif($step['state'] === 'current')
              @else
                <span>{{ $idx + 1 }}</span>
              @endif
            </span>
            <div class="pcs-step__body">
              <div class="pcs-step__row">
                <span class="pcs-step__label">{{ $step['label'] }}</span>
                @if($step['state'] === 'upcoming')
                  <span class="pcs-step__date">{{ __('complaint_show.progress.pending') }}</span>
                @elseif($step['date'])
                  <span class="pcs-step__date">{{ $step['date']->locale(app()->getLocale())->translatedFormat('M j, Y') }}</span>
                @else
                  <span class="pcs-step__date">{{ __('complaint_show.progress.date_unavailable') }}</span>
                @endif
              </div>
              @if($step['state'] === 'complete')
                <span class="pcs-step__state"><span class="sr-only">{{ __('complaint_show.progress.completed') }}: </span></span>
              @elseif($step['state'] === 'current')
                <span class="pcs-step__state">{{ __('complaint_show.progress.current') }}</span>
                @if($pcsWhat !== '' && !$pcsIsRejected && !$pcsIsClosed)
                  <div class="pcs-next">
                    <h3 class="pcs-next__title">{{ __('complaint_show.progress.next_title') }}</h3>
                    <p class="pcs-next__body">{{ $pcsWhat }}</p>
                  </div>
                @endif
              @elseif($step['state'] === 'upcoming')
                <span class="pcs-step__state"><span class="sr-only">{{ __('complaint_show.progress.upcoming') }}</span></span>
              @elseif($step['state'] === 'rejected')
                <span class="pcs-step__state">{{ __('transparency.show.rejected_stage') }}</span>
                @if($pcsWhat !== '')
                  <div class="pcs-next">
                    <h3 class="pcs-next__title">{{ __('complaint_show.progress.next_title') }}</h3>
                    <p class="pcs-next__body">{{ $pcsWhat }}</p>
                  </div>
                @endif
              @endif
            </div>
          </li>
        @endforeach
      </ol>
      @if($pcsIsClosed)
        <p class="pcs-resolved-line">
          @if($pcsClosedDate)
            {{ __('complaint_show.progress.closed_on', ['date' => $pcsClosedDate->locale(app()->getLocale())->translatedFormat('M j, Y')]) }}
          @elseif($pcsResolvedDate)
            {{ __('complaint_show.progress.resolved_on', ['date' => $pcsResolvedDate->locale(app()->getLocale())->translatedFormat('M j, Y')]) }}
          @else
            {{ __('complaint_show.progress.closed_on', ['date' => __('complaint_show.progress.date_unavailable')]) }}
          @endif
        </p>
      @elseif($statusEnum === \App\Enums\ComplaintStatus::Resolved)
        <p class="pcs-resolved-line">
          @if($pcsResolvedDate)
            {{ __('complaint_show.progress.resolved_on', ['date' => $pcsResolvedDate->locale(app()->getLocale())->translatedFormat('M j, Y')]) }}
          @else
            {{ __('complaint_show.progress.resolved_on', ['date' => __('complaint_show.progress.date_unavailable')]) }}
          @endif
        </p>
      @endif
    </section>

{{--
    The resident's own control over publication, and an honest note when staff
    have moderated what the public sees.

    Two promises are kept here. First, the resident can change this at any time
    and no one else can. Second, nothing staff do to the public presentation
    rewrites what the resident filed: the copy below is their own, unedited,
    with a plain statement of what was withheld instead of a silent alteration.
--}}
<div class="visibility-panel">
  <div class="visibility-panel__head">
    <h3 class="visibility-panel__title">{{ __('complaint_show.visibility.title_legacy') }}</h3>
    <p class="visibility-panel__lede">{{ __('complaint_show.visibility.lede') }}</p>
  </div>
  <h2 class="pcs-section-title pcs-mobile-only"><span class="pcs-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></span>{{ __('complaint_show.visibility.title') }}</h2>

  <form data-loading-submit method="POST" action="{{ route('complaints.visibility.update', $complaint) }}" class="visibility-panel__actions">
    @csrf
    @method('PUT')
    <input type="hidden" name="visibility" value="{{ $complaint->is_public ? 'private' : 'public' }}">
    <button type="submit" class="visibility-panel__button">
      {{ $complaint->is_public ? __('complaint_show.visibility.privatise') : __('complaint_show.visibility.publish') }}
    </button>
    <span class="visibility-panel__state">
      {{ $complaint->is_public
          ? __('complaint_show.visibility.public_state')
          : __('complaint_show.visibility.private_state') }}
    </span>
  </form>
  <details class="pcs-how pcs-mobile-only">
    <summary>{{ __('complaint_show.visibility.how_it_works') }}</summary>
    <p class="pcs-how__body">{{ __('complaint_show.visibility.lede') }}</p>
  </details>

  @if ($complaint->isHiddenFromPublic() || $complaint->isRedacted())
    <div class="visibility-moderation" role="status">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
      <div>
        <strong>This report is not currently shown as you filed it.</strong>
        <p>
          What you see above is your original submission, unchanged.
          @if ($complaint->moderation_reason)
            Staff recorded the reason: <em>{{ $complaint->moderation_reason }}</em>.
          @else
            A staff member has withheld part of it from the public page.
          @endif
          Your choice of public or private is unaffected, and you can still change it using the button above.
        </p>
      </div>
    </div>
  @endif
</div>

    <div class="show-layout">

      {{-- ── Left Column: Details ── --}}
      <div class="fu d3">

        {{-- Description --}}
        <div class="detail-card pcs-card--description" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="detail-card-title">{{ __('complaint_show.report.description_label') }}</span>
          </div>
          <div class="detail-card-body">
            <h2 class="pcs-section-title pcs-mobile-only"><span class="pcs-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>{{ __('complaint_show.report.title') }}</h2>
            <p id="pcs-description" class="detail-value detail-value-large pcs-desc-wrap">{{ $complaint->description }}</p>
            <button type="button" class="pcs-show-more pcs-mobile-only" aria-expanded="false" aria-controls="pcs-description" data-pcs-desc-toggle>{{ __('complaint_show.report.show_more') }}</button>
          </div>
        </div>

        {{-- Details Grid --}}
        <div class="detail-card pcs-card--details" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span class="detail-card-title">{{ __('complaint_show.details.legacy_details') }}</span>
          </div>
          <div class="detail-card-body">
            <h2 class="pcs-section-title pcs-mobile-only"><span class="pcs-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></span>{{ __('complaint_show.details.title') }}</h2>
            <div class="details-grid">
              <div class="detail-field">
                <div class="detail-label">{{ __('complaint_show.details.category') }}</div>
                <span class="detail-pill">{{ \Illuminate\Support\Str::headline($complaint->category) }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">{{ __('complaint_show.details.review_status') }}</div>
                <span class="detail-value">{{ $complaint->reviewStatusEnum->label() }}</span>
              </div>
              <div class="detail-field detail-field--visibility">
                <div class="detail-label">{{ __('complaint_show.visibility.title_legacy') }}</div>
                <span class="detail-value">{{ $complaint->is_public ? __('complaint_show.details.published') : __('my_complaints.visibility_private') }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">{{ __('complaint_show.details.suggested_priority') }}</div>
                <span class="detail-value">{{ $complaint->suggestedPriorityEnum->label() }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">{{ __('complaint_show.details.confirmed_priority') }}</div>
                <span class="detail-value">{{ $complaint->confirmedPriorityEnum?->label() ?? __('complaint_show.details.awaiting') }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">{{ __('complaint_show.details.department') }}</div>
                <div class="detail-dept">@if($complaint->department)<span class="dept-indicator" data-dept="{{ $complaint->department->code }}"><span class="dept-dot" data-dept="{{ $complaint->department->code }}" aria-hidden="true"></span><span class="dept-indicator__name">{{ $complaint->department->name }}</span></span>@else {{ __('complaint_show.details.unassigned') }} @endif</div>
                @if($complaint->department)
                  <div class="pcs-detail-sub" style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                    {{ __('complaint_show.details.code_label') }}: {{ $complaint->department->code }}
                  </div>
                @endif
              </div>
              <div class="detail-field">
                <div class="detail-label">{{ __('complaint_show.details.date_filed') }}</div>
                <div class="detail-value">{{ $complaint->created_at->locale(app()->getLocale())->translatedFormat('F j, Y') }}</div>
                <div class="pcs-detail-sub" style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                  {{ $complaint->created_at->diffForHumans() }}
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      {{-- ── Right Column: Photos & Location (desktop, only if applicable) ── --}}
      @if($pcsShowPhotos || $pcsShowLocation)
      <div class="fu d4">
        {{-- Photos (only when category requires them or images exist) --}}
        @if($pcsShowPhotos)
        <div class="detail-card pcs-card--photos" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <span class="detail-card-title">{{ __('complaint_show.report.photos_label') }} @if($complaint->evidence_image_count > 1)<small>({{ $complaint->evidence_image_count }})</small>@endif</span>
          </div>
          <div class="detail-card-body" style="padding: 0;">
            <p class="pcs-counter pcs-mobile-only" data-pcs-counter aria-live="polite">{{ __('complaint_show.report.counter', ['current' => 1, 'total' => count($complaint->evidence_images)]) }}</p>
            <div class="image-gallery" data-count="{{ count($complaint->evidence_images) }}" data-pcs-gallery>
              @foreach($complaint->evidence_images as $imageIndex => $imagePath)
                @php
                  $imageUrl = route('complaints.evidence', [$complaint, $imageIndex]);
                @endphp
                <a class="image-wrap" href="{{ $imageUrl }}" target="_blank" rel="noopener" data-pcs-photo data-pcs-index="{{ $imageIndex }}" aria-label="{{ __('complaint_show.report.photo_alt', ['index' => $imageIndex + 1]) }}">
                  <img src="{{ $imageUrl }}" alt="{{ __('complaint_show.report.photo_alt', ['index' => $imageIndex + 1]) }}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <span style="display: none; padding: 24px; text-align: center; color: var(--muted); font-size: 12px;">Image unavailable</span>
                </a>
              @endforeach
            </div>
          </div>
        </div>
        @endif

        {{-- Location (only when category requires it or data exists) --}}
        @if($pcsShowLocation)
        <div class="detail-card pcs-card--location">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="detail-card-title">{{ __('complaint_show.location.title') }}</span>
          </div>
          <div class="detail-card-body">
            @if($complaint->barangay || $complaint->address_text)
              <p class="pcs-loc-text pcs-mobile-only">
                @if($complaint->barangay)
                  <span class="pcs-loc-text--barangay">{{ $complaint->barangay }}</span>
                @endif
                @if($complaint->barangay && $complaint->address_text)<br>@endif
                @if($complaint->address_text){{ $complaint->address_text }}@endif
              </p>
            @endif
            @if($complaint->latitude !== null && $complaint->longitude !== null)
              <div id="complaint-map" class="map-container"></div>
              @if($complaint->address_text)
                <div class="pcs-address-desktop" style="margin-top: 12px; font-size: 13px; color: var(--muted);">
                  <strong>{{ __('complaint_show.location.address_label') }}:</strong> {{ $complaint->address_text }}
                </div>
              @endif
              <p class="pcs-coords" style="margin-top: 4px; font-size: 12px; color: var(--muted);">
                {{ number_format($complaint->latitude, 6) }}, {{ number_format($complaint->longitude, 6) }}
              </p>
              <a class="pcs-maps-link pcs-mobile-only" href="https://www.openstreetmap.org/?mlat={{ $complaint->latitude }}&amp;mlon={{ $complaint->longitude }}#map=17/{{ $complaint->latitude }}/{{ $complaint->longitude }}" target="_blank" rel="noopener">{{ __('complaint_show.location.open_in_maps') }}</a>
            @else
              <div class="no-location">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.5;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <div>{{ __('complaint_show.location.no_location') }}</div>
              </div>
            @endif
          </div>
        </div>
        @endif
      </div>
      @endif

    </div>

    {{-- Mobile: Help inset (contact text reused from the footer setting) --}}
    <section class="pcs-help pcs-mobile-only" aria-labelledby="pcs-help-heading">
      <div class="pcs-inset pcs-inset--help">
        <h2 class="pcs-section-title" id="pcs-help-heading" style="font-size: 16px;"><span class="pcs-title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg></span>{{ __('complaint_show.help.title') }}</h2>
        <p class="pcs-help__body">{{ \App\Models\SystemSetting::getValue('contact_text', 'For concerns, contact the Municipality of Daet through the Transparency Register or visit the municipal hall.') }}</p>
        <a class="pcs-help__link" href="{{ route('transparency') }}" wire:navigate>{{ __('footer.transparency_register') }}</a>
      </div>
    </section>

  </div>

  {{-- Mobile: photo viewer (dialog). Desktop keeps target=_blank links. --}}
  <dialog id="pcs-viewer" class="pcs-viewer pcs-mobile-only" aria-label="{{ __('complaint_show.report.viewer_label') }}">
    <div class="pcs-viewer__bar">
      <button type="button" class="pcs-viewer__close" data-pcs-viewer-close aria-label="{{ __('complaint_show.report.viewer_close') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>
    <figure class="pcs-viewer__figure">
      <img class="pcs-viewer__img" alt="" data-pcs-viewer-img>
    </figure>
  </dialog>

</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {

    /*
     * Keep the map honest about its own size.
     *
     * Leaflet measures the container once, when the map is built, and caches
     * that measurement. It is never wrong on a desktop, because a desktop
     * viewport has settled by the time the map is built. A phone has not: the
     * URL bar collapses, scrollbars come and go, a late webfont reflows the card
     * around the map, the keyboard opens. Any of those after the measurement
     * leaves the tiles laid out for a size the container no longer is, which
     * shows as a blank band or as map content clipped to a strip.
     *
     * invalidateSize() re-reads the container. Called on the next frame and once
     * more after a short delay to catch late layout, then on every resize and
     * rotation. `animate: false` because a re-measure is not a user gesture and
     * should not zoom the map at them.
     */
    const keepMapSized = (target) => {
      if (!target) return;

      const refresh = () => target.invalidateSize({ animate: false });

      window.requestAnimationFrame(refresh);
      window.setTimeout(refresh, 300);
      window.addEventListener('resize', refresh);
      window.addEventListener('orientationchange', () => {
        window.setTimeout(refresh, 150);
      });
    };

    // ── Mobile (≤768px) civic-tech interactions ──
    const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

    // Copy ticket (clipboard.writeText only + polite status)
    (function () {
      const btn = document.querySelector('[data-pcs-copy]');
      const idEl = document.querySelector('[data-pcs-ticket]');
      const status = document.querySelector('.pcs-copy-status');
      if (!btn || !idEl) return;
      const copyIcon = btn.querySelector('[data-pcs-copy-icon]');
      const copiedIcon = btn.querySelector('[data-pcs-copied-icon]');
      const copyLabel = @json(__('complaint_show.page.copy'));
      const copiedLabel = @json(__('complaint_show.page.copied'));
      btn.addEventListener('click', async () => {
        const text = idEl.textContent.trim();
        try { await navigator.clipboard.writeText(text); } catch (e) {}
        btn.setAttribute('aria-label', copiedLabel);
        btn.setAttribute('title', copiedLabel);
        if (copyIcon) copyIcon.hidden = true;
        if (copiedIcon) copiedIcon.hidden = false;
        if (status) status.textContent = copiedLabel;
        window.setTimeout(() => {
          btn.setAttribute('aria-label', copyLabel);
          btn.setAttribute('title', copyLabel);
          if (copyIcon) copyIcon.hidden = false;
          if (copiedIcon) copiedIcon.hidden = true;
          if (status) status.textContent = '';
        }, 2000);
      });
    })();

    // Description show more/less
    (function () {
      const btn = document.querySelector('[data-pcs-desc-toggle]');
      const desc = document.getElementById('pcs-description');
      if (!btn || !desc) return;
      const more = btn.textContent;
      btn.addEventListener('click', () => {
        const expanded = desc.classList.toggle('is-expanded');
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        btn.textContent = expanded ? @json(__('complaint_show.report.show_less')) : @json(__('complaint_show.report.show_more'));
      });
    })();

    // Gallery counter + viewer (mobile only; desktop keeps target=_blank)
    (function () {
      const gallery = document.querySelector('[data-pcs-gallery]');
      const counter = document.querySelector('[data-pcs-counter]');
      const viewer = document.getElementById('pcs-viewer');
      const viewerImg = viewer ? viewer.querySelector('[data-pcs-viewer-img]') : null;
      const viewerClose = viewer ? viewer.querySelector('[data-pcs-viewer-close]') : null;
      if (gallery && counter) {
        const items = Array.from(gallery.querySelectorAll('[data-pcs-photo]'));
        const total = items.length;
        const update = () => {
          if (!total) return;
          const rect = gallery.getBoundingClientRect();
          let idx = 0;
          items.forEach((it, i) => {
            const r = it.getBoundingClientRect();
            if (Math.abs(r.left - rect.left) < 24) idx = i;
          });
          counter.textContent = @json(__('complaint_show.report.counter', ['current' => 1, 'total' => 1])).replace(':current', String(idx + 1)).replace(':total', String(total));
        };
        gallery.addEventListener('scroll', () => { window.requestAnimationFrame(update); }, { passive: true });
        update();
      }
      if (viewer && viewerImg) {
        let lastTrigger = null;
        gallery && gallery.addEventListener('click', (e) => {
          const link = e.target.closest('[data-pcs-photo]');
          if (!link || !isMobile()) return;
          e.preventDefault();
          lastTrigger = link;
          viewerImg.src = link.getAttribute('href');
          viewerImg.alt = link.querySelector('img') ? link.querySelector('img').getAttribute('alt') || '' : '';
          if (!viewer.open) viewer.showModal();
          if (viewerClose) viewerClose.focus();
        });
        const closeViewer = () => { if (viewer.open) viewer.close(); if (lastTrigger) lastTrigger.focus(); };
        if (viewerClose) viewerClose.addEventListener('click', closeViewer);
        viewer.addEventListener('click', (e) => { if (e.target === viewer) closeViewer(); });
        viewer.addEventListener('close', () => { viewerImg.removeAttribute('src'); });
      }
    })();

    const mapEl = document.getElementById('complaint-map');
    if (!mapEl) return;

    // Leaflet is bundled rather than fetched from a CDN, but a bundled module is
    // deferred and this inline script is not, so the map is built from a callback
    // the bundle calls instead of by reaching for window.L and finding it
    // undefined on a page that is otherwise fine.
    window.whenLeafletReady(() => {

    @if($complaint->latitude !== null && $complaint->longitude !== null)
      const lat = {{ $complaint->latitude }};
      const lng = {{ $complaint->longitude }};

      const map = L.map('complaint-map').setView([lat, lng], 15);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      L.marker([lat, lng]).addTo(map);

      keepMapSized(map);
      if ('IntersectionObserver' in window) {
        new IntersectionObserver((entries) => {
          entries.forEach((en) => { if (en.isIntersecting) try { map.invalidateSize({ animate: false }); } catch (e) {} });
        }, { root: null, threshold: 0.1 }).observe(mapEl);
      }
    @endif
    }, {
        // The coordinates are printed underneath the map regardless, so the
        // work can still be done. Saying so is what stops a blank box being
        // read as "my location was not saved".
        onUnavailable: () => {
          if (mapEl) mapEl.innerHTML = @json('<div style="padding:20px;text-align:center;color:var(--muted);font-size:12px">' . __('complaint_show.location.map_unavailable') . '</div>');
        },
    });
  });
</script>
@endpush

@endsection
