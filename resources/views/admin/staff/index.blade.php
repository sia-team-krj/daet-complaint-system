@extends('layouts.app')
@section('title', 'Staff Accounts — Admin')
@section('content')
<style>
  :root { --navy: #0B1F3A; --navy-mid: #12294d; --gold: #C9A84C; --border-gold: rgba(201,168,76,0.20); }
  .admin-root { min-height: calc(100svh - 64px); background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%); display: flex; }
  .admin-sidebar { width: 280px; background: rgba(255,255,255,0.03); border-right: 1px solid var(--border-gold); padding: 24px 0; }
  .admin-main { flex: 1; padding: 32px 40px; }
  .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .page-title { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 700; color: #fff; }
  .btn-create { background: var(--gold); color: var(--navy); padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: 700; }
  .table-wrap { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { background: rgba(255,255,255,0.03); padding: 14px 16px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.5); border-bottom: 1px solid var(--border-gold); }
  td { padding: 16px; color: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(201,168,76,0.1); }
  tr:hover td { background: rgba(255,255,255,0.02); }
  .btn-action { padding: 6px 12px; border: 1px solid var(--gold); color: var(--gold); border-radius: 4px; font-size: 11px; background: transparent; cursor: pointer; }
  .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
  .badge-active { background: rgba(63,203,111,0.12); color: #6ee7a0; }
  .badge-inactive { background: rgba(239,68,68,0.12); color: #fca5a5; }
</style>
<div class="admin-root">
  @include('admin.partials.sidebar')
  <main class="admin-main">
    <div class="page-header">
      <h1 class="page-title">Staff Accounts</h1>
      <a href="{{ route('admin.staff.create') }}" class="btn-create">+ Create Staff Account</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Department</th><th>Contact</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          @forelse($staff as $s)
            <tr>
              <td><strong style="color: var(--gold);">{{ $s->full_name }}</strong></td>
              <td>{{ $s->email }}</td>
              <td>{{ $s->department?->name ?? '—' }}</td>
              <td>{{ $s->contact_number ?? '—' }}</td>
              <td><span class="badge badge-active">Active</span></td>
              <td>
                <form method="POST" action="{{ route('admin.staff.department', $s) }}" style="display: inline;">
                  @csrf @method('PATCH')
                  <select name="department_id" onchange="this.form.submit()" style="background: rgba(255,255,255,0.06); border: 1px solid var(--gold); color: #fff; padding: 6px; border-radius: 4px; font-size: 12px;">
                    @foreach($departments as $d)
                      <option value="{{ $d->id }}" {{ $s->department_id === $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                  </select>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">No staff accounts found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </main>
</div>
@endsection
