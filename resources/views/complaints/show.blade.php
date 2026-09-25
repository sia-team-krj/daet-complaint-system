@extends($mainLayout)
@section('title', "Complaint {$complaint->ticket_id} — Daet Listens")
@section('content')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

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
    --border-navy: rgba(11,31,58,0.08);
    --border-gold: rgba(201,168,76,0.20);
  }

  *, *::before, *::after { box-sizing: border-box; }

  .show-root {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: calc(100svh - 64px);
    padding-top: 64px;
  }

  /* ── Breadcrumb ── */
  .breadcrumb-wrap {
    background: var(--navy);
    border-bottom: 1px solid rgba(201,168,76,0.15);
    padding: 16px 40px;
  }
  .breadcrumb {
    max-width: 1280px; margin: 0 auto;
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: rgba(255,255,255,0.55);
  }
  .breadcrumb a {
    color: var(--gold); text-decoration: none; transition: opacity 0.2s;
  }
  .breadcrumb a:hover { opacity: 0.8; }
  .breadcrumb-sep { opacity: 0.4; }
  .breadcrumb-current { color: rgba(255,255,255,0.85); }

  /* ── Page Header ── */
  .show-header {
    background: var(--navy);
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
  .show-header-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.1));
  }
  .show-header-glow {
    position: absolute; top: -30%; right: 10%;
    width: 30vw; height: 30vw; max-width: 400px; max-height: 400px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.06) 0%, transparent 68%);
    pointer-events: none;
  }
  .show-header-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
  }
  .show-ticket {
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px; font-weight: 600;
    color: var(--gold); letter-spacing: 0.1em;
    text-transform: uppercase; margin-bottom: 8px;
  }
  .show-title-wrap {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 20px; flex-wrap: wrap;
  }
  .show-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(24px, 3vw, 36px); font-weight: 700;
    color: var(--white); line-height: 1.15;
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

  .badge-submitted  { background: rgba(59,130,246,0.12); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
  .badge-review     { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
  .badge-progress   { background: rgba(139,92,246,0.12); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); }
  .badge-resolved   { background: rgba(63,203,111,0.15); color: #4ade80; border: 1px solid rgba(63,203,111,0.35); }
  .badge-rejected   { background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
  .badge-closed     { background: rgba(107,114,128,0.12); color: #9ca3af; border: 1px solid rgba(107,114,128,0.3); }

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
  .detail-card {
    background: var(--white);
    border: 1px solid var(--border-gold);
    border-radius: 8px; overflow: hidden;
  }
  .detail-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
    display: flex; align-items: center; gap: 10px;
  }
  .detail-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px; font-weight: 700;
    color: var(--navy);
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
    color: var(--text-muted); margin-bottom: 6px;
  }
  .detail-value {
    font-size: 14px; color: var(--text-body); line-height: 1.6;
  }
  .detail-value-large {
    font-size: 16px; line-height: 1.7;
  }
  .detail-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    background: var(--gold-pale); color: #92670a;
    padding: 5px 12px; border-radius: 20px;
    border: 1px solid rgba(201,168,76,0.25);
  }
  .detail-dept {
    font-size: 14px; font-weight: 600; color: var(--navy);
  }

  /* ── Image Gallery ── */
  .image-wrap {
    border-radius: 6px; overflow: hidden;
    border: 1px solid var(--border-gold);
    background: var(--cream-dark);
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
    max-height: 260px;
    object-fit: cover;
  }
  .no-image {
    padding: 40px; text-align: center;
    color: var(--text-muted); font-size: 13px;
  }

  /* ── Map ── */
  .map-container {
    height: 280px; border-radius: 6px;
    border: 1px solid var(--border-gold);
  }
  .no-location {
    padding: 60px 40px; text-align: center;
    color: var(--text-muted); font-size: 13px;
    background: var(--cream-dark); border-radius: 6px;
  }

  /* ── Timeline (Right Column) ── */
  .timeline-card {
    background: var(--white);
    border: 1px solid var(--border-gold);
    border-radius: 8px; overflow: hidden;
    position: sticky; top: 80px;
  }
  .timeline-header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
  }
  .timeline-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px; font-weight: 700; color: var(--navy);
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
    width: 3px; background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.2));
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
    background: var(--gold); border-radius: 50%;
    border: 2px solid var(--white);
    box-shadow: 0 0 0 2px var(--gold);
  }
  .timeline-status {
    font-size: 12px; font-weight: 700;
    color: var(--navy); margin-bottom: 4px;
  }
  .timeline-note {
    font-size: 13px; color: var(--text-body);
    line-height: 1.5; margin-bottom: 6px;
  }
  .timeline-meta {
    font-size: 11px; color: var(--text-muted);
  }
  .timeline-actor {
    font-weight: 600; color: var(--gold);
  }

  /* ── Back Button ── */
  .back-btn {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 600; color: var(--text-muted);
    text-decoration: none; margin-bottom: 20px;
    transition: color 0.2s;
  }
  .back-btn:hover { color: var(--navy); }

  /* Animations */
  @keyframes fadeUp { from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);} }
  .fu  { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1.2) both; }
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
    .timeline { padding-left: 20px; }
    .timeline::before { left: 0; }
    .timeline-item::before { left: -20px; margin-left: -3.5px; width: 9px; height: 9px; }
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
    <div class="show-header-bar"></div>
    <div class="show-header-glow"></div>
    <div class="show-header-inner">
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
      </div>
    </div>
  </div>

  {{-- ── Main Content ── --}}
  <div class="show-body">

    <a href="{{ route('complaints.index') }}" class="back-btn fu d2" wire:navigate>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
      Back to all complaints
    </a>

    <div class="show-layout">

      {{-- ── Left Column: Details ── --}}
      <div class="fu d3">

        {{-- Description --}}
        <div class="detail-card" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="detail-card-title">Description</span>
          </div>
          <div class="detail-card-body">
            <p class="detail-value detail-value-large">{{ $complaint->description }}</p>
          </div>
        </div>

        {{-- Details Grid --}}
        <div class="detail-card" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span class="detail-card-title">Details</span>
          </div>
          <div class="detail-card-body">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
              <div class="detail-field">
                <div class="detail-label">Category</div>
                <span class="detail-pill">{{ $complaint->category }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">Review status</div>
                <span class="detail-value">{{ $complaint->reviewStatusEnum->label() }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">Suggested priority</div>
                <span class="detail-value">{{ $complaint->suggestedPriorityEnum->label() }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">Confirmed priority</div>
                <span class="detail-value">{{ $complaint->confirmedPriorityEnum?->label() ?? 'Awaiting department review' }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">Department</div>
                <div class="detail-dept">{{ $complaint->department->name ?? 'Unassigned' }}</div>
                @if($complaint->department)
                  <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Code: {{ $complaint->department->code }}
                  </div>
                @endif
              </div>
              <div class="detail-field">
                <div class="detail-label">Date Filed</div>
                <div class="detail-value">{{ $complaint->created_at->format('F d, Y') }}</div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                  {{ $complaint->created_at->diffForHumans() }}
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Attached Photos --}}
        <div class="detail-card" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <span class="detail-card-title">Attached Photos @if($complaint->evidence_image_count > 1)<small>({{ $complaint->evidence_image_count }})</small>@endif</span>
          </div>
          <div class="detail-card-body" style="padding: 0;">
            @if($complaint->evidence_images)
              <div class="image-gallery">
                @foreach($complaint->evidence_images as $imageIndex => $imagePath)
                  @php
                    $imageUrl = route('complaints.evidence', [$complaint, $imageIndex]);
                  @endphp
                  <a class="image-wrap" href="{{ $imageUrl }}" target="_blank" rel="noopener">
                    <img src="{{ $imageUrl }}" alt="Complaint evidence photo {{ $imageIndex + 1 }}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span style="display: none; padding: 24px; text-align: center; color: var(--text-muted); font-size: 12px;">Image unavailable</span>
                  </a>
                @endforeach
              </div>
            @else
              <div class="no-image">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.5;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                <div>No photos attached to this complaint</div>
              </div>
            @endif
          </div>
        </div>

        {{-- Location Map --}}
        <div class="detail-card">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="detail-card-title">Location</span>
          </div>
          <div class="detail-card-body">
            @if($complaint->latitude && $complaint->longitude)
              <div id="complaint-map" class="map-container"></div>
              @if($complaint->address_text)
                <div style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
                  <strong>Address:</strong> {{ $complaint->address_text }}
                </div>
              @endif
              <div style="margin-top: 4px; font-size: 12px; color: var(--text-muted);">
                {{ number_format($complaint->latitude, 6) }}, {{ number_format($complaint->longitude, 6) }}
              </div>
            @else
              <div class="no-location">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.5;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <div>No location data available</div>
              </div>
            @endif
          </div>
        </div>

      </div>

      {{-- ── Right Column: Timeline ── --}}
      <div class="fu d4">
        <div class="timeline-card">
          <div class="timeline-header">
            <div class="timeline-title">Staff Responses</div>
          </div>
          <div class="timeline-body">
            @php
              $publicLogs = $complaint->logs->where('is_internal', false)->sortByDesc('created_at');
            @endphp
            @if($publicLogs->isEmpty())
              <div style="text-align: center; padding: 40px 20px; color: var(--text-muted); font-size: 13px;">
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
                    <div class="timeline-meta">
                      <span class="timeline-actor">Responded by {{ $complaint->department->name ?? 'LGU' }} Staff</span>
                      • {{ $log->created_at->diffForHumans() }}
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>

    </div>

  </div>

</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('complaint-map');
    if (!mapEl || typeof L === 'undefined') return;

    @if($complaint->latitude && $complaint->longitude)
      const lat = {{ $complaint->latitude }};
      const lng = {{ $complaint->longitude }};

      const map = L.map('complaint-map').setView([lat, lng], 15);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      L.marker([lat, lng]).addTo(map);
    @endif
  });
</script>
@endpush

@endsection
