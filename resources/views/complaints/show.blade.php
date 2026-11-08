@extends($mainLayout)
@section('title', "Complaint {$complaint->ticket_id} - Daet Listens")
@section('content')


<style>
  *, *::before, *::after { box-sizing: border-box; }

  .show-root {
    font-family: var(--font-sans);
    background: var(--bg);
    min-height: calc(100svh - var(--navbar-height));
    padding-top: var(--navbar-height);
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
    border: 1px solid rgba(180,83,9,0.35);
    border-radius: 8px;
  }
  .info-request__title {
    margin: 0 0 14px;
    font-family: var(--font-sans);
    font-size: 20px; font-weight: 700;
    color: var(--navy-900);
  }
  .info-request__question {
    margin: 0 0 18px;
    padding: 16px 18px;
    background: var(--status-progress-bg);
    border: 1px solid rgba(201,168,76,0.28);
    border-radius: 6px;
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

  /* ── Timeline (Right Column) ── */
  .timeline-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px; overflow: hidden;
    position: sticky; top: 80px;
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

    @php $openRequest = $complaint->informationRequests()->whereIn('status', ['open', 'answered'])->latest('id')->first(); @endphp

    @if($openRequest)
      <section class="info-request fu d2" aria-labelledby="info-request-heading">
        <h2 id="info-request-heading" class="info-request__title">
          {{ $openRequest->isAnswered() ? 'We received your reply' : 'The department needs more information' }}
        </h2>

        <blockquote class="info-request__question">
          <p class="info-request__asked-by">
            Asked by {{ $openRequest->requester?->full_name ?? 'the department' }} · {{ $openRequest->created_at->format('M j, Y g:i A') }}
          </p>
          <p>{{ $openRequest->question }}</p>
        </blockquote>

        @if($openRequest->isAnswered())
          <p class="info-request__thanks">
            Thank you. The department has your reply and will continue the review.
          </p>
        @else
          <form method="POST" action="{{ route('complaints.information.store', $complaint) }}" enctype="multipart/form-data" class="info-request__form" data-disable-on-submit>
            @csrf
            <div class="info-request__field">
              <label for="response" class="info-request__label">Your reply</label>
              <textarea name="response" id="response" rows="4" required minlength="10" maxlength="2000" placeholder="Describe what you can see, or add anything that would help the department check this.">{{ old('response') }}</textarea>
              @error('response')<p class="info-request__error">{{ $message }}</p>@enderror
            </div>

            <div class="info-request__field">
              <label for="images" class="info-request__label">Add photos <small>Optional, up to 5</small></label>
              <input type="file" name="images[]" id="images" accept="image/jpeg,image/png,image/webp" multiple>
              <p class="info-request__hint">Photos taken on your phone may share their location, which helps us confirm the spot.</p>
              @error('images')<p class="info-request__error">{{ $message }}</p>@enderror
              @error('images.*')<p class="info-request__error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="info-request__submit">Send to the department</button>
          </form>
        @endif


</section>
    @endif

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
    <h3 class="visibility-panel__title">Who can see this</h3>
    <p class="visibility-panel__lede">You decide whether this appears on the public transparency page. No member of staff can change it.</p>
  </div>

  <form method="POST" action="{{ route('complaints.visibility.update', $complaint) }}" class="visibility-panel__actions">
    @csrf
    @method('PUT')
    <input type="hidden" name="visibility" value="{{ $complaint->is_public ? 'private' : 'public' }}">
    <button type="submit" class="visibility-panel__button">
      {{ $complaint->is_public ? 'Make this private' : 'Publish on the transparency page' }}
    </button>
    <span class="visibility-panel__state">
      {{ $complaint->is_public
          ? 'Currently published under your display alias.'
          : 'Currently private. Only you and the staff handling it can see this.' }}
    </span>
  </form>

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
        <div class="detail-card" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="detail-card-title">Description</span>
          </div>
          <div class="detail-card-body">
            <p class="detail-value detail-value-large">{{ $complaint->description }}</p>
          </div>
        </div>

        {{-- Details Grid --}}
        <div class="detail-card" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span class="detail-card-title">Details</span>
          </div>
          <div class="detail-card-body">
            <div class="details-grid">
              <div class="detail-field">
                <div class="detail-label">Category</div>
                <span class="detail-pill">{{ \Illuminate\Support\Str::headline($complaint->category) }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">Review status</div>
                <span class="detail-value">{{ $complaint->reviewStatusEnum->label() }}</span>
              </div>
              <div class="detail-field">
                <div class="detail-label">Who can see this</div>
                <span class="detail-value">{{ $complaint->is_public ? 'Published on the transparency page' : 'Private' }}</span>
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
                <div class="detail-dept">@if($complaint->department)<span class="dept-indicator" data-dept="{{ $complaint->department->code }}"><span class="dept-dot" data-dept="{{ $complaint->department->code }}" aria-hidden="true"></span><span class="dept-indicator__name">{{ $complaint->department->name }}</span></span>@else Unassigned @endif</div>
                @if($complaint->department)
                  <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                    Code: {{ $complaint->department->code }}
                  </div>
                @endif
              </div>
              <div class="detail-field">
                <div class="detail-label">Date Filed</div>
                <div class="detail-value">{{ $complaint->created_at->format('F j, Y') }}</div>
                <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                  {{ $complaint->created_at->diffForHumans() }}
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Attached Photos --}}
        <div class="detail-card" style="margin-bottom: 24px;">
          <div class="detail-card-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
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
                    <span style="display: none; padding: 24px; text-align: center; color: var(--muted); font-size: 12px;">Image unavailable</span>
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="detail-card-title">Location</span>
          </div>
          <div class="detail-card-body">
            @if($complaint->latitude !== null && $complaint->longitude !== null)
              <div id="complaint-map" class="map-container"></div>
              @if($complaint->address_text)
                <div style="margin-top: 12px; font-size: 13px; color: var(--muted);">
                  <strong>Address:</strong> {{ $complaint->address_text }}
                </div>
              @endif
              <div style="margin-top: 4px; font-size: 12px; color: var(--muted);">
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold-500)" stroke-width="1.8" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
            <div class="timeline-title">Staff Responses</div>
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
      </div>

    </div>

  </div>

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
    @endif
    }, {
        // The coordinates are printed underneath the map regardless, so the
        // work can still be done. Saying so is what stops a blank box being
        // read as "my location was not saved".
        onUnavailable: () => {
          if (mapEl) mapEl.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:12px">Map unavailable. Your coordinates are still listed below.</div>';
        },
    });
  });
</script>
@endpush

@endsection
