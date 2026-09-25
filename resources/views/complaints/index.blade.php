@extends($mainLayout)
@section('title', 'My Complaints — Daet Listens')
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

  .list-root {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: calc(100svh - 64px);
    padding-top: 64px;
  }

  /* ── Page Header ── */
  .list-header {
    background: var(--navy);
    position: relative;
    overflow: hidden;
    padding: 48px 40px 52px;
  }
  .list-header::before {
    content: '';
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
    pointer-events: none;
  }
  .list-header-bar {
    position: absolute; top: 0; left: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.1));
  }
  .list-header-glow {
    position: absolute; top: -40%; right: -5%;
    width: 40vw; height: 40vw; max-width: 500px; max-height: 500px;
    background: radial-gradient(ellipse, rgba(201,168,76,0.06) 0%, transparent 68%);
    pointer-events: none;
  }
  .list-header-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    display: flex; align-items: flex-end;
    justify-content: space-between; gap: 24px; flex-wrap: wrap;
  }
  .list-eyebrow {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 10px;
  }
  .list-eyebrow::before { content: ''; width: 20px; height: 1px; background: var(--gold); }
  .list-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 3.5vw, 44px); font-weight: 700;
    color: var(--white); line-height: 1.08; letter-spacing: -0.01em;
    margin-bottom: 6px;
  }
  .list-subtitle {
    font-size: 13px; color: rgba(255,255,255,0.45);
    font-weight: 300; line-height: 1.6;
  }
  .btn-file-new {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy); font-family: 'DM Sans', sans-serif;
    font-size: 11px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase;
    padding: 13px 26px; border-radius: 4px; text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s; flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(201,168,76,0.3);
  }
  .btn-file-new:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(201,168,76,0.45); }

  /* ── Filter Bar ── */
  .filter-bar {
    max-width: 1280px; margin: 0 auto;
    padding: 24px 40px 0;
    display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
  }
  .filter-form { display: flex; gap: 12px; flex-wrap: wrap; flex: 1; }
  .filter-select, .filter-input {
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; padding: 10px 14px;
    border: 1px solid var(--border-gold); border-radius: 6px;
    background: var(--white); color: var(--text-body);
    min-width: 160px;
  }
  .filter-select:focus, .filter-input:focus {
    outline: none; border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.15);
  }
  .filter-btn {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px; font-weight: 600;
    padding: 10px 20px; border-radius: 6px;
    border: 1px solid var(--border-gold);
    background: transparent; color: var(--text-body);
    cursor: pointer; transition: all 0.2s;
  }
  .filter-btn:hover { background: var(--gold-pale); border-color: var(--gold); }
  .filter-btn-primary {
    background: var(--navy); color: var(--white);
    border-color: var(--navy);
  }
  .filter-btn-primary:hover { background: var(--navy-mid); }

  /* ── Main Content ── */
  .list-body {
    max-width: 1280px; margin: 0 auto;
    padding: 32px 40px 64px;
  }

  /* ── Complaint Cards ── */
  .complaints-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
  }
  .complaint-card {
    background: var(--white);
    border: 1px solid var(--border-gold);
    border-radius: 8px;
    padding: 24px;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex; flex-direction: column;
  }
  .complaint-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(11,31,58,0.08);
  }
  .card-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 16px; gap: 12px;
  }
  .card-ticket {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px; font-weight: 700;
    color: var(--navy); letter-spacing: 0.03em;
  }
  .card-status {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.09em;
    text-transform: uppercase; padding: 4px 10px; border-radius: 4px;
    white-space: nowrap;
  }
  .card-status::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: 0.7; }

  .badge-submitted  { background: rgba(59,130,246,0.08); color: #1d4ed8; border: 1px solid rgba(59,130,246,0.2); }
  .badge-review     { background: rgba(245,158,11,0.08); color: #92400e; border: 1px solid rgba(245,158,11,0.2); }
  .badge-progress   { background: rgba(139,92,246,0.08); color: #4c1d95; border: 1px solid rgba(139,92,246,0.2); }
  .badge-resolved   { background: rgba(63,203,111,0.1);  color: #065f46; border: 1px solid rgba(63,203,111,0.25); }
  .badge-rejected   { background: rgba(239,68,68,0.08);  color: #991b1b; border: 1px solid rgba(239,68,68,0.2); }
  .badge-closed     { background: rgba(107,114,128,0.08);color: #374151; border: 1px solid rgba(107,114,128,0.2); }

  .card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px; font-weight: 600;
    color: var(--navy); line-height: 1.3;
    margin-bottom: 8px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .card-desc {
    font-size: 13px; color: var(--text-muted); line-height: 1.6;
    margin-bottom: 16px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; flex-grow: 1;
  }
  .card-meta {
    display: flex; align-items: center; gap: 12px;
    font-size: 12px; color: var(--text-muted);
    padding-top: 16px; border-top: 1px solid rgba(201,168,76,0.15);
  }
  .card-review-label {
    color: #92670a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }
  .card-dept {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 10px; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    background: var(--gold-pale); color: #92670a;
    padding: 3px 10px; border-radius: 20px;
    border: 1px solid rgba(201,168,76,0.25);
  }
  .card-action {
    margin-left: auto;
    font-size: 11px; font-weight: 600; color: var(--gold);
    text-decoration: none; letter-spacing: 0.05em; text-transform: uppercase;
    transition: color 0.2s;
  }
  .card-action:hover { color: #92670a; }

  /* ── Empty State ── */
  .empty-state {
    text-align: center; padding: 80px 32px;
    background: var(--white); border: 1px solid var(--border-gold);
    border-radius: 8px;
  }
  .empty-icon {
    width: 72px; height: 72px; border-radius: 20px;
    background: var(--gold-pale); border: 1px solid var(--border-gold);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px; color: var(--gold);
  }
  .empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px; font-weight: 700; color: var(--navy); margin-bottom: 10px;
  }
  .empty-desc { font-size: 14px; color: var(--text-muted); max-width: 420px; margin: 0 auto 28px; line-height: 1.7; }

  /* ── Pagination ── */
  .pagination-wrap {
    margin-top: 40px;
    display: flex; justify-content: center;
  }
  .pagination {
    display: flex; gap: 6px; list-style: none; padding: 0; margin: 0;
  }
  .pagination li a, .pagination li span {
    display: flex; align-items: center; justify-content: center;
    min-width: 36px; height: 36px;
    font-size: 13px; font-weight: 500;
    color: var(--text-body); text-decoration: none;
    border: 1px solid var(--border-gold); border-radius: 6px;
    background: var(--white); transition: all 0.2s;
  }
  .pagination li a:hover { background: var(--gold-pale); border-color: var(--gold); }
  .pagination li.active span { background: var(--navy); color: var(--white); border-color: var(--navy); }
  .pagination li.disabled span { opacity: 0.5; }

  /* Animations */
  @keyframes fadeUp { from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);} }
  .fu  { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1.2) both; }
  .d1  { animation-delay: 0.04s; } .d2 { animation-delay: 0.14s; }
  .d3  { animation-delay: 0.24s; } .d4 { animation-delay: 0.34s; }

  /* Responsive */
  @media (max-width: 1100px) {
    .list-header { padding: 40px 32px 44px; }
    .filter-bar { padding: 24px 32px 0; }
    .list-body { padding: 32px 32px 56px; }
  }
  @media (max-width: 768px) {
    .list-root { padding-top: 64px; }
    .list-header { padding: 32px 20px 36px; }
    .filter-bar { padding: 20px 20px 0; }
    .list-body { padding: 24px 20px 48px; }
    .list-header-inner { flex-direction: column; align-items: flex-start; }
    .btn-file-new { width: 100%; justify-content: center; }
    .complaints-grid { grid-template-columns: 1fr; }
    .filter-form { width: 100%; }
    .filter-select, .filter-input { flex: 1; min-width: 0; }
  }
</style>

<div class="list-root">

  {{-- ── Page Header ── --}}
  <div class="list-header">
    <div class="list-header-bar"></div>
    <div class="list-header-glow"></div>
    <div class="list-header-inner">
      <div>
        <div class="list-eyebrow fu d1">Citizen Portal</div>
        <h1 class="list-title fu d2">My Complaints</h1>
        <p class="list-subtitle fu d3">
          View and track all your filed complaints
        </p>
      </div>
      <a href="{{ route('complaints.create') }}" wire:navigate class="btn-file-new fu d4">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        File New Complaint
      </a>
    </div>
  </div>

  {{-- ── Filter Bar ── --}}
  <div class="filter-bar fu d3">
    <form class="filter-form" method="GET" action="{{ route('complaints.index') }}">
      <select name="status" class="filter-select">
        <option value="">All Statuses</option>
        @foreach($statuses as $status)
          <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
            {{ $status->label() }}
          </option>
        @endforeach
      </select>
      <input type="text" name="search" class="filter-input" placeholder="Search by ticket ID..."
             value="{{ request('search') }}">
      <button type="submit" class="filter-btn filter-btn-primary">Filter</button>
      @if(request()->hasAny(['status', 'search']))
        <a href="{{ route('complaints.index') }}" class="filter-btn">Clear</a>
      @endif
    </form>
  </div>

  {{-- ── Main Content ── --}}
  <div class="list-body">

    @if($complaints->isEmpty())

      {{-- Empty State --}}
      <div class="empty-state fu d4">
        <div class="empty-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div class="empty-title">No complaints found</div>
        <p class="empty-desc">
          @if(request()->hasAny(['status', 'search']))
            No complaints match your current filters. Try adjusting your search criteria.
          @else
            You haven't filed any complaints yet. Start by reporting an issue in your community.
          @endif
        </p>
        @if(!request()->hasAny(['status', 'search']))
          <a href="{{ route('complaints.create') }}" wire:navigate class="btn-file-new">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            File Your First Complaint
          </a>
        @endif
      </div>

    @else

      {{-- Complaint Cards Grid --}}
      <div class="complaints-grid">
        @foreach($complaints as $i => $complaint)
          @php
            $statusEnum = $complaint->status instanceof \App\Enums\ComplaintStatus 
              ? $complaint->status 
              : \App\Enums\ComplaintStatus::from($complaint->status);
            $delay = 'd' . min($i + 1, 5);
          @endphp
          <div class="complaint-card fu {{ $delay }}">
            <div class="card-header">
              <span class="card-ticket">{{ $complaint->ticket_id }}</span>
              <span class="card-status {{ $statusEnum->badgeColor() }}">
                {{ $statusEnum->label() }}
              </span>
            </div>
            <h3 class="card-title">{{ $complaint->title }}</h3>
            <p class="card-desc">{{ Str::limit($complaint->description, 150) }}</p>
            <div class="card-meta">
              <span class="card-review-label">{{ $complaint->reviewStatusEnum->label() }}</span>
              <span class="card-dept">{{ $complaint->department->name ?? 'Unassigned' }}</span>
              <span>{{ $complaint->created_at->format('M d, Y') }}</span>
              <a href="{{ route('complaints.show', $complaint) }}" class="card-action" wire:navigate>View →</a>
            </div>
          </div>
        @endforeach
      </div>

      {{-- Pagination --}}
      @if($complaints->hasPages())
        <div class="pagination-wrap fu d5">
          {{ $complaints->links() }}
        </div>
      @endif

    @endif

  </div>

</div>

@endsection
