@extends('layouts.app')
@section('title', 'Create Staff Account — Admin')
@section('content')
<style>
  :root { --navy: #0B1F3A; --navy-mid: #12294d; --gold: #C9A84C; --gold-light: #E2C06A; --border-gold: rgba(201,168,76,0.20); }
  .admin-form { min-height: calc(100svh - 64px); background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%); padding: 32px 40px; }
  .page-title { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 700; color: #fff; margin-bottom: 24px; }
  .form-card { max-width: 600px; background: rgba(255,255,255,0.04); border: 1px solid var(--border-gold); border-radius: 6px; padding: 32px; }
  .form-group { margin-bottom: 20px; }
  .form-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
  .form-input, .form-select { width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(201,168,76,0.25); border-radius: 4px; padding: 12px; color: #fff; font-size: 14px; }
  .form-input:focus, .form-select:focus { outline: none; border-color: var(--gold); }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .btn-submit { background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: var(--navy); border: none; padding: 14px 32px; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 14px; }
  .btn-cancel { color: rgba(255,255,255,0.6); text-decoration: none; margin-left: 16px; }
</style>
<div class="admin-form">
  <h1 class="page-title">Create Staff Account</h1>
  <form method="POST" action="{{ route('admin.staff.store') }}" class="form-card">
    @csrf
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-input" required>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-input" required>
    </div>
    <div class="form-group">
      <label class="form-label">Contact Number</label>
      <input type="text" name="contact_number" class="form-input" placeholder="9171234567">
    </div>
    <div class="form-group">
      <label class="form-label">Department Assignment</label>
      <select name="department_id" class="form-select" required>
        <option value="">Select Department</option>
        @foreach($departments as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>
    </div>
    <div style="margin-top: 24px;">
      <button type="submit" class="btn-submit">Create Staff Account</button>
      <a href="{{ route('admin.staff.index') }}" class="btn-cancel">Cancel</a>
    </div>
    <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 20px;">A welcome email with a temporary password will be sent to the staff member.</p>
  </form>
</div>
@endsection
