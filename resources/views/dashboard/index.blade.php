@extends($mainLayout)
@section('title', __('dashboard.title').' - Daet Listens')
@section('content')
<style>
  .citizen-dashboard { --db-navy:#102545; --db-gold:#d4a947; --db-ink:#0f172a; --db-sub:#475569; --db-line:#cdd5e1; min-height:100dvh; padding-top:var(--navbar-height); background:#f6f8fb; color:var(--db-ink); font-family:var(--font-sans); overflow-x:hidden; }
  .db-container { width:100%; max-width:1040px; margin:0 auto; padding:0 24px; box-sizing:border-box; }
  .dashboard-welcome { background:#0b1a33; color:#fff; }
  .dashboard-welcome-inner { display:flex; align-items:center; justify-content:space-between; gap:24px; padding-top:28px; padding-bottom:28px; }
  .dashboard-welcome-title { margin:0; color:#fff; font-size:32px; font-weight:700; line-height:1.15; }
  .dashboard-welcome-title span { color:#e2c06a; }
  .dashboard-welcome-copy p { margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:15px; line-height:1.5; max-width:560px; }
  .dashboard-primary-action { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:40px; padding:10px 18px; background:#d4a947; color:#102545; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; flex-shrink:0; }
  .dashboard-primary-action:hover { background:#b8902f; color:#fff; }
  .dashboard-primary-action:focus-visible, .db-card:focus-visible, a:focus-visible, button:focus-visible, input:focus-visible { outline:2px solid #d4a947; outline-offset:2px; box-shadow:0 0 0 3px rgba(212,169,71,.25); }
  .dashboard-content { padding-top:32px; padding-bottom:48px; }
  .dashboard-columns { display:grid; grid-template-columns:minmax(0,1fr); gap:24px; align-items:start; }
  .dashboard-reports { min-width:0; }
  .dashboard-section-heading { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
  .dashboard-section-heading h2 { margin:0; font-size:20px; font-weight:600; color:var(--db-ink); }
  .dashboard-section-heading p { margin:4px 0 0; color:var(--db-sub); font-size:13px; }
  .db-filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .db-chip { display:inline-flex; align-items:center; min-height:36px; padding:6px 12px; border:1px solid var(--db-line); border-radius:999px; background:#fff; color:#334155; font-size:13px; font-weight:600; text-decoration:none; }
  .db-chip.is-active { background:#102545; color:#fff; border-color:#102545; }
  .db-chip:focus-visible { outline:2px solid #d4a947; outline-offset:2px; }
  .db-track-form { display:flex; gap:8px; align-items:center; }
  .db-track-form input { height:40px; padding:8px 12px; border:1px solid var(--db-line); border-radius:8px; background:#fff; color:var(--db-ink); font-family:var(--font-sans); font-size:13px; width:180px; }
  .db-track-form input::placeholder { color:#64748b; }
  .db-track-form input:focus { border-color:#d4a947; box-shadow:0 0 0 3px rgba(212,169,71,.25); outline:none; }
  .db-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border:1px solid var(--db-line); border-radius:8px; background:#fff; color:#102545; cursor:pointer; }
  .dashboard-report-list { display:grid; gap:12px; list-style:none; margin:0; padding:0; }
  .db-card { position:relative; display:block; background:#fff; border:1px solid var(--db-line); border-radius:12px; padding:20px 24px; box-shadow:0 1px 3px rgba(15,23,42,.06); text-decoration:none; color:var(--db-ink); }
  .db-card:hover { border-color:#94a3b8; background:#f6f8fb; }
  .db-card__row1 { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px; }
  .db-ticket { font-family:"IBM Plex Mono", ui-monospace, monospace; font-size:13px; font-weight:700; color:#102545; }
  .db-pill { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
  .db-pill--submitted { background:#dbeafe; color:#1e40af; }
  .db-pill--verified, .db-pill--assigned { background:#e8eef8; color:#102545; }
  .db-pill--in-progress { background:#fef3c7; color:#92400e; }
  .db-pill--resolved { background:#dcfce7; color:#15803d; }
  .db-pill--rejected { background:#fee2e2; color:#b91c1c; }
  .db-pill--closed { background:#eef1f6; color:#475569; }
  .db-card__title { margin:0 0 6px; font-size:18px; font-weight:700; line-height:1.3; color:var(--db-ink); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .db-card__meta { margin:0 0 14px; color:var(--db-sub); font-size:13px; }
  .db-progress { display:flex; align-items:center; gap:0; margin-top:4px; }
  .db-progress__step { display:flex; align-items:center; gap:6px; font-size:11px; color:#475569; }
  .db-progress__dot { width:10px; height:10px; border-radius:50%; background:#cdd5e1; flex-shrink:0; }
  .db-progress__dot--done { background:#102545; }
  .db-progress__dot--current { background:#d4a947; box-shadow:0 0 0 3px rgba(212,169,71,.25); }
  .db-progress__line { flex:1; height:2px; background:#eef1f6; margin:0 6px; min-width:12px; }
  .db-progress__line--done { background:#102545; }
  .db-progress-mobile { display:none; }
  .db-progress-segments { display:flex; gap:4px; margin-top:6px; }
  .db-progress-segment { flex:1; height:4px; border-radius:999px; background:#eef1f6; }
  .db-progress-segment.is-done { background:#102545; }
  .db-progress-segment.is-current { background:#d4a947; }
  .db-card__meta { display:flex; flex-wrap:wrap; gap:6px 12px; }
  .db-card__meta span { white-space:normal; }
  .db-progress-bar { height:4px; background:#eef1f6; border-radius:999px; overflow:hidden; margin-top:8px; }
  .db-progress-bar span { display:block; height:100%; background:#102545; }
  .db-card__chevron { position:absolute; right:20px; top:50%; transform:translateY(-50%); color:#475569; }
  .db-card__body { padding-right:28px; }
  .dashboard-empty { background:#fff; border:1px solid var(--db-line); border-radius:12px; padding:48px 24px; text-align:center; box-shadow:0 1px 3px rgba(15,23,42,.06); }
  .dashboard-empty-icon { display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:50%; background:#eef1f6; color:#102545; margin-bottom:14px; }
  .dashboard-empty h3 { margin:0 0 8px; font-size:20px; color:var(--db-ink); }
  .dashboard-empty p { margin:0 0 20px; color:var(--db-sub); font-size:15px; }
  .dashboard-rail { display:grid; gap:16px; }
  .db-side-card { background:#fff; border:1px solid var(--db-line); border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(15,23,42,.06); }
  .db-side-card h2 { margin:0 0 14px; font-size:15px; font-weight:600; color:var(--db-ink); }
  .dashboard-process { list-style:none; margin:0; padding:0; display:grid; gap:16px; }
  .dashboard-process li { display:flex; gap:12px; }
  .dashboard-process-number { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; flex-shrink:0; border-radius:50%; background:#eef1f6; color:#102545; font-size:12px; font-weight:700; }
  .dashboard-process li:not(:last-child) .dashboard-process-number { box-shadow:0 14px 0 -6px #eef1f6; }
  .dashboard-process strong { display:block; font-size:13px; color:var(--db-ink); margin-bottom:2px; }
  .dashboard-process p { margin:0; font-size:13px; color:var(--db-sub); line-height:1.45; }
  .db-dismiss { margin-top:12px; background:none; border:0; color:#102545; font-size:13px; font-weight:600; text-decoration:underline; cursor:pointer; padding:6px 0; min-height:32px; }
  .db-privacy { display:flex; gap:10px; font-size:13px; color:var(--db-sub); line-height:1.5; }
  .db-privacy svg { flex-shrink:0; color:#102545; margin-top:2px; }
  .dashboard-success { display:flex; gap:10px; padding:14px 16px; margin-bottom:20px; background:#dcfce7; border:1px solid #15803d; color:#14532d; border-radius:8px; font-size:14px; }
  .dashboard-alert { display:flex; gap:12px; padding:14px 16px; margin-bottom:20px; background:#fef3c7; border:1px solid #b45309; color:#92400e; border-radius:8px; text-decoration:none; font-size:14px; }
  .dashboard-pagination { margin-top:16px; }
  @media (min-width:1024px) {
    .dashboard-columns { grid-template-columns:minmax(0,1fr) 320px; }
    .dashboard-rail { position:sticky; top:calc(var(--navbar-height) + 16px); }
  }
  @media (max-width:1023px) {
    .db-progress { display:none; }
    .db-progress-mobile { display:block; }
    .db-progress-mobile__label { font-size:12px; color:#475569; }
  }
  @media (max-width:768px) {
    .db-container { padding:0 16px; }
    .dashboard-welcome-inner { flex-direction:column; align-items:stretch; padding-top:24px; padding-bottom:24px; gap:16px; }
    .dashboard-primary-action { width:100%; min-height:44px; }
    .dashboard-welcome-title { font-size:28px; }
    .db-track-form input { width:140px; }
    .db-card { padding:16px 20px; }
    .db-icon-btn, .db-chip { min-height:44px; }
  }
  @media (prefers-reduced-motion: reduce) { .citizen-dashboard * { transition-duration:0.01ms !important; } }
</style>

<div class="citizen-dashboard">
  <section class="dashboard-welcome" aria-labelledby="dashboard-welcome-title">
    <div class="db-container dashboard-welcome-inner">
      <div class="dashboard-welcome-copy">
        @php
          $hour = now('Asia/Manila')->hour;
          $greetingKey = $hour < 12 ? 'dashboard.greeting_morning' : ($hour < 17 ? 'dashboard.greeting_afternoon' : 'dashboard.greeting_evening');
        @endphp
        <h1 class="dashboard-welcome-title" id="dashboard-welcome-title">
          {{ __($greetingKey) }}, <span>{{ auth()->user()->first_name }}.</span>
        </h1>
        <p>{{ __('dashboard.subtitle') }}</p>
      </div>
      @if($totalReports > 0)
      <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-primary-action">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
        {{ __('dashboard.file_complaint') }}
      </a>
      @endif
    </div>
  </section>

  <div class="db-container dashboard-content">
    @if (session('success'))
      <div class="dashboard-success" role="status">
        <div>{{ __('dashboard.success') }} <strong>{{ session('new_ticket') }}</strong> {{ __('dashboard.success_ticket') }}</div>
      </div>
    @endif

    @if ($attentionComplaint)
      <a href="{{ route('complaints.show', $attentionComplaint) }}" wire:navigate class="dashboard-alert">
        <strong>{{ __('dashboard.attention_title') }}</strong>
        <span>{{ $attentionComplaint->ticket_id }} {{ __('dashboard.attention_hint') }}</span>
        <span>{{ __('dashboard.read_update') }} →</span>
      </a>
    @endif

    <div class="dashboard-columns">
      <section class="dashboard-reports" aria-labelledby="dashboard-reports-title">
        <div class="dashboard-section-heading">
          <div>
            <h2 id="dashboard-reports-title">{{ __('dashboard.your_reports') }}</h2>
            <p>{{ __('dashboard.your_reports_hint') }}</p>
          </div>
          <div class="db-filters" role="group" aria-label="{{ __('dashboard.your_reports') }}">
            <a href="{{ route('dashboard') }}" class="db-chip {{ !request('filter') ? 'is-active' : '' }}">{{ __('dashboard.all') }} ({{ $counts['all'] }})</a>
            <a href="{{ route('dashboard', ['filter' => 'open']) }}" class="db-chip {{ request('filter') === 'open' ? 'is-active' : '' }}">{{ __('dashboard.open') }} ({{ $counts['open'] }})</a>
            <a href="{{ route('dashboard', ['filter' => 'resolved']) }}" class="db-chip {{ request('filter') === 'resolved' ? 'is-active' : '' }}">{{ __('dashboard.resolved') }} ({{ $counts['resolved'] }})</a>
          </div>
        </div>

        <form method="GET" action="{{ route('complaints.track') }}" class="db-track-form" style="margin-bottom:16px">
          <label class="sr-only" for="db-ticket-search">{{ __('dashboard.track_by_ticket') }}</label>
          <input id="db-ticket-search" type="search" name="ticket_id" placeholder="{{ __('dashboard.search_placeholder') }}" value="{{ request('ticket_id') }}">
          <button type="submit" class="admin-button admin-button--secondary" style="min-height:40px">{{ __('dashboard.search') }}</button>
        </form>

        @if ($complaints->isEmpty())
          <div class="dashboard-empty">
            <div class="dashboard-empty-icon" aria-hidden="true">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </div>
            <h3>{{ __('dashboard.empty_title') }}</h3>
            <p>{{ __('dashboard.empty_hint') }}</p>
            <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-primary-action">{{ __('dashboard.file_first') }}</a>
          </div>
        @else
          @php
            $categoryLabels = collect(\App\Services\DepartmentRouter::getCategoryOptions())->flatten()->pluck('label', 'value');
            $steps = __('dashboard.steps');
            // Filter chips need a filtered list — handled server-side via ?filter on dashboard
            $displayComplaints = $complaints;
          @endphp
          <ul class="dashboard-report-list">@foreach ($displayComplaints as $complaint) @include('partials.report-card', ['complaint' => $complaint]) @endforeach</ul>

          @if ($totalReports > 3)
            <p style="margin-top:16px"><a href="{{ route('complaints.index') }}" wire:navigate style="color:#102545;font-weight:600">{{ __('dashboard.view_all') }} →</a></p>
          @endif

          @if ($complaints->hasPages())
            <div class="dashboard-pagination" aria-label="Report pages">{{ $complaints->links() }}</div>
          @endif
        @endif
      </section>

      <aside class="dashboard-rail" aria-label="Dashboard help">
        @if($totalReports <= 1)
        <section class="db-side-card" id="dashboard-how-card" aria-labelledby="dashboard-process-title">
          <h2 id="dashboard-process-title">{{ __('dashboard.how_it_works') }}</h2>
          <ol class="dashboard-process">
            <li><span class="dashboard-process-number">1</span><div><strong>{{ __('dashboard.how_step1_title') }}</strong><p>{{ __('dashboard.how_step1') }}</p></div></li>
            <li><span class="dashboard-process-number">2</span><div><strong>{{ __('dashboard.how_step2_title') }}</strong><p>{{ __('dashboard.how_step2') }}</p></div></li>
            <li><span class="dashboard-process-number">3</span><div><strong>{{ __('dashboard.how_step3_title') }}</strong><p>{{ __('dashboard.how_step3') }}</p></div></li>
          </ol>
          <button type="button" class="db-dismiss" id="dashboard-how-dismiss">{{ __('dashboard.dismiss') }}</button>
        </section>
        @endif

        <section class="db-side-card" aria-label="{{ __('dashboard.privacy_title') }}">
          <div class="db-privacy">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
            <div><strong>{{ __('dashboard.privacy_title') }}</strong><p style="margin:4px 0 0">{{ __('dashboard.privacy_note') }}</p></div>
          </div>
        </section>
      </aside>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  var card=document.getElementById('dashboard-how-card');
  var dismiss=document.getElementById('dashboard-how-dismiss');
  try { if(localStorage.getItem('daet_dashboard_how_dismissed')==='1' && card) card.hidden=true; } catch(e){}
  if(dismiss){ dismiss.addEventListener('click', function(){ try{ localStorage.setItem('daet_dashboard_how_dismissed','1'); }catch(e){} if(card) card.hidden=true; }); }
})();
</script>
@endpush
