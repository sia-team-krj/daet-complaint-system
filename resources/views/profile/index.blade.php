@extends('layouts.app')
@section('title', 'Profile — Daet Listens')

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');
  
  :root {
    --navy: #0B1F3A;
    --navy-mid: #12294d;
    --gold: #C9A84C;
    --gold-light: #E2C06A;
    --gold-pale: rgba(201,168,76,0.12);
    --border-gold: rgba(201,168,76,0.20);
    --text-dim: rgba(255,255,255,0.55);
    --red: #EF4444;
  }
  
  .profile-root {
    min-height: calc(100svh - 64px);
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    position: relative;
    padding: 40px 0;
  }
  
  .profile-bg {
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px);
    pointer-events: none;
  }
  
  .profile-bar {
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(180deg, var(--gold), rgba(201,168,76,0.06));
  }
  
  .profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 40px;
    position: relative;
    z-index: 5;
  }
  
  .profile-eyebrow {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 10px;
  }
  
  .profile-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 32px;
  }
  
  .profile-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 32px;
    align-items: start;
  }
  
  /* Identity Card */
  .identity-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 8px;
    padding: 40px;
    text-align: center;
  }
  
  .identity-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--navy-mid);
    border: 2px solid var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--gold);
  }
  
  .identity-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
  }
  
  .identity-email {
    font-size: 13px;
    color: var(--text-dim);
    margin-bottom: 16px;
  }
  
  .role-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 16px;
  }
  
  .role-citizen { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); }
  .role-staff { background: rgba(201,168,76,0.2); color: var(--gold); }
  .role-admin { background: rgba(239,68,68,0.15); color: var(--red); }
  
  .member-since {
    font-size: 12px;
    color: var(--text-dim);
    margin-bottom: 24px;
  }
  
  /* Department badge for staff */
  .dept-badge {
    display: inline-block;
    padding: 8px 16px;
    border: 1px solid var(--gold);
    border-radius: 4px;
    font-size: 12px;
    color: var(--gold);
    margin-bottom: 20px;
  }
  
  /* Stats row */
  .stats-row {
    display: flex;
    gap: 24px;
    justify-content: center;
    padding-top: 20px;
    border-top: 1px solid var(--border-gold);
  }
  
  .stat-item {
    text-align: center;
  }
  
  .stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 700;
    color: var(--gold);
  }
  
  .stat-label {
    font-size: 10px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }
  
  /* Form Cards */
  .form-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 8px;
    padding: 32px;
    margin-bottom: 24px;
  }
  
  .form-card-danger {
    border-color: rgba(239,68,68,0.20);
  }
  
  .form-card-header {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--text-dim);
    margin-bottom: 24px;
  }
  
  .form-card-header-danger {
    color: var(--red);
  }
  
  .form-group { margin-bottom: 20px; }
  .form-label {
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.6);
    margin-bottom: 8px;
  }
  
  .form-input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 4px;
    padding: 12px 14px;
    color: #fff;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
  }
  
  .form-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
  }
  
  .form-input:disabled {
    background: rgba(255,255,255,0.03);
    color: rgba(255,255,255,0.4);
    cursor: not-allowed;
  }
  
  .form-note {
    font-size: 11px;
    color: var(--text-dim);
    margin-top: 6px;
  }
  
  .password-hints {
    font-size: 11px;
    color: var(--text-dim);
    margin-top: 8px;
    line-height: 1.5;
  }
  
  /* Buttons */
  .btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy);
    padding: 14px 28px;
    border: none;
    border-radius: 4px;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  
  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201,168,76,0.3);
  }
  
  .btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: transparent;
    border: 1px solid var(--gold);
    color: var(--gold);
    padding: 14px 28px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-outline:hover {
    background: var(--gold);
    color: var(--navy);
  }
  
  /* Alerts */
  .alert {
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .alert-success {
    background: rgba(63,203,111,0.12);
    border: 1px solid rgba(63,203,111,0.25);
    color: #6ee7a0;
  }
  
  .alert-error {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #fca5a5;
  }
  
  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .fu { animation: fadeUp 0.6s cubic-bezier(.22,.68,0,1.2) both; }
  .d1 { animation-delay: 0.04s; }
  .d2 { animation-delay: 0.14s; }
  .d3 { animation-delay: 0.24s; }
  
  /* Responsive */
  @media (max-width: 900px) {
    .profile-grid {
      grid-template-columns: 1fr;
    }
    .profile-container {
      padding: 0 20px;
    }
  }
</style>

<div class="profile-root">
  <div class="profile-bg"></div>
  <div class="profile-bar"></div>
  
  <div class="profile-container">
    <div class="profile-eyebrow fu d1">Your Account</div>
    <h1 class="profile-title fu d2">Profile Settings</h1>
    
    <div class="profile-grid">
      {{-- Left Column: Identity Card --}}
      <div class="identity-card fu d2">
        <div class="identity-avatar">
          {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
        </div>
        
        <div class="identity-name">{{ $user->full_name }}</div>
        <div class="identity-email">{{ $user->email }}</div>
        
        <span class="role-badge role-{{ $user->role }}">
          {{ ucfirst($user->role) }}
        </span>
        
        <div class="member-since">Member since {{ $user->created_at->format('F Y') }}</div>
        
        @if($user->role === 'staff' && $user->department)
          <div class="dept-badge">Assigned to: {{ $user->department->name }}</div>
        @endif
        
        @if($user->role === 'citizen' && isset($stats))
          <div class="stats-row">
            <div class="stat-item">
              <div class="stat-value">{{ $stats['total_filed'] ?? 0 }}</div>
              <div class="stat-label">Total Filed</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ $stats['resolved'] ?? 0 }}</div>
              <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
              <div class="stat-label">Pending</div>
            </div>
          </div>
        @endif
        
        @if($user->role === 'staff' && isset($stats))
          <div class="stats-row">
            <div class="stat-item">
              <div class="stat-value">{{ $stats['handled'] ?? 0 }}</div>
              <div class="stat-label">Handled</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ $stats['resolved_this_month'] ?? 0 }}</div>
              <div class="stat-label">This Month</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ $stats['avg_days'] ?? '—' }}</div>
              <div class="stat-label">Avg. Days</div>
            </div>
          </div>
        @endif
        
        @if($user->role === 'admin' && isset($stats))
          <div class="system-label" style="font-size: 12px; color: var(--gold); margin-bottom: 20px;">
            ★ System Administrator
          </div>
          <div class="stats-row">
            <div class="stat-item">
              <div class="stat-value">{{ $stats['total_users'] ?? 0 }}</div>
              <div class="stat-label">Users</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ $stats['total_complaints'] ?? 0 }}</div>
              <div class="stat-label">Complaints</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ $stats['total_departments'] ?? 0 }}</div>
              <div class="stat-label">Depts</div>
            </div>
          </div>
        @endif
      </div>
      
      {{-- Right Column: Forms --}}
      <div>
        {{-- Flash Messages --}}
        @if(session('status'))
          <div class="alert alert-success fu d2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('status') }}
          </div>
        @endif
        
        @if(session('error'))
          <div class="alert alert-error fu d2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
          </div>
        @endif
        
        {{-- Update Profile Card --}}
        <div class="form-card fu d3">
          <div class="form-card-header">Personal Information</div>
          
          <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-input" value="{{ old('first_name', $user->first_name) }}" required>
                @error('first_name')<span style="color: var(--red); font-size: 11px;">{{ $message }}</span>@enderror
              </div>
              
              <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-input" value="{{ old('last_name', $user->last_name) }}" required>
                @error('last_name')<span style="color: var(--red); font-size: 11px;">{{ $message }}</span>@enderror
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
              @error('email')<span style="color: var(--red); font-size: 11px;">{{ $message }}</span>@enderror
            </div>
            
            @if($user->role === 'staff' && $user->department)
              <div class="form-group">
                <label class="form-label">Department</label>
                <input type="text" class="form-input" value="{{ $user->department->name }}" disabled>
                <p class="form-note">Contact admin to change department assignment</p>
              </div>
            @endif
            
            @if($user->role === 'admin')
              <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" class="form-input" value="System Administrator" disabled>
              </div>
            @endif
            
            <button type="submit" class="btn-submit">Save Changes</button>
          </form>
        </div>
        
        {{-- Change Password Card --}}
        <div class="form-card fu d4">
          <div class="form-card-header">Security</div>
          
          <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            
            <div class="form-group">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-input" required>
              @error('current_password')<span style="color: var(--red); font-size: 11px;">{{ $message }}</span>@enderror
            </div>
            
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-input" required>
              <div class="password-hints">
                Minimum 8 characters, at least 1 uppercase letter and 1 number
              </div>
              @error('new_password')<span style="color: var(--red); font-size: 11px;">{{ $message }}</span>@enderror
            </div>
            
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="new_password_confirmation" class="form-input" required>
            </div>
            
            <button type="submit" class="btn-outline">Update Password</button>
          </form>
        </div>
        
        {{-- Danger Zone (Admin Only) --}}
        @if($user->role === 'admin')
          <div class="form-card form-card-danger fu d5">
            <div class="form-card-header form-card-header-danger">System</div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label class="form-label">User ID</label>
                <input type="text" class="form-input" value="{{ $user->id }}" disabled>
              </div>
              
              <div class="form-group">
                <label class="form-label">Account Created</label>
                <input type="text" class="form-input" value="{{ $user->created_at->format('M d, Y H:i') }}" disabled>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Last Updated</label>
              <input type="text" class="form-input" value="{{ $user->updated_at->format('M d, Y H:i') }}" disabled>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
