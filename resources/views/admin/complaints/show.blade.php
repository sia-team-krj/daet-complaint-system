@extends('layouts.app')
@section('title', $complaint->ticket_id . ' — Admin')
@section('content')
<style>
  :root { --navy: #0B1F3A; --navy-mid: #12294d; --gold: #C9A84C; --gold-light: #E2C06A; --border-gold: rgba(201,168,76,0.20); }
  .admin-detail { min-height: calc(100svh - 64px); background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%); padding: 32px 40px; }
  .breadcrumb { display: flex; gap: 12px; margin-bottom: 24px; font-size: 13px; }
  .breadcrumb a { color: rgba(255,255,255,0.5); text-decoration: none; }
  .detail-card { background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; margin-bottom: 24px; }
  .card-header { padding: 20px 24px; border-bottom: 1px solid rgba(201,168,76,0.15); display: flex; align-items: center; gap: 12px; }
  .card-title { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 700; color: #fff; }
  .card-body { padding: 24px; }
  .form-group { margin-bottom: 20px; }
  .form-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.5); margin-bottom: 8px; display: block; }
  .form-select, .form-textarea, .form-input { width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(201,168,76,0.25); border-radius: 4px; padding: 12px; color: #fff; font-size: 13px; }
  .form-textarea { min-height: 100px; resize: vertical; }
  .btn-update { background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: var(--navy); border: none; padding: 14px 28px; border-radius: 4px; font-weight: 700; cursor: pointer; }
  .two-col { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
  .timeline { position: relative; padding-left: 20px; }
  .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: var(--gold); }
  .timeline-item { position: relative; padding-bottom: 20px; }
  .timeline-item::before { content: ''; position: absolute; left: -20px; top: 4px; width: 8px; height: 8px; background: var(--gold); border-radius: 50%; }
  .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
  .info-label { font-size: 10px; text-transform: uppercase; color: rgba(255,255,255,0.4); }
  .info-value { font-size: 14px; color: #fff; }
  @media (max-width: 1100px) { .two-col { grid-template-columns: 1fr; } }
</style>
<div class="admin-detail">
  <nav class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <span style="color: rgba(255,255,255,0.3);">/</span>
    <a href="{{ route('admin.complaints.index') }}">Complaints</a>
    <span style="color: rgba(255,255,255,0.3);">/</span>
    <span style="color: var(--gold);">{{ $complaint->ticket_id }}</span>
  </nav>
  <div class="two-col">
    <div>
      <div class="detail-card">
        <div class="card-header"><div class="card-title">{{ $complaint->title }}</div></div>
        <div class="card-body">
          <div class="info-grid" style="margin-bottom: 20px;">
            <div><div class="info-label">Status</div><div class="info-value">{{ $complaint->status?->label() }}</div></div>
            <div><div class="info-label">Category</div><div class="info-value">{{ $complaint->category }}</div></div>
            <div><div class="info-label">Urgency</div><div class="info-value">{{ $complaint->urgency }}</div></div>
            <div><div class="info-label">Filed</div><div class="info-value">{{ $complaint->created_at?->format('M d, Y') }}</div></div>
          </div>
          <div class="form-label">Description</div>
          <p style="color: rgba(255,255,255,0.8); line-height: 1.7;">{{ $complaint->description }}</p>
        </div>
      </div>
      <div class="detail-card">
        <div class="card-header"><div class="card-title">Citizen Information</div></div>
        <div class="card-body">
          <div class="info-grid">
            <div><div class="info-label">Name</div><div class="info-value">{{ $complaint->user?->full_name }}</div></div>
            <div><div class="info-label">Email</div><div class="info-value">{{ $complaint->user?->email }}</div></div>
            <div><div class="info-label">Contact</div><div class="info-value">{{ $complaint->user?->contact_number ?? 'N/A' }}</div></div>
            <div><div class="info-label">Barangay</div><div class="info-value">{{ $complaint->user?->barangay ?? 'N/A' }}</div></div>
          </div>
        </div>
      </div>
    </div>
    <div>
      <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="detail-card">
        @csrf @method('PUT')
        <div class="card-header"><div class="card-title">Update Complaint</div></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              @foreach($statuses as $s)
                <option value="{{ $s->value }}" {{ $complaint->status?->value === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="">Unassigned</option>
              @foreach($departments as $d)
                <option value="{{ $d->id }}" {{ $complaint->department_id === $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Staff Note</label>
            <textarea name="staff_note" class="form-textarea" placeholder="Add notes about this update..."></textarea>
          </div>
          <button type="submit" class="btn-update">Update Complaint</button>
        </div>
      </form>
      <div class="detail-card">
        <div class="card-header"><div class="card-title">Status History</div></div>
        <div class="card-body">
          <div class="timeline">
            @forelse($complaint->logs as $log)
              <div class="timeline-item">
                <div style="font-size: 12px; color: var(--gold); font-weight: 600;">{{ $log->previous_status ?? '—' }} → {{ $log->new_status }}</div>
                @if($log->comment)<div style="font-size: 13px; color: rgba(255,255,255,0.7); margin: 8px 0;">{{ $log->comment }}</div>@endif
                <div style="font-size: 11px; color: rgba(255,255,255,0.4);">{{ $log->actor?->full_name }} • {{ $log->created_at?->format('M d, g:i A') }}</div>
              </div>
            @empty
              <p style="color: rgba(255,255,255,0.5);">No status updates yet.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
