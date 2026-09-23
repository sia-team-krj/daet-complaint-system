@extends('layouts.app')
@section('title', 'Staff Portal — Daet Listens')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');

  :root {
    --navy: #0B1F3A;
    --navy-mid: #12294d;
    --gold: #C9A84C;
    --gold-light: #E2C06A;
    --text-dim: rgba(255,255,255,0.55);
    --border-gold: rgba(201,168,76,0.20);
    --red: #EF4444;
    --amber: #F59E0B;
  }

  .staff-root {
    min-height: calc(100svh - 64px);
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    position: relative;
    padding: 40px 0;
  }

  .staff-bg {
    position: absolute; inset: 0;
    background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px);
    pointer-events: none;
  }
  .staff-bar {
    position: absolute; top: 0; left: 0; width: 3px; height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }

  .staff-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    position: relative;
    z-index: 5;
  }

  /* Header */
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
  }
  .page-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--gold);
    margin-bottom: 8px;
  }
  .page-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px; font-weight: 700;
    color: #fff;
  }
  .page-subtext {
    font-size: 14px; color: var(--text-dim);
    margin-top: 8px;
  }
  .role-badge {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(201,168,76,0.15);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 4px;
    font-size: 10px; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--gold);
  }

  /* Activity link */
  .activity-link {
    padding: 10px 20px;
    background: transparent;
    border: 1px solid var(--gold);
    border-radius: 4px;
    color: var(--gold);
    font-size: 12px; font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
  }
  .activity-link:hover {
    background: var(--gold);
    color: var(--navy);
  }

  /* Tabs */
  .tabs-wrap {
    display: flex;
    gap: 4px;
    margin-bottom: 28px;
    border-bottom: 1px solid var(--border-gold);
    padding-bottom: 0;
  }
  .tab {
    padding: 14px 24px;
    font-size: 13px; font-weight: 600;
    color: var(--text-dim);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s;
  }
  .tab:hover { color: #fff; }
  .tab.active {
    color: var(--gold);
    border-bottom-color: var(--gold);
  }

  /* Stats Row */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }
  .stat-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 6px;
    padding: 24px;
  }
  .stat-card::before {
    content: ''; display: block;
    width: 40px; height: 2px;
    background: var(--gold);
    margin-bottom: 16px;
  }
  .stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px; font-weight: 700;
    color: var(--gold);
    margin-bottom: 6px;
  }
  .stat-label {
    font-size: 11px; font-weight: 500;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  /* Filter Bar */
  .filter-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    align-items: center;
  }
  .filter-select, .filter-input {
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border-gold);
    border-radius: 4px;
    padding: 10px 14px;
    color: #fff;
    font-size: 13px;
    outline: none;
  }
  .filter-select:focus, .filter-input:focus {
    border-color: var(--gold);
  }
  .filter-input { min-width: 240px; }
  .filter-btn {
    padding: 10px 20px;
    background: var(--gold);
    border: none;
    border-radius: 4px;
    color: var(--navy);
    font-size: 12px; font-weight: 700;
    cursor: pointer;
  }

  /* Complaint Cards */
  .complaint-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .complaint-card {
    display: grid;
    grid-template-columns: 140px 1fr 140px 100px 100px 120px;
    gap: 16px;
    align-items: center;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 6px;
    padding: 20px;
    transition: all 0.2s;
  }
  .complaint-card:hover {
    background: rgba(255,255,255,0.06);
  }
  .complaint-card.urgency-high {
    border-left: 3px solid var(--red);
  }
  .complaint-card.urgency-amber {
    border-left: 3px solid var(--amber);
  }
  .complaint-card.urgency-normal {
    border-left: 3px solid var(--gold);
  }

  .ticket-id {
    font-family: 'Cormorant Garamond', serif;
    font-size: 15px; font-weight: 700;
    color: var(--gold);
  }
  .complaint-preview {
    min-width: 0;
  }
  .complaint-title {
    font-size: 14px; font-weight: 600;
    color: #fff;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .complaint-category {
    font-size: 11px; color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }
  .status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .date-filed {
    font-size: 12px; color: var(--text-dim);
  }
  .days-open {
    font-size: 12px; font-weight: 600;
    padding: 4px 10px;
    border-radius: 4px;
    display: inline-block;
  }
  .days-normal { color: #6ee7a0; background: rgba(63,203,111,0.1); }
  .days-amber { color: #fbbf24; background: rgba(245,158,11,0.15); }
  .days-red { color: #fca5a5; background: rgba(239,68,68,0.12); }

  .btn-review {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    border: none; border-radius: 4px;
    color: var(--navy);
    font-size: 12px; font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
  }
  .btn-review:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201,168,76,0.3);
  }

  /* Empty State */
  .empty-state {
    text-align: center; padding: 80px 40px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 6px;
  }
  .empty-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px; font-weight: 600;
    color: #fff; margin-bottom: 8px;
  }
  .empty-text {
    font-size: 14px; color: var(--text-dim);
  }

  /* Pagination */
  .pagination-wrap {
    margin-top: 24px;
    display: flex;
    justify-content: center;
  }

  /* My Activity specific */
  .my-response {
    font-size: 12px; color: var(--text-dim);
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
  }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fu { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1.2) both; }
  .d1 { animation-delay: 0.04s; }
  .d2 { animation-delay: 0.12s; }
  .d3 { animation-delay: 0.20s; }
  .d4 { animation-delay: 0.28s; }
  .d5 { animation-delay: 0.36s; }

  /* Responsive */
  @media (max-width: 1200px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .complaint-card {
      grid-template-columns: 1fr;
      gap: 12px;
    }
  }
  @media (max-width: 768px) {
    .staff-container { padding: 0 20px; }
    .page-header { flex-direction: column; gap: 16px; }
    .stats-row { grid-template-columns: 1fr; }
  }
</style>

<div class="staff-root">
  <div class="staff-bg"></div>
  <div class="staff-bar"></div>

  <div class="staff-container">
    {{-- Header --}}
    <header class="page-header fu d1">
      <div>
        <div class="page-eyebrow">Staff Portal</div>
        <h1 class="page-title">{{ $department->name }} Dashboard</h1>
        <div class="page-subtext">
          Welcome back, {{ $user->first_name }} <span class="role-badge">Staff</span>
        </div>
      </div>
      @if($tab === 'queue')
        <a href="{{ route('staff.dashboard', ['tab' => 'my-activity']) }}" class="activity-link fu d2">
          My Activity →
        </a>
      @else
        <a href="{{ route('staff.dashboard', ['tab' => 'queue']) }}" class="activity-link fu d2">
          ← Department Queue
        </a>
      @endif
    </header>

    {{-- Tabs --}}
    <div class="tabs-wrap fu d2">
      <a href="{{ route('staff.dashboard', ['tab' => 'queue']) }}" class="tab {{ $tab === 'queue' ? 'active' : '' }}">
        Department Queue
      </a>
      <a href="{{ route('staff.dashboard', ['tab' => 'my-activity']) }}" class="tab {{ $tab === 'my-activity' ? 'active' : '' }}">
        My Activity
      </a>
    </div>

    @if($tab === 'queue')
      {{-- Queue Tab Content --}}
      <div class="stats-row fu d3">
        <div class="stat-card">
          <div class="stat-value">{{ $stats['new'] ?? 0 }}</div>
          <div class="stat-label">New / Unread</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ $stats['inProgress'] ?? 0 }}</div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ $stats['resolvedThisMonth'] ?? 0 }}</div>
          <div class="stat-label">Resolved This Month</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ $stats['avgDays'] ?? '—' }}</div>
          <div class="stat-label">Avg. Days Open</div>
        </div>
      </div>

      {{-- Filter Bar --}}
      <form class="filter-bar fu d4" method="GET" action="{{ route('staff.dashboard') }}">
        <input type="hidden" name="tab" value="queue">
        <select name="status" class="filter-select">
          <option value="">All Status</option>
          <option value="Submitted" {{ ($status ?? '') === 'Submitted' ? 'selected' : '' }}>Submitted</option>
          <option value="InProgress" {{ ($status ?? '') === 'InProgress' ? 'selected' : '' }}>In Progress</option>
          <option value="Resolved" {{ ($status ?? '') === 'Resolved' ? 'selected' : '' }}>Resolved</option>
          <option value="Rejected" {{ ($status ?? '') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <input type="text" name="search" class="filter-input" placeholder="Search by ticket ID or keyword..." value="{{ $search ?? '' }}">
        <button type="submit" class="filter-btn">Filter</button>
      </form>

      {{-- Complaint Cards --}}
      <div class="complaint-cards fu d5">
        @forelse($complaints as $complaint)
          @php
            $daysOpen = $complaint->created_at->diffInDays(now());
            $urgencyClass = $daysOpen > 14 ? 'urgency-high' : ($daysOpen > 7 ? 'urgency-amber' : 'urgency-normal');
            $daysClass = $daysOpen > 14 ? 'days-red' : ($daysOpen > 7 ? 'days-amber' : 'days-normal');
            $statusEnum = $complaint->status instanceof \App\Enums\ComplaintStatus
              ? $complaint->status
              : \App\Enums\ComplaintStatus::from($complaint->status);
          @endphp
          <div class="complaint-card {{ $urgencyClass }}">
            <div class="ticket-id">{{ $complaint->ticket_id }}</div>
            <div class="complaint-preview">
              <div class="complaint-title">{{ Str::limit($complaint->title, 50) }}</div>
              <div class="complaint-category">{{ str_replace('_', ' ', $complaint->category) }}</div>
            </div>
            <div>
              <span class="status-badge {{ $statusEnum->badgeColor() }}">
                {{ $statusEnum->label() }}
              </span>
            </div>
            <div class="date-filed">{{ $complaint->created_at->format('M d, Y') }}</div>
            <div>
              <span class="days-open {{ $daysClass }}">{{ $daysOpen }} days</span>
            </div>
            <div>
              <a href="{{ route('staff.complaints.show', $complaint) }}" class="btn-review">
                Review →
              </a>
            </div>
          </div>
        @empty
          <div class="empty-state">
            <div class="empty-title">No complaints found</div>
            <div class="empty-text">There are no complaints matching your filters.</div>
          </div>
        @endforelse
      </div>

      <div class="pagination-wrap">
        {{ $complaints->links() }}
      </div>

    @else
      {{-- My Activity Tab Content --}}
      <div class="stats-row fu d3" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
          <div class="stat-value">{{ $stats['totalResponded'] ?? 0 }}</div>
          <div class="stat-label">Total Responded</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ $stats['resolvedByMe'] ?? 0 }}</div>
          <div class="stat-label">Resolved by Me</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ isset($stats['lastActivity']) ? $stats['lastActivity']->diffForHumans() : '—' }}</div>
          <div class="stat-label">Last Activity</div>
        </div>
      </div>

      <div class="fu d4" style="margin-bottom: 20px;">
        <p style="color: var(--text-dim); font-size: 14px;">
          Showing complaints where you have added a response or update.
        </p>
      </div>

      {{-- My Activity Complaint Cards --}}
      <div class="complaint-cards fu d5">
        @forelse($complaints as $complaint)
          @php
            $myLastLog = $complaint->logs->first();
            $daysOpen = $complaint->created_at->diffInDays(now());
            $urgencyClass = $daysOpen > 14 ? 'urgency-high' : ($daysOpen > 7 ? 'urgency-amber' : 'urgency-normal');
            $statusEnum = $complaint->status instanceof \App\Enums\ComplaintStatus
              ? $complaint->status
              : \App\Enums\ComplaintStatus::from($complaint->status);
          @endphp
          <div class="complaint-card {{ $urgencyClass }}">
            <div class="ticket-id">{{ $complaint->ticket_id }}</div>
            <div class="complaint-preview">
              <div class="complaint-title">{{ Str::limit($complaint->title, 50) }}</div>
              <div class="my-response">
                @if($myLastLog)
                  "{{ Str::limit($myLastLog->comment, 60) }}"
                @else
                  No response yet
                @endif
              </div>
            </div>
            <div>
              <span class="status-badge {{ $statusEnum->badgeColor() }}">
                {{ $statusEnum->label() }}
              </span>
            </div>
            <div class="date-filed">{{ $complaint->created_at->format('M d, Y') }}</div>
            <div class="date-filed">
              @if($myLastLog)
                {{ $myLastLog->created_at->diffForHumans() }}
              @else
                —
              @endif
            </div>
            <div>
              <a href="{{ route('staff.complaints.show', $complaint) }}" class="btn-review">
                View →
              </a>
            </div>
          </div>
        @empty
          <div class="empty-state">
            <div class="empty-title">No activity yet</div>
            <div class="empty-text">You haven't responded to any complaints yet.</div>
          </div>
        @endforelse
      </div>

      <div class="pagination-wrap">
        {{ $complaints->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
