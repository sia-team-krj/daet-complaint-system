@extends('layouts.app')
@section('title', $complaint->ticket_id . ' — Staff View')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');
  :root {
    --navy: #0B1F3A;
    --navy-mid: #12294d;
    --gold: #C9A84C;
    --gold-light: #E2C06A;
    --gold-pale: rgba(201,168,76,0.12);
    --white: #ffffff;
    --text-dim: rgba(255,255,255,0.55);
    --border-gold: rgba(201,168,76,0.20);
    --red: #EF4444;
    --amber: #F59E0B;
    --green: #3FCB6F;
    --blue: #3B82F6;
  }

  .staff-root {
    min-height: calc(100svh - 64px);
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    position: relative;
    padding: 40px 0;
  }

  .decorations {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
  }
  .stripe-bg {
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px);
  }
  .gold-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }
  .glow-tr {
    position: absolute;
    top: -10%;
    right: -5%;
    width: 40vw;
    height: 40vw;
    max-width: 500px;
    max-height: 500px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.07) 0%, transparent 68%);
  }
  .glow-bl {
    position: absolute;
    bottom: -10%;
    left: -5%;
    width: 35vw;
    height: 35vw;
    max-width: 400px;
    max-height: 400px;
    background: radial-gradient(ellipse, rgba(26,53,96,0.6) 0%, transparent 68%);
  }
  .watermark {
    position: absolute;
    bottom: 2%;
    right: 3%;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(80px, 15vw, 200px);
    font-weight: 700;
    color: rgba(255,255,255,0.018);
    line-height: 1;
    user-select: none;
  }

  .staff-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    position: relative;
    z-index: 5;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fu { animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) both; }
  .d1 { animation-delay: 0.04s; }
  .d2 { animation-delay: 0.14s; }
  .d3 { animation-delay: 0.24s; }
  .d4 { animation-delay: 0.34s; }
  .d5 { animation-delay: 0.44s; }

  .breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
  }
  .breadcrumb a {
    color: var(--text-dim);
    text-decoration: none;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .breadcrumb a:hover { color: var(--gold); }
  .breadcrumb-gold { color: var(--gold); }
  .breadcrumb-current {
    font-family: 'Cormorant Garamond', serif;
    color: var(--gold);
    font-weight: 700;
    font-size: 14px;
  }
  .breadcrumb-sep { color: rgba(255,255,255,0.25); }

  .page-header {
    margin-bottom: 32px;
  }
  .ticket-display {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 5vw, 48px);
    font-weight: 700;
    color: var(--gold);
    margin-bottom: 12px;
  }
  .complaint-title-display {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(20px, 3vw, 28px);
    font-weight: 600;
    color: var(--white);
    margin-bottom: 16px;
  }

  .status-badge-lg {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 16px;
    border: 1px solid;
  }
  .status-submitted {
    background: rgba(59,130,246,0.12);
    border-color: rgba(59,130,246,0.3);
    color: var(--blue);
  }
  .status-in-progress {
    background: rgba(245,158,11,0.12);
    border-color: rgba(245,158,11,0.3);
    color: var(--amber);
  }
  .status-resolved {
    background: rgba(63,203,111,0.12);
    border-color: rgba(63,203,111,0.3);
    color: var(--green);
  }
  .status-rejected {
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.3);
    color: var(--red);
  }

  .meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    font-size: 13px;
    color: var(--text-dim);
    font-family: 'DM Sans', sans-serif;
  }
  .meta-item { display: flex; align-items: center; gap: 6px; }
  .meta-dot { color: rgba(255,255,255,0.25); }
  .meta-value { color: rgba(255,255,255,0.8); }

  .detail-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 28px;
  }

  .card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 6px;
    margin-bottom: 24px;
  }
  .card:last-child { margin-bottom: 0; }
  .card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 24px;
    border-bottom: 1px solid rgba(201,168,76,0.15);
  }
  .card-header-icon {
    width: 36px;
    height: 36px;
    background: var(--gold-pale);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
  }
  .card-header-icon svg { width: 18px; height: 18px; }
  .card-header-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 17px;
    font-weight: 700;
    color: var(--white);
  }
  .card-body { padding: 24px; }

  .citizen-row {
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .citizen-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--navy-mid);
    border: 1px solid var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 15px;
    font-weight: 700;
    color: var(--gold);
    flex-shrink: 0;
  }
  .citizen-info h4 {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 500;
    color: var(--white);
    margin-bottom: 4px;
  }
  .citizen-info p {
    font-size: 13px;
    color: var(--text-dim);
    margin: 0;
  }

  .description-text {
    font-size: 14px;
    line-height: 1.85;
    color: rgba(255,255,255,0.8);
    margin-bottom: 16px;
  }
  .category-chip {
    display: inline-block;
    padding: 6px 14px;
    background: var(--gold-pale);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 3px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--gold);
  }

  .map-container {
    height: 280px;
    border-radius: 0 0 6px 6px;
    overflow: hidden;
  }
  .map-placeholder {
    height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.03);
    color: var(--text-dim);
    gap: 12px;
  }
  .map-placeholder svg {
    width: 40px;
    height: 40px;
    opacity: 0.3;
    color: var(--gold);
  }

  .gold-marker {
    width: 14px;
    height: 14px;
    background: var(--gold);
    border-radius: 50%;
    border: 2px solid var(--white);
    position: relative;
  }
  .gold-marker::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 2px solid var(--gold);
    animation: pulse 2s ease-out infinite;
    opacity: 0;
  }
  @keyframes pulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(2.5); opacity: 0; }
  }

  .photo-wrap {
    border: 1px solid var(--border-gold);
    border-radius: 4px;
    overflow: hidden;
  }
  .photo-wrap img {
    width: 100%;
    display: block;
  }

  .form-group { margin-bottom: 20px; }
  .form-group:last-child { margin-bottom: 0; }
  .form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.6);
    margin-bottom: 10px;
  }
  .form-label .indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }
  .indicator-green { background: var(--green); }
  .indicator-amber { background: var(--amber); }
  .form-select, .form-textarea {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 4px;
    padding: 12px 16px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--white);
    outline: none;
    transition: all 0.2s;
  }
  .form-select:focus, .form-textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
  }
  .form-textarea { min-height: 120px; resize: vertical; }
  .form-select option { background: var(--navy); color: var(--white); }

  .form-hint {
    font-size: 12px;
    color: var(--text-dim);
    margin-top: 6px;
  }
  .form-error {
    font-size: 12px;
    color: var(--red);
    margin-top: 6px;
  }

  .internal-field .form-textarea {
    border-color: rgba(245,158,11,0.3);
  }
  .internal-field .form-textarea:focus {
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
  }
  .internal-field .form-label {
    color: var(--amber);
  }
  .internal-hint {
    font-size: 12px;
    color: var(--amber);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .btn-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 16px 28px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    border: none;
    border-radius: 4px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: var(--navy);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 20px rgba(201,168,76,0.25);
  }
  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(201,168,76,0.35);
  }

  .right-col {
    position: sticky;
    top: 88px;
    height: fit-content;
  }

  .timeline {
    position: relative;
    padding-left: 24px;
  }
  .timeline::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 4px;
    bottom: 4px;
    width: 2px;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.2));
  }
  .timeline-item {
    position: relative;
    padding-bottom: 24px;
  }
  .timeline-item:last-child { padding-bottom: 0; }
  .timeline-item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--gold);
    border: 2px solid var(--navy);
  }
  .timeline-item.internal::before {
    background: var(--amber);
    border-style: dashed;
  }

  .timeline-avatar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
  }
  .timeline-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--gold-pale);
    border: 1px solid var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--gold);
  }
  .timeline-actor-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--white);
  }
  .timeline-role-badge {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 8px;
    background: var(--gold-pale);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 3px;
    color: var(--gold);
  }

  .status-change-pill {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(201,168,76,0.1);
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 30px;
    font-size: 11px;
    color: var(--gold);
    margin-bottom: 8px;
  }

  .timeline-note {
    background: rgba(255,255,255,0.03);
    border-left: 2px solid var(--gold);
    padding: 12px 14px;
    border-radius: 0 4px 4px 0;
    font-size: 13px;
    line-height: 1.6;
    color: rgba(255,255,255,0.85);
    margin-bottom: 8px;
  }
  .timeline-item.internal .timeline-note {
    border-left-style: dashed;
    border-left-color: var(--amber);
    background: rgba(245,158,11,0.05);
  }

  .timeline-tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 3px 10px;
    border-radius: 3px;
    margin-right: 8px;
  }
  .tag-public {
    background: rgba(63,203,111,0.12);
    color: var(--green);
  }
  .tag-internal {
    background: rgba(245,158,11,0.12);
    color: var(--amber);
  }
  .timeline-time {
    font-size: 12px;
    color: var(--text-dim);
    margin-top: 4px;
  }

  .timeline-empty {
    text-align: center;
    padding: 40px 20px;
  }
  .timeline-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    opacity: 0.3;
    color: var(--gold);
  }
  .timeline-empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    color: var(--white);
    margin-bottom: 6px;
  }
  .timeline-empty-text {
    font-size: 13px;
    color: var(--text-dim);
  }

  .alert {
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .alert-success {
    background: rgba(63,203,111,0.1);
    border: 1px solid rgba(63,203,111,0.25);
    color: var(--green);
  }
  .alert-error {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    color: var(--red);
  }

  @media (max-width: 1100px) {
    .detail-grid { grid-template-columns: 1fr; }
    .right-col { position: static; }
  }
  @media (max-width: 768px) {
    .staff-container { padding: 0 24px; }
    .ticket-display { font-size: 32px; }
    .map-container, .map-placeholder { height: 220px; }
    .breadcrumb { flex-wrap: wrap; }
  }
</style>

<div class="staff-root">
  <div class="decorations">
    <div class="stripe-bg"></div>
    <div class="gold-bar"></div>
    <div class="glow-tr"></div>
    <div class="glow-bl"></div>
    <div class="watermark">DAET</div>
  </div>

  <div class="staff-container">
    {{-- Breadcrumb --}}
    <nav class="breadcrumb fu d1">
      <a href="{{ route('staff.dashboard') }}">
        <span style="color: var(--gold);">←</span>
        <span>Staff Dashboard</span>
      </a>
      <span class="breadcrumb-sep">·</span>
      <span class="breadcrumb-gold">{{ $complaint->department->name ?? 'Department' }}</span>
      <span class="breadcrumb-sep">·</span>
      <span class="breadcrumb-current">{{ $complaint->ticket_id }}</span>
    </nav>

    {{-- Flash Messages --}}
    @if(session('status'))
      <div class="alert alert-success fu d2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        {{ session('status') }}
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-error fu d2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ session('error') }}
      </div>
    @endif

    {{-- Page Header --}}
    @php
      $statusClass = match($complaint->status->value) {
        'Submitted' => 'status-submitted',
        'In Progress' => 'status-in-progress',
        'Resolved' => 'status-resolved',
        'Rejected' => 'status-rejected',
        default => 'status-submitted'
      };
      $daysOpen = $complaint->created_at->diffInDays(now());
    @endphp

    <header class="page-header fu d2">
      <div class="ticket-display">{{ $complaint->ticket_id }}</div>
      <h1 class="complaint-title-display">{{ $complaint->title }}</h1>
      <span class="status-badge-lg {{ $statusClass }}">{{ $complaint->status->label() }}</span>
      <div class="meta-row">
        <span class="meta-item">Filed on <span class="meta-value">{{ $complaint->created_at->format('F d, Y') }}</span></span>
        <span class="meta-dot">·</span>
        <span class="meta-item">{{ $complaint->category }}</span>
        <span class="meta-dot">·</span>
        <span class="meta-item">{{ $complaint->department->name ?? 'Unassigned' }}</span>
        <span class="meta-dot">·</span>
        <span class="meta-item">{{ $daysOpen }} days open</span>
      </div>
    </header>

    {{-- Two Column Grid --}}
    <div class="detail-grid">
      {{-- Left Column --}}
      <div class="fu d3">
        {{-- Citizen Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="card-header-title">Citizen Information</div>
          </div>
          <div class="card-body">
            <div class="citizen-row">
              <div class="citizen-avatar">{{ substr($complaint->user->full_name, 0, 1) }}</div>
              <div class="citizen-info">
                <h4>{{ $complaint->user->full_name }}</h4>
                <p>{{ $complaint->user->email }}</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Description Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="card-header-title">Complaint Description</div>
          </div>
          <div class="card-body">
            <div class="description-text">{{ $complaint->description }}</div>
            <span class="category-chip">{{ $complaint->category }}</span>
          </div>
        </div>

        {{-- Location Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="card-header-title">Complaint Location</div>
          </div>
          @if($complaint->latitude && $complaint->longitude)
            <div id="complaint-map" class="map-container"></div>
          @else
            <div class="map-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>No location provided</span>
            </div>
          @endif
        </div>

        {{-- Photo Card (conditional) --}}
        @if($complaint->image_path)
          <div class="card">
            <div class="card-header">
              <div class="card-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
              <div class="card-header-title">Attached Photo</div>
            </div>
            <div class="card-body">
              <a href="{{ Storage::url($complaint->image_path) }}" target="_blank" class="photo-wrap">
                <img src="{{ Storage::url($complaint->image_path) }}" alt="Complaint photo">
              </a>
            </div>
          </div>
        @endif
      </div>

      {{-- Right Column (Sticky) --}}
      <div class="right-col fu d4">
        {{-- Response Form Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div class="card-header-title">Add Response</div>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('staff.complaints.update', $complaint) }}">
              @csrf
              @method('PUT')

              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" {{ $complaint->status->value === $statusOption->value ? 'selected' : '' }}>
                      {{ $statusOption->label() }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <span class="indicator indicator-green"></span>
                  Public Response
                </label>
                <textarea name="note" class="form-textarea" placeholder="Write a response visible to the citizen..." required minlength="10">{{ old('note') }}</textarea>
                <div class="form-hint">Minimum 10 characters</div>
                @error('note')
                  <div class="form-error">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group internal-field">
                <label class="form-label">
                  <span class="indicator indicator-amber"></span>
                  🔒 Internal Note
                </label>
                <textarea name="internal_note" class="form-textarea" placeholder="Staff-only note, not visible to citizen...">{{ old('internal_note') }}</textarea>
                <div class="internal-hint">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  Not visible to citizen
                </div>
              </div>

              <button type="submit" class="btn-submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                Send Response & Update Status
              </button>
            </form>
          </div>
        </div>

        {{-- Activity Timeline Card --}}
        <div class="card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="card-header-title">Activity History</div>
          </div>
          <div class="card-body">
            @if($complaint->logs->isEmpty())
              <div class="timeline-empty">
                <svg class="timeline-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <div class="timeline-empty-title">No activity yet</div>
                <div class="timeline-empty-text">Be the first to respond to this complaint</div>
              </div>
            @else
              <div class="timeline">
                @foreach($complaint->logs->sortByDesc('created_at') as $log)
                  <div class="timeline-item {{ $log->is_internal ? 'internal' : '' }}">
                    <div class="timeline-avatar-row">
                      <div class="timeline-avatar">{{ substr($log->actor->full_name ?? 'S', 0, 1) }}</div>
                      <span class="timeline-actor-name">{{ $log->actor->full_name ?? 'System' }}</span>
                      <span class="timeline-role-badge">{{ $log->actor->role ?? 'staff' }}</span>
                    </div>

                    @if($log->previous_status && $log->new_status)
                      <span class="status-change-pill">{{ $log->previous_status }} → {{ $log->new_status }}</span>
                    @endif

                    @if($log->comment)
                      <div class="timeline-note">{{ $log->comment }}</div>
                    @endif

                    <span class="timeline-tag {{ $log->is_internal ? 'tag-internal' : 'tag-public' }}">
                      {{ $log->is_internal ? '🔒 Internal' : '✓ Public' }}
                    </span>
                    <div class="timeline-time">{{ $log->created_at->diffForHumans() }}</div>
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
@endsection
