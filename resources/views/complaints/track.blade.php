@extends($mainLayout)
@section('title', 'Track Complaint — Daet Listens')
@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&display=swap');

  :root {
    --navy:        #0B1F3A;
    --navy-mid:    #12294d;
    --gold:        #C9A84C;
    --cream:       #F5F0E8;
    --white:       #ffffff;
    --text-body:   #4B5563;
    --error:       #991b1b;
    --error-bg:    rgba(239,68,68,0.08);
    --error-border: rgba(239,68,68,0.2);
  }

  *, *::before, *::after { box-sizing: border-box; }

  .track-root {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: calc(100svh - 64px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }

  .track-card {
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(11,31,58,0.08);
    padding: 48px 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
  }

  .track-card h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 8px;
  }

  .track-card p {
    color: var(--text-body);
    margin-bottom: 32px;
    font-size: 15px;
  }

  .track-form {
    display: flex;
    gap: 12px;
  }

  .track-form input {
    flex: 1;
    padding: 12px 16px;
    border: 1.5px solid rgba(11,31,58,0.15);
    border-radius: 8px;
    font-size: 15px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color 0.2s;
  }

  .track-form input:focus {
    border-color: var(--gold);
  }

  .track-form button {
    padding: 12px 24px;
    background: var(--navy);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: background 0.2s;
  }

  .track-form button:hover {
    background: var(--navy-mid);
  }

  .track-link {
    display: block;
    margin-top: 20px;
    color: var(--gold);
    font-size: 14px;
    text-decoration: none;
  }

  .track-link:hover {
    text-decoration: underline;
  }

  .flash-error {
    display: flex; align-items: flex-start; gap: 12px;
    background: var(--error-bg); border: 1px solid var(--error-border);
    border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;
    font-size: 13px; color: var(--error); line-height: 1.6;
  }

  @media (max-width: 500px) {
    .track-form {
      flex-direction: column;
    }
  }
</style>

<div class="track-root">
  <div class="track-card">
    <h2>Track Your Complaint</h2>
    <p>Enter your ticket ID to check the status of your filing.</p>

    @if($notFound ?? false)
      <div style="display: flex; align-items: flex-start; gap: 12px; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #991b1b;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <div>
          No complaint found with ticket ID <strong>{{ request('ticket_id') }}</strong>.<br>
          Please check the ID and try again.
        </div>
      </div>
    @endif

    <form method="GET" action="{{ route('complaints.track') }}" class="track-form">
      <input type="text" name="ticket_id" placeholder="e.g. COMP-2026-00001" value="{{ request('ticket_id') }}" />
      <button type="submit">Track</button>
    </form>

    <a href="{{ route('complaints.index') }}" class="track-link">← Back to My Complaints</a>
  </div>
</div>

@endsection
