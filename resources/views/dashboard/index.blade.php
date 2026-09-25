@extends($mainLayout)
@section('title', 'My Dashboard — Daet Listens')
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
    --border-navy: rgba(11,31,58,0.08);
    --border-gold: rgba(201,168,76,0.20);
  }

  *, *::before, *::after { box-sizing: border-box; }

  .dash-root {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: calc(100svh - 64px);
    padding-top: 64px;
  }

  /* ── Page Header ── */
  .dash-header {
    background: var(--navy);
    position: relative;
    overflow: hidden;
    padding: 48px 40px 52px;
  }
  .dash-header::before {
    content: '';
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
    pointer-events: none;
  }
  .dash-header-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.1));
  }
  .dash-header-glow {
    position: absolute; top: -40%; right: -5%;
    width: 40vw; height: 40vw; max-width: 500px; max-height: 500px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.06) 0%, transparent 68%);
    pointer-events: none;
  }
  .dash-header-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    display: flex; align-items: flex-end;
    justify-content: space-between; gap: 24px; flex-wrap: wrap;
  }
  .dash-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 10px;
  }
  .dash-eyebrow::before { content: ''; width: 20px; height: 1px; background: var(--gold); }
  .dash-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 3.5vw, 44px); font-weight: 700;
    color: var(--white); line-height: 1.08; letter-spacing: -0.01em;
    margin-bottom: 6px;
  }
  .dash-title span { color: var(--gold); font-style: italic; }
  .dash-subtitle {
    font-size: 13px; color: rgba(255,255,255,0.72);
    font-weight: 400; line-height: 1.6;
  }
  .btn-file-complaint {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy); font-family: 'DM Sans', sans-serif;
    font-size: 11px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
    padding: 13px 26px; border-radius: 4px; text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s; flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(201,168,76,0.3);
  }
  .btn-file-complaint:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(201,168,76,0.45); }

  /* ── Stats Row ── */
  .dash-stats {
    max-width: 1280px; margin: 0 auto;
    padding: 0 40px;
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 1px; background: var(--border-gold);
    border: 1px solid var(--border-gold);
    border-top: none; border-radius: 0 0 8px 8px;
    overflow: hidden;
  }
  .dash-stat {
    background: var(--navy);
    padding: 22px 24px;
    position: relative; transition: background 0.2s;
  }
  .dash-stat::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 0; height: 2px; background: var(--gold);
    transition: width 0.35s ease;
  }
  .dash-stat:hover { background: var(--navy-mid); }
  .dash-stat:hover::before { width: 100%; }
  .dash-stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(26px, 3vw, 38px); font-weight: 700;
    color: var(--gold); line-height: 1; margin-bottom: 4px;
  }
  .dash-stat-lbl {
    font-size: 9.5px; font-weight: 600; letter-spacing: 0.13em;
    text-transform: uppercase; color: rgba(255,255,255,0.64);
  }

  .dash-scope-note {
    max-width: 1280px;
    margin: 0 auto;
    padding: 13px 40px;
    display: flex;
    align-items: center;
    gap: 9px;
    border-bottom: 1px solid var(--border-gold);
    background: #fffdf8;
    color: var(--text-muted);
    font-size: 11px;
    line-height: 1.5;
  }
  .dash-scope-note svg { flex: 0 0 auto; color: var(--gold); }
  .dash-scope-note strong { color: var(--navy); font-weight: 700; }

  /* ── Main Content ── */
  .dash-body {
    max-width: 1280px; margin: 0 auto;
    padding: 40px 40px 64px;
  }

  /* ── Flash success ── */
  .flash-success {
    display: flex; align-items: flex-start; gap: 12px;
    background: rgba(63,203,111,0.08); border: 1px solid rgba(63,203,111,0.2);
    border-radius: 6px; padding: 14px 18px; margin-bottom: 28px;
    font-size: 13px; color: #166534; line-height: 1.6;
  }
  .flash-success svg { flex-shrink: 0; color: #3FCB6F; margin-top: 1px; }
  .flash-ticket {
    display: inline-block; font-family: 'Cormorant Garamond', serif;
    font-size: 15px; font-weight: 700; color: var(--navy);
    background: rgba(201,168,76,0.15); border: 1px solid var(--border-gold);
    padding: 2px 10px; border-radius: 3px; margin-left: 6px;
    letter-spacing: 0.05em;
  }

  /* ── Section header ── */
  .section-hd {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 16px; flex-wrap: wrap;
  }
  .section-hd-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px; font-weight: 700; color: var(--navy); letter-spacing: -0.01em;
  }
  .section-hd-count {
    font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-muted); background: var(--cream-dark);
    padding: 4px 12px; border-radius: 20px;
  }

  /* ── Complaints Table Card ── */
  .complaints-card {
    background: var(--white);
    border: 1px solid var(--border-navy);
    border-radius: 8px; overflow: hidden;
  }
  .complaints-table { width: 100%; border-collapse: collapse; }
  .complaints-table thead tr {
    border-bottom: 1px solid var(--border-navy);
    background: #fafaf8;
  }
  .complaints-table th {
    padding: 12px 20px; text-align: left;
    font-size: 9.5px; font-weight: 700; letter-spacing: 0.13em;
    text-transform: uppercase; color: var(--text-muted);
    white-space: nowrap;
  }
  .complaints-table tbody tr {
    border-bottom: 1px solid rgba(11,31,58,0.05);
    transition: background 0.15s;
  }
  .complaints-table tbody tr:last-child { border-bottom: none; }
  .complaints-table tbody tr:hover { background: #fdfaf4; }
  .complaints-table td {
    padding: 16px 20px; font-size: 13px;
    color: var(--text-body); vertical-align: middle;
  }

  /* Ticket ID */
  .ticket-id {
    font-family: 'Cormorant Garamond', serif;
    font-size: 15px; font-weight: 700; color: var(--navy);
    letter-spacing: 0.03em;
  }

  /* Category pill */
  .category-pill {
    display: inline-block; font-size: 10px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    background: var(--gold-pale); color: #92670a;
    padding: 3px 10px; border-radius: 20px;
    border: 1px solid rgba(201,168,76,0.25);
  }

  /* Review and priority */
  .review-priority { display: flex; flex-direction: column; gap: 4px; font-size: 11px; }
  .review-priority strong { color: var(--navy); font-size: 11px; }
  .review-priority span { color: var(--text-muted); }

  /* Status badges */
  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.09em;
    text-transform: uppercase; padding: 4px 10px; border-radius: 4px;
    white-space: nowrap;
  }
  .status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: 0.7; }

  .badge-submitted  { background: rgba(59,130,246,0.08); color: #1d4ed8; border: 1px solid rgba(59,130,246,0.2); }
  .badge-review     { background: rgba(245,158,11,0.08); color: #92400e; border: 1px solid rgba(245,158,11,0.2); }
  .badge-progress   { background: rgba(139,92,246,0.08); color: #4c1d95; border: 1px solid rgba(139,92,246,0.2); }
  .badge-resolved   { background: rgba(63,203,111,0.1);  color: #065f46; border: 1px solid rgba(63,203,111,0.25); }
  .badge-rejected   { background: rgba(239,68,68,0.08);  color: #991b1b; border: 1px solid rgba(239,68,68,0.2); }
  .badge-closed     { background: rgba(107,114,128,0.08);color: #374151; border: 1px solid rgba(107,114,128,0.2); }
  .review-pending,
  .review-needs-information { background: rgba(245,158,11,0.08); color: #92400e; border: 1px solid rgba(245,158,11,0.2); }
  .review-verified { background: rgba(63,203,111,0.1); color: #065f46; border: 1px solid rgba(63,203,111,0.25); }
  .review-duplicate { background: rgba(139,92,246,0.08); color: #4c1d95; border: 1px solid rgba(139,92,246,0.2); }
  .review-rejected,
  .review-escalated { background: rgba(239,68,68,0.08); color: #991b1b; border: 1px solid rgba(239,68,68,0.2); }

  /* Date */
  .date-cell { font-size: 12px; color: var(--text-muted); white-space: nowrap; }

  /* Department */
  .dept-cell { font-size: 12px; color: var(--text-muted); }

  /* Row action */
  .row-action {
    font-size: 11px; font-weight: 600; color: var(--gold);
    text-decoration: none; letter-spacing: 0.05em; text-transform: uppercase;
    transition: color 0.2s;
  }
  .row-action:hover { color: #92670a; }

  /* ── Empty State ── */
  .empty-state {
    padding: 72px 32px; text-align: center;
  }
  .empty-icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: var(--gold-pale); border: 1px solid var(--border-gold);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px; color: var(--gold);
  }
  .empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px; font-weight: 700; color: var(--navy); margin-bottom: 8px;
  }
  .empty-desc { font-size: 13.5px; color: var(--text-muted); max-width: 380px; margin: 0 auto 28px; line-height: 1.7; }
  .btn-empty-cta {
    display: inline-flex; align-items: center; gap: 9px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy); font-family: 'DM Sans', sans-serif;
    font-size: 11px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
    padding: 13px 28px; border-radius: 4px; text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 20px rgba(201,168,76,0.28);
  }
  .btn-empty-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(201,168,76,0.45); }

  /* Animations */
  @keyframes fadeUp { from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);} }
  .fu  { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1.2) both; }
  .d1  { animation-delay: 0.04s; } .d2 { animation-delay: 0.14s; }
  .d3  { animation-delay: 0.24s; } .d4 { animation-delay: 0.34s; }

  /* Responsive */
  @media (max-width: 1100px) {
    .dash-header { padding: 40px 32px 44px; }
    .dash-stats  { padding: 0 32px; }
    .dash-scope-note { padding-right: 32px; padding-left: 32px; }
    .dash-body   { padding: 32px 32px 56px; }
    .dash-stats  { grid-template-columns: repeat(2, 1fr); border-radius: 0 0 6px 6px; }
  }
  @media (max-width: 768px) {
    .dash-root { padding-top: 64px; }
    .dash-header { padding: 32px 20px 36px; }
    .dash-stats  { padding: 0 20px; grid-template-columns: repeat(2, 1fr); }
    .dash-scope-note { padding-right: 20px; padding-left: 20px; }
    .dash-body   { padding: 24px 20px 48px; }
    .dash-header-inner { flex-direction: column; align-items: flex-start; }
    .btn-file-complaint { width: 100%; justify-content: center; }
    /* Scroll table on small screens */
    .complaints-card {
      max-width: 100%;
      overflow-x: auto;
      overscroll-behavior-x: contain;
      -webkit-overflow-scrolling: touch;
    }
    .complaints-table { min-width: 700px; }
  }
  @media (max-width: 540px) {
    .dash-stats { grid-template-columns: 1fr 1fr; }
    .dash-stat { padding: 16px 16px; }
    .dash-stat-num { font-size: 24px; }
  }
  @media (max-width: 380px) {
    .dash-stats { grid-template-columns: 1fr; }
    .dash-body { padding-right: 14px; padding-left: 14px; }
    .dash-scope-note { padding-right: 14px; padding-left: 14px; }
  }
</style>

<div class="dash-root">

  {{-- ── Page Header ── --}}
  <div class="dash-header">
    <div class="dash-header-bar"></div>
    <div class="dash-header-glow"></div>
    <div class="dash-header-inner">
      <div>
        <div class="dash-eyebrow fu d1">Your complaint center</div>
        <h1 class="dash-title fu d2">
          @php
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
          @endphp
          {{ $greeting }}, <span>{{ auth()->user()->first_name }}.</span>
        </h1>
        <p class="dash-subtitle fu d3">
          A private view of the reports you filed and their current progress.
        </p>
      </div>
      <a href="{{ route('complaints.create') }}" wire:navigate class="btn-file-complaint fu d4">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        File a New Complaint
      </a>
    </div>
  </div>

  {{-- ── Stats Row (attached below header) ── --}}
  <div class="dash-stats">
    <div class="dash-stat">
      <div class="dash-stat-num">{{ $stats['total'] }}</div>
      <div class="dash-stat-lbl">My reports</div>
    </div>
    <div class="dash-stat">
      <div class="dash-stat-num">{{ $stats['pending'] }}</div>
      <div class="dash-stat-lbl">Needs attention</div>
    </div>
    <div class="dash-stat">
      <div class="dash-stat-num">{{ $stats['resolved'] }}</div>
      <div class="dash-stat-lbl">Resolved</div>
    </div>
    <div class="dash-stat">
      <div class="dash-stat-num">{{ $stats['avgDays'] ?? '—' }}</div>
      <div class="dash-stat-lbl">Avg. resolution</div>
    </div>
  </div>

  <div class="dash-scope-note">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
    <span><strong>Private view:</strong> these totals and reports belong only to your account.</span>
  </div>

  {{-- ── Main Body ── --}}
  <div class="dash-body">

    {{-- Flash success after filing --}}
    @if (session('success'))
      <div class="flash-success fu d1">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
          Complaint submitted successfully.
          <span class="flash-ticket">{{ session('new_ticket') }}</span>
          Keep this ticket number for your records.
        </div>
      </div>
    @endif

    {{-- ── Complaints Section ── --}}
    <div class="section-hd fu d2">
      <div class="section-hd-title">Your complaints</div>
      @if($complaints->count())
        <span class="section-hd-count">{{ $complaints->total() }} {{ Str::plural('report', $complaints->total()) }}</span>
      @endif
    </div>

    <div class="complaints-card fu d3">

      @if($complaints->isEmpty())

        {{-- Empty State --}}
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </div>
          <div class="empty-title">No complaints filed yet</div>
          <p class="empty-desc">
            Have a concern about a public service or facility?
            File your first complaint and we'll route it to the right department.
          </p>
          <a href="{{ route('complaints.create') }}" wire:navigate class="btn-empty-cta">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            File Your First Complaint
          </a>
        </div>

      @else

        <table class="complaints-table">
          <thead>
            <tr>
              <th>Ticket</th>
              <th>Category</th>
              <th>Title</th>
              <th>Review</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Department</th>
              <th>Filed</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($complaints as $complaint)
              @php
                $statusEnum = \App\Enums\ComplaintStatus::from($complaint->status instanceof \App\Enums\ComplaintStatus ? $complaint->status->value : $complaint->status);
              @endphp
              <tr>
                <td><span class="ticket-id">{{ $complaint->ticket_id }}</span></td>
                <td><span class="category-pill">{{ $complaint->category }}</span></td>
                <td style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  {{ $complaint->title }}
                </td>
                <td>
                  <span class="status-badge {{ $complaint->reviewStatusEnum->badgeClass() }}">{{ $complaint->reviewStatusEnum->label() }}</span>
                </td>
                <td>
                  <div class="review-priority">
                    <strong>{{ $complaint->confirmedPriorityEnum?->label() ?? 'Awaiting review' }}</strong>
                    <span>System suggested: {{ $complaint->suggestedPriorityEnum->label() }}</span>
                  </div>
                </td>
                <td>
                  <span class="status-badge {{ $statusEnum->badgeColor() }}">
                    {{ $statusEnum->label() }}
                  </span>
                </td>
                <td class="dept-cell">{{ $complaint->department->name ?? 'Pending routing' }}</td>
                <td class="date-cell">{{ $complaint->created_at->format('M d, Y') }}</td>
                <td>
                  <a href="{{ route('complaints.show', $complaint) }}" class="row-action" wire:navigate>View →</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        {{-- Pagination --}}
        @if($complaints->hasPages())
          <div style="padding: 16px 20px; border-top: 1px solid var(--border-navy);">
            {{ $complaints->links() }}
          </div>
        @endif

      @endif

    </div>
  </div>

</div>

@endsection