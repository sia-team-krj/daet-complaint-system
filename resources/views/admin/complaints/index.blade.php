@extends('layouts.app')
@section('title', 'All Complaints — Admin')
@section('content')
<style>
  :root { --navy: #0B1F3A; --navy-mid: #12294d; --gold: #C9A84C; --border-gold: rgba(201,168,76,0.20); }
  .admin-root { min-height: calc(100svh - 64px); background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%); display: flex; }
  .admin-sidebar { width: 280px; background: rgba(255,255,255,0.03); border-right: 1px solid var(--border-gold); padding: 24px 0; }
  .admin-main { flex: 1; padding: 32px 40px; }
  .page-title { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 700; color: #fff; margin-bottom: 24px; }
  .filter-bar { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; padding: 20px; margin-bottom: 24px; display: flex; gap: 16px; flex-wrap: wrap; align-items: end; }
  .filter-group { display: flex; flex-direction: column; gap: 6px; }
  .filter-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.5); }
  .filter-input, .filter-select { background: rgba(255,255,255,0.06); border: 1px solid rgba(201,168,76,0.25); border-radius: 4px; padding: 10px 14px; color: #fff; font-size: 13px; min-width: 160px; }
  .filter-input::placeholder { color: rgba(255,255,255,0.3); }
  .btn-filter { background: var(--gold); color: var(--navy); border: none; padding: 10px 20px; border-radius: 4px; font-weight: 700; cursor: pointer; }
  .btn-export { background: transparent; border: 1px solid var(--gold); color: var(--gold); padding: 10px 20px; border-radius: 4px; text-decoration: none; }
  .table-wrap { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: rgba(255,255,255,0.03); padding: 14px 16px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.5); border-bottom: 1px solid var(--border-gold); }
  td { padding: 16px; color: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(201,168,76,0.1); }
  tr:hover td { background: rgba(255,255,255,0.02); }
  .ticket-id { color: var(--gold); font-weight: 700; }
  .btn-manage { padding: 6px 12px; border: 1px solid var(--gold); color: var(--gold); text-decoration: none; border-radius: 4px; font-size: 11px; }
  .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
  .pagination { padding: 16px; display: flex; justify-content: center; }
  .checkbox { width: 16px; height: 16px; accent-color: var(--gold); }
</style>
<div class="admin-root">
  @include('admin.partials.sidebar')
  <main class="admin-main">
    <h1 class="page-title">All Complaints</h1>
    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label class="filter-label">Department</label>
        <select name="department" class="filter-select">
          <option value="">All</option>
          <option value="unassigned" {{ request('department') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
          @foreach($departments as $d)
            <option value="{{ $d->id }}" {{ request('department') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">Status</label>
        <select name="status" class="filter-select">
          <option value="">All</option>
          @foreach($statuses as $s)
            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
          @endforeach
        </select>
      </div>
      <div class="filter-group">
        <label class="filter-label">From</label>
        <input type="date" name="date_from" class="filter-input" value="{{ request('date_from') }}">
      </div>
      <div class="filter-group">
        <label class="filter-label">To</label>
        <input type="date" name="date_to" class="filter-input" value="{{ request('date_to') }}">
      </div>
      <div class="filter-group">
        <label class="filter-label">Search</label>
        <input type="text" name="search" class="filter-input" placeholder="Ticket ID..." value="{{ request('search') }}">
      </div>
      <button type="submit" class="btn-filter">Filter</button>
      <a href="{{ route('admin.complaints.export', request()->all()) }}" class="btn-export">Export CSV</a>
    </form>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" class="checkbox" id="selectAll"></th>
            <th>Ticket</th>
            <th>Citizen</th>
            <th>Department</th>
            <th>Category</th>
            <th>Status</th>
            <th>Filed</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($complaints as $c)
            <tr>
              <td><input type="checkbox" class="checkbox" name="complaint_ids[]" value="{{ $c->id }}"></td>
              <td class="ticket-id">{{ $c->ticket_id }}</td>
              <td>{{ $c->user?->full_name ?? 'N/A' }}</td>
              <td>{{ $c->department?->name ?? '—' }}</td>
              <td>{{ $c->category }}</td>
              <td><span class="badge" style="background: {{ $c->status?->badgeColor() }}20; color: {{ $c->status?->badgeColor() }}">{{ $c->status?->label() ?? $c->status }}</span></td>
              <td>{{ $c->created_at?->format('M d, Y') }}</td>
              <td><a href="{{ route('admin.complaints.show', $c) }}" class="btn-manage">Manage</a></td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">No complaints found.</td></tr>
          @endforelse
        </tbody>
      </table>
      <div class="pagination">{{ $complaints->links() }}</div>
    </div>
  </main>
</div>
@endsection
