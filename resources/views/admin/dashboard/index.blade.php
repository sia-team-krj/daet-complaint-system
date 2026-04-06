@extends('layouts.app')
@section('title', 'Admin Dashboard — Daet Listens')
@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');
  :root { --navy: #0B1F3A; --navy-mid: #12294d; --gold: #C9A84C; --gold-light: #E2C06A; --border-gold: rgba(201,168,76,0.20); }
  .admin-root { min-height: calc(100svh - 64px); background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%); display: flex; position: relative; }
  .admin-bg { position: absolute; inset: 0; background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px); pointer-events: none; }
  .admin-bar { position: absolute; top: 0; left: 0; width: 3px; height: 100%; background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06)); }
  .admin-sidebar { width: 280px; background: rgba(255,255,255,0.03); border-right: 1px solid var(--border-gold); padding: 24px 0; position: relative; z-index: 10; flex-shrink: 0; }
  .admin-brand { padding: 0 20px 24px; border-bottom: 1px solid var(--border-gold); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
  .admin-seal { width: 40px; height: 40px; background: linear-gradient(135deg, var(--gold), var(--gold-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--navy); font-weight: bold; }
  .admin-title { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 700; color: #fff; }
  .admin-subtitle { font-size: 10px; color: var(--gold); letter-spacing: 0.1em; text-transform: uppercase; }
  .sidebar-nav { list-style: none; padding: 0; margin: 0; }
  .sidebar-nav li { margin: 2px 0; }
  .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s; position: relative; }
  .sidebar-nav a:hover { color: #fff; background: rgba(255,255,255,0.03); }
  .sidebar-nav a.active { color: var(--gold); background: rgba(201,168,76,0.08); }
  .sidebar-nav a.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--gold); }
  .admin-main { flex: 1; padding: 32px 40px; position: relative; z-index: 5; overflow-y: auto; }
  .page-title { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 700; color: #fff; margin-bottom: 28px; }
  .stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 32px; }
  .stat-card { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; padding: 20px; position: relative; }
  .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); }
  .stat-value { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 700; color: var(--gold); margin-bottom: 4px; }
  .stat-label { font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.08em; }
  .dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-bottom: 32px; }
  .dept-card { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; padding: 20px; text-decoration: none; color: inherit; display: block; }
  .dept-card:hover { background: rgba(255,255,255,0.06); }
  .dept-code { font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: var(--gold); }
  .dept-name { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 700; color: #fff; margin: 4px 0 12px; }
  .dept-stats { display: flex; gap: 16px; font-size: 12px; color: rgba(255,255,255,0.6); }
  .section-title { font-family: 'Cormorant Garamond', serif; font-size: 20px; font-weight: 700; color: #fff; margin: 32px 0 20px; }
  .table-wrap { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: rgba(255,255,255,0.03); padding: 14px 16px; text-align: left; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(255,255,255,0.5); border-bottom: 1px solid var(--border-gold); }
  td { padding: 16px; color: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(201,168,76,0.1); }
  tr:hover td { background: rgba(255,255,255,0.02); }
  .unassigned-row { background: rgba(245,158,11,0.08) !important; }
  .btn-manage { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid var(--gold); border-radius: 4px; color: var(--gold); font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; }
  .btn-manage:hover { background: var(--gold); color: var(--navy); }
  .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
  .badge-amber { background: rgba(245,158,11,0.15); color: #fbbf24; }
  .badge-green { background: rgba(63,203,111,0.12); color: #6ee7a0; }
  .badge-red { background: rgba(239,68,68,0.12); color: #fca5a5; }
  @media (max-width: 1200px) { .stats-row { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 768px) { .admin-sidebar { display: none; } .stats-row { grid-template-columns: repeat(2, 1fr); } }
</style>
<div class="admin-root">
  <div class="admin-bg"></div>
  <div class="admin-bar"></div>
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <div class="admin-seal">★</div>
      <div>
        <div class="admin-title">Daet Listens</div>
        <div class="admin-subtitle">Admin Panel</div>
      </div>
    </div>
    <ul class="sidebar-nav">
      <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span>⊞</span> Overview</a></li>
      <li><a href="{{ route('admin.complaints.index') }}" class="{{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}"><span>📝</span> All Complaints</a></li>
      <li><a href="{{ route('admin.staff.index') }}" class="{{ request()->routeIs('admin.staff.*') ? 'active' : '' }}"><span>👤</span> Staff Accounts</a></li>
      <li><a href="{{ route('logout') }}"><span>→</span> Logout</a></li>
    </ul>
  </aside>
  <main class="admin-main">
    <h1 class="page-title">System Overview</h1>
    <div class="stats-row">
      <div class="stat-card"><div class="stat-value">{{ $stats['total'] ?? 0 }}</div><div class="stat-label">Total Complaints</div></div>
      <div class="stat-card"><div class="stat-value">{{ $stats['unassigned'] ?? 0 }}</div><div class="stat-label">Unassigned</div></div>
      <div class="stat-card"><div class="stat-value">{{ $stats['inProgress'] ?? 0 }}</div><div class="stat-label">In Progress</div></div>
      <div class="stat-card"><div class="stat-value">{{ $stats['resolvedToday'] ?? 0 }}</div><div class="stat-label">Resolved Today</div></div>
      <div class="stat-card"><div class="stat-value">{{ $stats['avgDays'] ?? '—' }}</div><div class="stat-label">Avg. Resolution Days</div></div>
    </div>
    <h2 class="section-title">Departments</h2>
    <div class="dept-grid">
      @forelse($departments ?? [] as $dept)
        <a href="{{ route('admin.complaints.index', ['department' => $dept->id]) }}" class="dept-card">
          <div class="dept-code">{{ $dept->code }}</div>
          <div class="dept-name">{{ $dept->name }}</div>
          <div class="dept-stats"><span>{{ $dept->pending_count ?? 0 }} pending</span><span>{{ $dept->staff_count ?? 0 }} staff</span></div>
        </a>
      @empty
        <p style="color: rgba(255,255,255,0.5);">No departments found.</p>
      @endforelse
    </div>
    @if(($unassignedComplaints ?? collect())->isNotEmpty())
      <h2 class="section-title" style="color: #fbbf24;">⚠️ Unassigned Complaints</h2>
      <div class="table-wrap" style="margin-bottom: 32px;">
        <table>
          <thead><tr><th>Ticket</th><th>Citizen</th><th>Category</th><th>Status</th><th>Filed</th><th>Action</th></tr></thead>
          <tbody>
            @foreach($unassignedComplaints as $c)
              <tr class="unassigned-row">
                <td><strong style="color: var(--gold);">{{ $c->ticket_id }}</strong></td>
                <td>{{ $c->user?->full_name ?? 'N/A' }}</td>
                <td>{{ $c->category }}</td>
                <td><span class="badge badge-amber">{{ $c->status?->label() ?? $c->status }}</span></td>
                <td>{{ $c->created_at?->format('M d, Y') ?? '—' }}</td>
                <td><a href="{{ route('admin.complaints.show', $c) }}" class="btn-manage">Manage →</a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
    <h2 class="section-title">Recent Complaints</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Ticket</th><th>Citizen</th><th>Department</th><th>Status</th><th>Filed</th><th>Action</th></tr></thead>
        <tbody>
          @forelse($recentComplaints ?? [] as $c)
            <tr>
              <td><strong style="color: var(--gold);">{{ $c->ticket_id }}</strong></td>
              <td>{{ $c->user?->full_name ?? 'N/A' }}</td>
              <td>{{ $c->department?->name ?? '—' }}</td>
              <td><span class="badge badge-{{ $c->status?->value === 'Resolved' ? 'green' : ($c->status?->value === 'Rejected' ? 'red' : 'amber') }}">{{ $c->status?->label() ?? $c->status }}</span></td>
              <td>{{ $c->created_at?->format('M d, Y') ?? '—' }}</td>
              <td><a href="{{ route('admin.complaints.show', $c) }}" class="btn-manage">Manage →</a></td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align: center; color: rgba(255,255,255,0.5);">No recent complaints.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </main>
</div>
@endsection
