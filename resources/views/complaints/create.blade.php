@extends($mainLayout)
@section('title', 'File a Complaint — Daet Listens')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');

  :root {
    --navy:        #0B1F3A;
    --navy-mid:    #12294d;
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
    --red:         #EF4444;
  }

  *, *::before, *::after { box-sizing: border-box; }

  .create-root {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: calc(100svh - 64px);
    padding-top: 64px;
  }

  /* ── Page Header ── */
  .create-header {
    background: var(--navy);
    position: relative; overflow: hidden;
    padding: 44px 40px 48px;
  }
  .create-header::before {
    content: ''; position: absolute; inset: 0;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
    pointer-events: none;
  }
  .create-header-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.1));
  }
  .create-header-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
  }
  .create-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 10px;
  }
  .create-eyebrow::before { content: ''; width: 20px; height: 1px; background: var(--gold); }
  .create-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(26px, 3.2vw, 40px); font-weight: 700;
    color: var(--white); line-height: 1.1; letter-spacing: -0.01em;
    margin-bottom: 8px;
  }
  .create-title span { color: var(--gold); font-style: italic; }
  .create-subtitle {
    font-size: 13px; color: rgba(255,255,255,0.42); font-weight: 300; line-height: 1.6;
  }

  /* ── Form Layout ── */
  .create-body {
    max-width: 1280px; margin: 0 auto;
    padding: 40px 40px 80px;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 32px;
    align-items: start;
  }

  /* ── Form Card ── */
  .form-card {
    background: var(--white);
    border: 1px solid var(--border-navy);
    border-radius: 8px; overflow: hidden;
  }
  .form-card-header {
    padding: 20px 28px 18px;
    border-bottom: 1px solid var(--border-navy);
    display: flex; align-items: center; gap: 12px;
  }
  .form-card-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: var(--gold-pale); border: 1px solid var(--border-gold);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold); flex-shrink: 0;
  }
  .form-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px; font-weight: 700; color: var(--navy);
  }
  .form-card-body { padding: 28px; }

  /* ── Field Styles ── */
  .field-group { margin-bottom: 22px; }
  .field-group:last-child { margin-bottom: 0; }
  .field-label {
    display: block; font-size: 10.5px; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--navy); margin-bottom: 8px; opacity: 0.7;
  }
  .field-label .req { color: var(--red); margin-left: 2px; }
  .field-hint { font-size: 11px; color: var(--text-muted); margin-bottom: 8px; font-weight: 300; }

  .field-input,
  .field-select,
  .field-textarea {
    width: 100%; background: #fafaf8;
    border: 1px solid var(--border-navy); border-radius: 4px;
    padding: 11px 14px;
    font-family: 'DM Sans', sans-serif; font-size: 13.5px; color: var(--navy);
    outline: none; transition: border-color 0.22s, background 0.22s, box-shadow 0.22s;
  }
  .field-input::placeholder,
  .field-textarea::placeholder { color: rgba(75,85,99,0.35); }
  .field-input:focus,
  .field-select:focus,
  .field-textarea:focus {
    border-color: rgba(201,168,76,0.55); background: var(--white);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.07);
  }
  .field-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; }
  .field-textarea { resize: vertical; min-height: 130px; line-height: 1.65; }
  .field-error-msg { font-size: 11.5px; color: var(--red); margin-top: 5px; }

  /* Input with icon */
  .field-wrap { position: relative; }
  .field-icon-left { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: rgba(107,114,128,0.5); pointer-events: none; }
  .field-wrap .field-input { padding-left: 38px; }
  .field-wrap:focus-within .field-icon-left { color: rgba(201,168,76,0.8); }

  /* Two-column row */
  .field-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

  /* ── Category Cards ── */
  .category-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }
  .category-option { position: relative; }
  .category-option input[type="radio"] {
    position: absolute; opacity: 0; width: 0; height: 0;
  }
  .category-label {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 14px 8px; border-radius: 6px;
    border: 1px solid var(--border-navy);
    background: #fafaf8; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    text-align: center;
  }
  .category-label:hover { border-color: rgba(201,168,76,0.4); background: #fdfaf4; }
  .category-option input:checked + .category-label {
    border-color: var(--gold); background: var(--gold-pale);
  }
  .category-icon { color: var(--text-muted); transition: color 0.2s; }
  .category-option input:checked + .category-label .category-icon { color: var(--gold); }
  .category-name { font-size: 11px; font-weight: 600; color: var(--text-body); letter-spacing: 0.04em; }

  /* ── Urgency selector ── */
  .urgency-row { display: flex; gap: 8px; flex-wrap: wrap; }
  .urgency-option { position: relative; }
  .urgency-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
  .urgency-label {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 4px;
    border: 1px solid var(--border-navy); background: #fafaf8;
    font-size: 12px; font-weight: 600; color: var(--text-muted);
    cursor: pointer; transition: all 0.18s;
    text-transform: uppercase; letter-spacing: 0.07em;
  }
  .urgency-dot { width: 7px; height: 7px; border-radius: 50%; }
  .urgency-option:nth-child(1) .urgency-dot { background: #6B7280; }
  .urgency-option:nth-child(2) .urgency-dot { background: #F59E0B; }
  .urgency-option:nth-child(3) .urgency-dot { background: #EF4444; }
  .urgency-option:nth-child(4) .urgency-dot { background: #7C3AED; }
  .urgency-option input:checked + .urgency-label { border-color: var(--gold); background: var(--gold-pale); color: var(--navy); }

  /* Department hint */
  .department-hint {
    margin-top: 12px;
    padding: 12px 14px;
    background: rgba(201,168,76,0.08);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 4px;
    font-size: 13px;
    color: rgba(11,31,58,0.8);
    animation: fadeUp 0.3s ease-out;
  }
  .department-hint strong {
    color: #0B1F3A;
  }

  /* ── Photo Upload ── */
  .upload-zone {
    border: 2px dashed var(--border-navy); border-radius: 6px;
    padding: 28px 20px; text-align: center; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    position: relative; background: #fafaf8;
  }
  .upload-zone:hover, .upload-zone.drag-over {
    border-color: rgba(201,168,76,0.5); background: var(--gold-pale);
  }
  .upload-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
  }
  .upload-icon { color: var(--gold); margin: 0 auto 10px; }
  .upload-title { font-size: 13px; font-weight: 600; color: var(--navy); margin-bottom: 4px; }
  .upload-sub { font-size: 11px; color: var(--text-muted); }
  .upload-preview { margin-top: 12px; display: none; }
  .upload-preview img { max-height: 160px; border-radius: 4px; border: 1px solid var(--border-navy); }

  /* ── Terms ── */
  .terms-row { display: flex; align-items: flex-start; gap: 10px; }
  .terms-row input[type="checkbox"] {
    appearance: none; width: 15px; height: 15px; flex-shrink: 0;
    border: 1px solid rgba(11,31,58,0.25); border-radius: 3px;
    background: #fafaf8; cursor: pointer; margin-top: 1px; position: relative;
    transition: background 0.15s, border-color 0.15s;
  }
  .terms-row input:checked { background: var(--gold); border-color: var(--gold); }
  .terms-row input:checked::after {
    content: ''; position: absolute; top: 1px; left: 4px;
    width: 4px; height: 8px;
    border: 1.5px solid var(--white); border-top: none; border-left: none; transform: rotate(45deg);
  }
  .terms-text { font-size: 12px; color: var(--text-muted); line-height: 1.65; }
  .terms-text a { color: var(--gold); text-decoration: none; font-weight: 500; }

  /* ── Submit Button ── */
  .btn-submit-complaint {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy); font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
    padding: 15px 32px; border-radius: 4px; border: none; cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 20px rgba(201,168,76,0.28);
  }
  .btn-submit-complaint:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(201,168,76,0.45); }
  .btn-submit-complaint:active { transform: none; }
  .btn-submit-complaint:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

  /* ── Sidebar ── */
  .sidebar-card {
    background: var(--white); border: 1px solid var(--border-navy);
    border-radius: 8px; overflow: hidden; margin-bottom: 20px;
  }
  .sidebar-card:last-child { margin-bottom: 0; }
  .sidebar-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border-navy);
    font-size: 10px; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--text-muted);
  }
  .sidebar-card-body { padding: 20px; }

  /* Department routing preview */
  .dept-route-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 0; border-bottom: 1px solid var(--border-navy);
    font-size: 12.5px; color: var(--text-body);
  }
  .dept-route-item:last-child { border-bottom: none; }
  .dept-code {
    font-family: 'Cormorant Garamond', serif;
    font-size: 13px; font-weight: 700; color: var(--navy);
    background: var(--gold-pale); border: 1px solid var(--border-gold);
    padding: 2px 8px; border-radius: 3px; flex-shrink: 0;
    min-width: 52px; text-align: center;
  }

  /* Tips list */
  .tip-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 8px 0; font-size: 12px; color: var(--text-muted); line-height: 1.6;
    border-bottom: 1px solid var(--border-navy);
  }
  .tip-item:last-child { border-bottom: none; }
  .tip-num {
    width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
    background: var(--gold-pale); border: 1px solid var(--border-gold);
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; font-weight: 700; color: #92670a;
  }

  /* Error banner */
  .error-banner {
    display: flex; align-items: flex-start; gap: 12px;
    background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);
    border-radius: 6px; padding: 14px 18px; margin-bottom: 24px;
    font-size: 12.5px; color: #991b1b; line-height: 1.65;
  }
  .error-banner svg { flex-shrink: 0; color: var(--red); margin-top: 1px; }

  /* Animations */
  @keyframes fadeUp { from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);} }
  .fu { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1.2) both; }
  .d1 { animation-delay: 0.04s; } .d2 { animation-delay: 0.14s; }
  .d3 { animation-delay: 0.24s; } .d4 { animation-delay: 0.34s; }

  /* Responsive */
  @media (max-width: 1100px) {
    .create-header { padding: 36px 32px 40px; }
    .create-body { padding: 32px 32px 64px; grid-template-columns: 1fr 300px; gap: 24px; }
    .category-grid { grid-template-columns: repeat(3, 1fr); }
  }
  @media (max-width: 900px) {
    .create-body { grid-template-columns: 1fr; }
    .create-sidebar { order: -1; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .create-sidebar .sidebar-card { margin-bottom: 0; }
  }
  @media (max-width: 768px) {
    .create-header { padding: 28px 20px 32px; }
    .create-body { padding: 24px 20px 56px; }
    .create-sidebar { grid-template-columns: 1fr; }
    .category-grid { grid-template-columns: repeat(2, 1fr); }
    .field-row-2 { grid-template-columns: 1fr; }
  }
  @media (max-width: 480px) {
    .urgency-row { flex-direction: column; }
    .urgency-label { width: 100%; justify-content: flex-start; }
    .category-grid { grid-template-columns: repeat(2, 1fr); }
  }
</style>

<div class="create-root">

  {{-- ── Page Header ── --}}
  <div class="create-header">
    <div class="create-header-bar"></div>
    <div class="create-header-inner">
      <div class="create-eyebrow fu d1">Citizen Services</div>
      <h1 class="create-title fu d2">File a <span>Complaint</span></h1>
      <p class="create-subtitle fu d3">
        Your complaint is routed directly to the responsible LGU department.
        Provide as much detail as possible for a faster resolution.
      </p>
    </div>
  </div>

  {{-- ── Body ── --}}
  <div class="create-body">

    {{-- ── Main Form Column ── --}}
    <div class="create-main">

      {{-- Validation errors --}}
      @if ($errors->any())
        <div class="error-banner fu d1">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div>
            <strong>Please fix the following:</strong>
            <ul style="margin: 6px 0 0; padding-left: 16px;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" id="complaint-form">
        @csrf

        {{-- ── Step 1: Category ── --}}
        <div class="form-card fu d2" style="margin-bottom: 20px;">
          <div class="form-card-header">
            <div class="form-card-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="form-card-title">Step 1 — Select Category</div>
          </div>
          <div class="form-card-body">
            <p class="field-hint">The category determines which department handles your complaint automatically.</p>
            
            <div class="field-group">
              <label class="field-label" for="category">Complaint Category <span class="req">*</span></label>
              <select id="category" name="category" class="field-select" required>
                <option value="">— Select a category —</option>
                <optgroup label="Engineering Office">
                  <option value="road_damage" {{ old('category') === 'road_damage' ? 'selected' : '' }}>Road Damage</option>
                  <option value="flooding" {{ old('category') === 'flooding' ? 'selected' : '' }}>Flooding / Drainage</option>
                  <option value="streetlight" {{ old('category') === 'streetlight' ? 'selected' : '' }}>Streetlight Issues</option>
                </optgroup>
                <optgroup label="General Services Office (GSO)">
                  <option value="garbage" {{ old('category') === 'garbage' ? 'selected' : '' }}>Garbage Collection</option>
                  <option value="sanitation" {{ old('category') === 'sanitation' ? 'selected' : '' }}>Sanitation</option>
                  <option value="park_maintenance" {{ old('category') === 'park_maintenance' ? 'selected' : '' }}>Park / Public Area Maintenance</option>
                  <option value="others" {{ old('category') === 'others' ? 'selected' : '' }}>Other Concerns</option>
                </optgroup>
                <optgroup label="Business Permits & Licensing">
                  <option value="business_permit" {{ old('category') === 'business_permit' ? 'selected' : '' }}>Business Permit Issues</option>
                </optgroup>
                <optgroup label="Peace & Order (PNP)">
                  <option value="noise_complaint" {{ old('category') === 'noise_complaint' ? 'selected' : '' }}>Noise Complaint</option>
                </optgroup>
                <optgroup label="Agriculture & Veterinary">
                  <option value="stray_animals" {{ old('category') === 'stray_animals' ? 'selected' : '' }}>Stray Animals</option>
                </optgroup>
              </select>
              
              <div id="department-hint" class="department-hint" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="2" style="vertical-align: middle; margin-right: 6px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span>This will be routed to: <strong id="department-name"></strong></span>
              </div>
              
              @error('category') <div class="field-error-msg">{{ $message }}</div> @enderror
            </div>
          </div>
        </div>

        {{-- ── Step 2: Details ── --}}
        <div class="form-card fu d3" style="margin-bottom: 20px;">
          <div class="form-card-header">
            <div class="form-card-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="form-card-title">Step 2 — Complaint Details</div>
          </div>
          <div class="form-card-body">

            <div class="field-group">
              <label class="field-label" for="title">
                Title <span class="req">*</span>
              </label>
              <p class="field-hint">A short, clear summary of the issue (max 255 characters).</p>
              <input type="text" id="title" name="title" class="field-input"
                placeholder="e.g. Pothole on Quezon Ave near Barangay Bagasbas"
                value="{{ old('title') }}" maxlength="255" required>
              @error('title') <div class="field-error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="field-group">
              <label class="field-label" for="description">
                Description <span class="req">*</span>
              </label>
              <p class="field-hint">Describe the issue in detail. Include location, duration, and any related incidents (minimum 20 characters).</p>
              <textarea id="description" name="description" class="field-textarea"
                placeholder="Describe the issue clearly. Include when you first noticed it, how it affects residents, and any other relevant details..."
                required minlength="20">{{ old('description') }}</textarea>
              @error('description') <div class="field-error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="field-row-2">
              <div class="field-group">
                <label class="field-label" for="urgency">
                  Urgency Level <span class="req">*</span>
                </label>
                <p class="field-hint">How urgently does this need attention?</p>
                <div class="urgency-row">
                  @foreach(['Low', 'Medium', 'High', 'Urgent'] as $level)
                    <div class="urgency-option">
                      <input type="radio" name="urgency" id="urg-{{ strtolower($level) }}"
                        value="{{ $level }}"
                        {{ old('urgency', 'Medium') === $level ? 'checked' : '' }}>
                      <label class="urgency-label" for="urg-{{ strtolower($level) }}">
                        <span class="urgency-dot"></span>
                        {{ $level }}
                      </label>
                    </div>
                  @endforeach
                </div>
                @error('urgency') <div class="field-error-msg">{{ $message }}</div> @enderror
              </div>
            </div>

          </div>
        </div>

        {{-- ── Step 3: Photo ── --}}
        <div class="form-card fu d4" style="margin-bottom: 20px;">
          <div class="form-card-header">
            <div class="form-card-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div class="form-card-title">Step 3 — Attach Photo <span style="font-weight:300; font-size:14px; color:var(--text-muted);">(Optional)</span></div>
          </div>
          <div class="form-card-body">
            <p class="field-hint">A photo helps the department understand the issue faster. Max 5MB. JPG, PNG, or WEBP.</p>
            <div class="upload-zone" id="upload-zone">
              <input type="file" name="image" id="image-input" accept="image/jpeg,image/png,image/webp">
              <div class="upload-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div class="upload-title">Click or drag to upload a photo</div>
              <div class="upload-sub">JPG, PNG, WEBP — max 5MB</div>
              <div class="upload-preview" id="upload-preview">
                <img id="preview-img" src="" alt="Preview">
              </div>
            </div>
            @error('image') <div class="field-error-msg" style="margin-top:8px;">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- ── Step 4: Location (Leaflet Map) ── --}}
        <div class="form-card fu d4" style="margin-bottom: 20px;">
          <div class="form-card-header">
            <div class="form-card-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="form-card-title">Step 4 — Location <span style="font-weight:300; font-size:14px; color:var(--text-muted);">(Optional)</span></div>
          </div>
          <div class="form-card-body">
            <p class="field-hint">Click on the map to mark the exact location of the issue. This helps departments find it faster.</p>
            
            {{-- Hidden inputs for map coordinates --}}
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
            
            <div class="field-group">
              <label class="field-label" for="address_text">Address / Landmark</label>
              <p class="field-hint">Describe the location or nearest landmark (e.g., "In front of Daet Public Market").</p>
              <input type="text" id="address_text" name="address_text" class="field-input"
                placeholder="Enter address or describe the location..."
                value="{{ old('address_text') }}" maxlength="500">
            </div>
            
            {{-- Leaflet Map Container --}}
            <div id="complaint-map" style="height: 320px; border-radius: 6px; border: 1px solid var(--border-navy); margin-top: 16px;"></div>
            <p class="field-hint" style="margin-top: 8px;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              Click anywhere on the map to place a marker. Drag the marker to adjust position.
            </p>
            
            @error('latitude') <div class="field-error-msg">{{ $message }}</div> @enderror
            @error('longitude') <div class="field-error-msg">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- ── Terms + Submit ── --}}
        <div class="form-card fu d4">
          <div class="form-card-body">
            <div class="field-group">
              <div class="terms-row">
                <input type="checkbox" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }} required>
                <label for="terms" class="terms-text">
                  I confirm that the information provided is accurate and truthful.
                  I understand that filing a false complaint is a violation of
                  <a href="#" target="_blank">LGU Daet's Complaint Policy</a> and may result in account suspension.
                </label>
              </div>
              @error('terms') <div class="field-error-msg">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn-submit-complaint" id="submit-btn">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Submit Complaint
            </button>
          </div>
        </div>

      </form>
    </div>

    {{-- ── Sidebar ── --}}
    <aside class="create-sidebar">

      {{-- Department Routing Guide --}}
      <div class="sidebar-card fu d2">
        <div class="sidebar-card-header">Department Routing</div>
        <div class="sidebar-card-body" style="padding: 8px 20px;">
          <div class="dept-route-item">
            <span class="dept-code">ENGR</span>
            <span>Roads, Drainage, Streetlights</span>
          </div>
          <div class="dept-route-item">
            <span class="dept-code">GSO</span>
            <span>Garbage, Sanitation, Parks, Other</span>
          </div>
          <div class="dept-route-item">
            <span class="dept-code">BPLS</span>
            <span>Business Permits</span>
          </div>
          <div class="dept-route-item">
            <span class="dept-code">PNP</span>
            <span>Noise Complaints, Peace & Order</span>
          </div>
          <div class="dept-route-item">
            <span class="dept-code">MAO</span>
            <span>Stray Animals, Agriculture</span>
          </div>
        </div>
      </div>

      {{-- Tips ── --}}
      <div class="sidebar-card fu d3">
        <div class="sidebar-card-header">Tips for a Strong Complaint</div>
        <div class="sidebar-card-body" style="padding: 8px 20px;">
          <div class="tip-item">
            <div class="tip-num">1</div>
            <div>Be specific about the exact location — include barangay and nearest landmark.</div>
          </div>
          <div class="tip-item">
            <div class="tip-num">2</div>
            <div>Attach a clear photo — it speeds up the department's on-site verification.</div>
          </div>
          <div class="tip-item">
            <div class="tip-num">3</div>
            <div>Describe how long the issue has existed and if it has caused harm.</div>
          </div>
          <div class="tip-item">
            <div class="tip-num">4</div>
            <div>Select the urgency that matches the actual risk — avoid inflating it unnecessarily.</div>
          </div>
        </div>
      </div>

    </aside>

  </div>
</div>

<script>
  // ── Image upload preview ──
  const input   = document.getElementById('image-input');
  const preview = document.getElementById('upload-preview');
  const img     = document.getElementById('preview-img');
  const zone    = document.getElementById('upload-zone');

  if (input) {
    input.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          img.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Drag-over visual feedback
  if (zone) {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', () => zone.classList.remove('drag-over'));
  }

  // ── Leaflet Map Initialization ──
  let map = null;
  let marker = null;

  function initComplaintMap() {
    try {
      console.log('Leaflet init starting...', { hasL: typeof L !== 'undefined', hasContainer: !!document.getElementById('complaint-map') });
      
      // Clean up existing map instance
      if (map) {
        map.remove();
        map = null;
        marker = null;
      }

      const el = document.getElementById('complaint-map');
      if (!el) {
        console.warn('Map container #complaint-map not found - skipping init');
        return;
      }
      
      if (typeof L === 'undefined') {
        console.error('Leaflet (L) is not loaded. Check if CDN scripts are loading.');
        el.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Map failed to load. Please refresh the page.</div>';
        return;
      }

      // Get input elements fresh each time (for Livewire navigation)
      const latInput = document.getElementById('latitude');
      const lngInput = document.getElementById('longitude');
      const addressInput = document.getElementById('address_text');

      // Default center: Daet, Camarines Norte (as specified)
      const defaultCenter = [14.1153, 122.9553];
      const defaultZoom = 14;

      map = L.map('complaint-map').setView(defaultCenter, defaultZoom);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap contributors'
      }).addTo(map);

      console.log('Leaflet map initialized successfully');

      // Custom gold pulsing pin icon
      const goldPinIcon = L.divIcon({
        className: 'gold-pin-marker',
        html: '<div class="pin-head"></div><div class="pin-pulse"></div>',
        iconSize: [30, 42],
        iconAnchor: [15, 42],
        popupAnchor: [0, -42]
      });

      // Check if we have old values to restore marker
      const oldLat = parseFloat(latInput?.value);
      const oldLng = parseFloat(lngInput?.value);
      if (oldLat && oldLng && !isNaN(oldLat) && !isNaN(oldLng)) {
        marker = L.marker([oldLat, oldLng], { icon: goldPinIcon, draggable: true }).addTo(map);
        map.setView([oldLat, oldLng], 16);
        setupMarkerEvents(marker, latInput, lngInput, addressInput);
        console.log('Restored marker from saved values:', oldLat, oldLng);
      }

      // Click on map to place/move marker
      map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        
        if (marker) {
          marker.setLatLng([lat, lng]);
        } else {
          marker = L.marker([lat, lng], { icon: goldPinIcon, draggable: true }).addTo(map);
          setupMarkerEvents(marker, latInput, lngInput, addressInput);
        }
        
        updateCoordinates(lat, lng, latInput, lngInput, addressInput);
        console.log('Map clicked, marker placed at:', lat, lng);
      });

    } catch (err) {
      console.error('Error initializing map:', err);
    }
  }

  function setupMarkerEvents(m, latInput, lngInput, addressInput) {
    m.on('dragend', function() {
      const pos = m.getLatLng();
      updateCoordinates(pos.lat, pos.lng, latInput, lngInput, addressInput);
      console.log('Marker dragged to:', pos.lat, pos.lng);
    });
  }

  function updateCoordinates(lat, lng, latInput, lngInput, addressInput) {
    if (latInput) latInput.value = lat.toFixed(8);
    if (lngInput) lngInput.value = lng.toFixed(8);
    
    // Auto-fill address_text if empty
    if (addressInput && !addressInput.value) {
      addressInput.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    }
    
    console.log('Coordinates updated:', lat.toFixed(8), lng.toFixed(8));
  }

  // Initialize on page load (use livewire:navigated for both initial load and navigation)
  document.addEventListener('livewire:navigated', function() {
    console.log('Livewire navigated, reinitializing map...');
    initComplaintMap();
  });

  // Also try immediate init for non-Livewire page loads
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      // Small delay to ensure Livewire has rendered
      setTimeout(initComplaintMap, 100);
    });
  } else {
    // DOM already loaded - delay for Livewire
    setTimeout(initComplaintMap, 100);
  }

  // ── Department Routing Hint ──
  const categorySelect = document.getElementById('category');
  const departmentHint = document.getElementById('department-hint');
  const departmentName = document.getElementById('department-name');

  // Category → Department mapping (matches PHP DepartmentRouter)
  const categoryToDept = {
    'road_damage': 'Engineering Office',
    'flooding': 'Engineering Office',
    'streetlight': 'Engineering Office',
    'garbage': 'General Services Office (GSO)',
    'sanitation': 'General Services Office (GSO)',
    'park_maintenance': 'General Services Office (GSO)',
    'others': 'General Services Office (GSO)',
    'business_permit': 'Business Permits & Licensing',
    'noise_complaint': 'Peace & Order (PNP)',
    'stray_animals': 'Agriculture & Veterinary',
  };

  if (categorySelect && departmentHint && departmentName) {
    categorySelect.addEventListener('change', function() {
      const category = this.value;
      if (category && categoryToDept[category]) {
        departmentName.textContent = categoryToDept[category];
        departmentHint.style.display = 'block';
      } else {
        departmentHint.style.display = 'none';
      }
    });

    // Trigger on page load if category is pre-selected
    if (categorySelect.value && categoryToDept[categorySelect.value]) {
      departmentName.textContent = categoryToDept[categorySelect.value];
      departmentHint.style.display = 'block';
    }
  }

  // ── Submit loading state ──
  const form      = document.getElementById('complaint-form');
  const submitBtn = document.getElementById('submit-btn');

  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.disabled = true;
      submitBtn.innerHTML = `
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/></svg>
        Submitting...
      `;
    });
  }
</script>

<style>
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  
  /* Gold pulsing location pin marker */
  .gold-pin-marker {
    position: relative;
    width: 30px;
    height: 42px;
  }
  .gold-pin-marker .pin-head {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #C9A84C, #E2C06A);
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    z-index: 2;
  }
  .gold-pin-marker .pin-head::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 6px;
    height: 6px;
    background: #0B1F3A;
    border-radius: 50%;
  }
  .gold-pin-marker .pin-pulse {
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: rgba(201, 168, 76, 0.4);
    border-radius: 50%;
    animation: pinPulse 1.5s ease-out infinite;
    z-index: 1;
  }
  @keyframes pinPulse {
    0% { transform: translateX(-50%) scale(0.5); opacity: 1; }
    100% { transform: translateX(-50%) scale(1.5); opacity: 0; }
  }
  
  /* Leaflet marker override to remove default icon */
  .leaflet-marker-icon.gold-pin-marker {
    background: transparent !important;
    border: none !important;
  }
</style>

@endsection