@extends('layouts.app')
@section('title', 'Complaint Submitted — ' . $complaint->ticket_id)

@section('content')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');
  
  :root {
    --navy: #0B1F3A;
    --navy-mid: #12294d;
    --gold: #C9A84C;
    --gold-light: #E2C06A;
    --border-gold: rgba(201,168,76,0.20);
  }
  
  .success-root {
    min-height: calc(100svh - 64px);
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }
  
  .success-bg {
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(
      -45deg, transparent, transparent 40px,
      rgba(201,168,76,0.025) 40px, rgba(201,168,76,0.025) 41px
    );
    pointer-events: none;
  }
  
  .success-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-gold);
    border-radius: 8px;
    padding: 48px 56px;
    max-width: 600px;
    width: 100%;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  
  .success-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), transparent);
  }
  
  .success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 32px;
    color: var(--navy);
  }
  
  .success-icon svg {
    width: 40px;
    height: 40px;
  }
  
  .success-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--gold);
    margin-bottom: 8px;
  }
  
  .ticket-display {
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 24px;
    letter-spacing: 0.02em;
  }
  
  .routed-to {
    font-size: 14px;
    color: rgba(255,255,255,0.7);
    margin-bottom: 8px;
  }
  
  .department-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 700;
    color: var(--gold);
    margin-bottom: 24px;
  }
  
  .handling-note {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border-gold);
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 32px;
    font-size: 14px;
    color: rgba(255,255,255,0.6);
    line-height: 1.6;
  }
  
  .btn-group {
    display: flex;
    gap: 16px;
    justify-content: center;
  }
  
  .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--navy);
    padding: 14px 28px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.05em;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201,168,76,0.3);
  }
  
  .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    border: 1px solid var(--gold);
    color: var(--gold);
    padding: 14px 28px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
  }
  
  .btn-secondary:hover {
    background: var(--gold);
    color: var(--navy);
  }
  
  @media (max-width: 640px) {
    .success-card {
      padding: 32px 24px;
    }
    .ticket-display {
      font-size: 36px;
    }
    .btn-group {
      flex-direction: column;
    }
  }
</style>

<div class="success-root">
  <div class="success-bg"></div>
  
  <div class="success-card">
    <div class="success-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
    </div>
    
    <div class="success-title">Complaint Submitted Successfully</div>
    
    <div class="ticket-display">{{ $complaint->ticket_id }}</div>
    
    <div class="routed-to">Routed to:</div>
    <div class="department-name">{{ $complaint->department?->name ?? 'General Services Office' }}</div>
    
    <div class="handling-note">
      Your complaint has been received and assigned to the appropriate department for verification.
      {{ $complaint->evidence_image_count }} {{ \Illuminate\Support\Str::plural('photo', $complaint->evidence_image_count) }} attached for review.
      The department will review the report and confirm its priority before work begins.
      @if(session('complaint_location_source') === 'photo_gps')
        The location embedded in your photo was used to place the report.
      @elseif(in_array(session('complaint_location_source'), ['address', 'map'], true))
        Your address or map point was used as the location fallback.
      @else
        No embedded photo location was found; the department may request more details during review.
      @endif
      You will receive updates as your complaint progresses through our system.
      Please save your ticket number for future reference.
    </div>
    
    <div class="btn-group">
      <a href="{{ route('complaints.show', $complaint) }}" class="btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
        View Complaint
      </a>
      <a href="{{ route('dashboard') }}" class="btn-secondary">
        Go to Dashboard
      </a>
    </div>
  </div>
</div>
@endsection
