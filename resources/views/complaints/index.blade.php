@extends($mainLayout)
@section('title', __('nav.my_complaints').' - Daet Listens')
@section('content')
<style>
  .list-root { --db-navy:#102545; --db-gold:#d4a947; --db-ink:#0f172a; --db-sub:#475569; --db-line:#cdd5e1; min-height:100dvh; padding-top:var(--navbar-height); background:#f6f8fb; color:var(--db-ink); font-family:var(--font-sans); overflow-x:hidden; }
  .list-container { width:100%; max-width:1040px; margin:0 auto; padding:0 24px; box-sizing:border-box; }
  .list-header { padding:32px 0 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
  .dashboard-primary-action { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:40px; padding:10px 18px; background:#d4a947; color:#102545; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; flex-shrink:0; }
  .dashboard-primary-action:hover { background:#b8902f; color:#fff; }
  .list-title { margin:0; font-family:var(--font-sans); font-size:32px; font-weight:700; line-height:1.15; color:var(--db-ink); }
  .list-subtitle { margin:6px 0 0; color:var(--db-sub); font-size:15px; }
  .list-body { padding:8px 0 48px; }
  .filter-bar { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
  .db-filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .db-chip { display:inline-flex; align-items:center; min-height:36px; padding:6px 12px; border:1px solid var(--db-line); border-radius:999px; background:#fff; color:#334155; font-size:13px; font-weight:600; text-decoration:none; }
  .db-chip.is-active { background:#102545; color:#fff; border-color:#102545; }
  .filter-right { display:flex; gap:8px; align-items:center; margin-left:auto; flex-wrap:wrap; }
  .filter-search { position:relative; display:flex; align-items:center; }
  .filter-search input { height:40px; padding:8px 36px 8px 36px; border:1px solid var(--db-line); border-radius:8px; background:#fff; color:var(--db-ink); font-family:var(--font-sans); font-size:13px; width:220px; }
  .filter-search input::placeholder { color:#64748b; }
  .filter-search input:focus { border-color:#d4a947; box-shadow:0 0 0 3px rgba(212,169,71,.25); outline:none; }
  .filter-search__icon { position:absolute; left:10px; color:#475569; display:flex; }
  .filter-search__clear { position:absolute; right:6px; width:28px; height:28px; border:0; background:none; color:#475569; cursor:pointer; font-size:16px; line-height:1; }
  .filter-sort { height:40px; padding:8px 12px; border:1px solid var(--db-line); border-radius:8px; background:#fff; color:var(--db-ink); font-family:var(--font-sans); font-size:13px; }
  .dashboard-report-list { display:grid; gap:12px; list-style:none; margin:0; padding:0; }
  .db-card { position:relative; display:block; background:#fff; border:1px solid var(--db-line); border-radius:12px; padding:20px 24px; box-shadow:0 1px 3px rgba(15,23,42,.06); text-decoration:none; color:var(--db-ink); }
  .db-card:hover { border-color:#94a3b8; background:#f6f8fb; }
  .db-card:focus-visible, a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible { outline:2px solid #d4a947; outline-offset:2px; box-shadow:0 0 0 3px rgba(212,169,71,.25); }
  .db-card__body { display:block; padding-right:28px; }
  .db-card__row1 { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px; }
  .db-ticket { font-family:"IBM Plex Mono", ui-monospace, monospace; font-size:13px; font-weight:700; color:#102545; }
  .db-pill { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
  .db-pill--submitted { background:#dbeafe; color:#1e40af; }
  .db-pill--verified, .db-pill--assigned { background:#e8eef8; color:#102545; }
  .db-pill--in-progress { background:#fef3c7; color:#92400e; }
  .db-pill--resolved { background:#dcfce7; color:#15803d; }
  .db-pill--rejected { background:#fee2e2; color:#b91c1c; }
  .db-card__title { display:block; margin:0 0 6px; font-size:18px; font-weight:700; line-height:1.3; color:var(--db-ink); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .db-card__meta { display:flex; flex-wrap:wrap; gap:6px 12px; margin:0 0 14px; color:var(--db-sub); font-size:13px; }
  .db-progress { display:flex; align-items:center; gap:0; }
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
  .db-card__chevron { position:absolute; right:20px; top:50%; transform:translateY(-50%); color:#475569; }
  .empty-state { text-align:center; padding:48px 24px; background:#fff; border:1px solid var(--db-line); border-radius:12px; box-shadow:0 1px 3px rgba(15,23,42,.06); }
  .empty-icon { width:52px; height:52px; border-radius:50%; background:#eef1f6; color:#102545; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
  .empty-title { font-size:20px; font-weight:700; color:var(--db-ink); margin:0 0 8px; }
  .empty-desc { font-size:15px; color:var(--db-sub); max-width:420px; margin:0 auto 20px; line-height:1.5; }
  .dashboard-primary-action { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:40px; padding:10px 18px; background:#d4a947; color:#102545; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; }
  .dashboard-primary-action:hover { background:#b8902f; color:#fff; }
  .admin-button--secondary { min-height:40px; }
  .showing-count { margin:16px 0 0; color:var(--db-sub); font-size:13px; }
  .pagination-wrap { margin-top:20px; display:flex; justify-content:center; }
  @media (max-width:1023px) {
    .db-progress { display:none; }
    .db-progress-mobile { display:block; }
    .db-progress-mobile__label { font-size:12px; color:#475569; }
  }
  @media (max-width:768px) {
    .list-container { padding:0 16px; }
    .list-title { font-size:28px; }
    .filter-bar { flex-direction:column; align-items:stretch; }
    .db-filters { flex-wrap:nowrap; overflow-x:auto; scrollbar-width:none; padding-bottom:2px; }
    .db-filters::-webkit-scrollbar { display:none; }
    .filter-right { margin-left:0; width:100%; }
    .filter-search { flex:1; }
    .filter-search input { flex:1; width:100%; height:44px; }
    .filter-sort { height:44px; }
    .db-card { padding:16px 20px; }
    .db-chip { min-height:44px; flex-shrink:0; }
  }
</style>

<div class="list-root">
  <div class="list-container">
    <div class="list-header">
      <div>
        <h1 class="list-title">{{ __('nav.my_complaints') }}</h1>
        <p class="list-subtitle">
          @if($complaints->total() === 1)1 complaint @else{{ $complaints->total() }} complaints @endif
        </p>
      </div>
      @if($counts['all'] > 0)
      <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-primary-action">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        {{ __('dashboard.file_complaint') }}
      </a>
      @endif
    </div>

    <div class="filter-bar">
      <div class="db-filters" role="group" aria-label="{{ __('nav.my_complaints') }}">
        <a href="{{ route('complaints.index', array_filter(['status'=>'all','q'=>request('q'),'sort'=>request('sort')])) }}" class="db-chip {{ in_array(request('status'), [null,'all','']) ? 'is-active' : '' }}">{{ __('dashboard.all') }} ({{ $counts['all'] }})</a>
        <a href="{{ route('complaints.index', array_filter(['status'=>'open','q'=>request('q'),'sort'=>request('sort')])) }}" class="db-chip {{ request('status') === 'open' ? 'is-active' : '' }}">{{ __('dashboard.open') }} ({{ $counts['open'] }})</a>
        <a href="{{ route('complaints.index', array_filter(['status'=>'resolved','q'=>request('q'),'sort'=>request('sort')])) }}" class="db-chip {{ request('status') === 'resolved' ? 'is-active' : '' }}">{{ __('dashboard.resolved') }} ({{ $counts['resolved'] }})</a>
      </div>
      <div class="filter-right">
        <form method="GET" action="{{ route('complaints.index') }}" class="filter-search" id="complaints-search-form">
          <input type="hidden" name="status" value="{{ request('status') }}">
          <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">
          <span class="filter-search__icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"/></svg></span>
          <input type="search" name="q" id="complaints-q" placeholder="{{ __('dashboard.search_placeholder') }}" value="{{ request('q') }}" aria-label="{{ __('dashboard.track_by_ticket') }}">
          @if(request('q'))
            <a href="{{ route('complaints.index', array_filter(['status'=>request('status'),'sort'=>request('sort')])) }}" class="filter-search__clear" aria-label="Clear search">×</a>
          @endif
          <button type="submit" class="admin-button admin-button--secondary" style="margin-left:8px">{{ __('dashboard.search') }}</button>
        </form>
        <form method="GET" action="{{ route('complaints.index') }}" id="complaints-sort-form">
          <input type="hidden" name="status" value="{{ request('status') }}">
          <input type="hidden" name="q" value="{{ request('q') }}">
          <label class="sr-only" for="complaints-sort">Sort</label>
          <select name="sort" id="complaints-sort" class="filter-sort" onchange="this.form.submit()">
            <option value="newest" @selected(request('sort','newest')==='newest')>Newest</option>
            <option value="oldest" @selected(request('sort')==='oldest')>Oldest</option>
          </select>
        </form>
      </div>
    </div>

    <div class="list-body">
      @if($complaints->total() === 0 && !request()->filled('q') && in_array(request('status'), [null,'all','']))
        <div class="empty-state">
          <div class="empty-icon" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg></div>
          <div class="empty-title">{{ __('dashboard.empty_title') }}</div>
          <p class="empty-desc">{{ __('dashboard.empty_hint') }}</p>
          <a href="{{ route('complaints.create') }}" wire:navigate class="dashboard-primary-action">{{ __('dashboard.file_first') }}</a>
        </div>
      @elseif($complaints->isEmpty())
        <div class="empty-state">
          <div class="empty-icon" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"/></svg></div>
          <div class="empty-title">No results</div>
          <p class="empty-desc">No complaints match your current filters.</p>
          <a href="{{ route('complaints.index') }}" class="admin-button admin-button--secondary">Clear filters</a>
        </div>
      @else
        <ul class="dashboard-report-list">
          @foreach($complaints as $complaint)
            @include('partials.report-card', ['complaint' => $complaint])
          @endforeach
        </ul>
        <p class="showing-count">Showing {{ $complaints->firstItem() }}-{{ $complaints->lastItem() }} of {{ $complaints->total() }}</p>
        @if($complaints->hasPages())
          <div class="pagination-wrap">{{ $complaints->links() }}</div>
        @endif
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  var input=document.getElementById('complaints-q');
  var form=document.getElementById('complaints-search-form');
  if(!input || !form) return;
  var timer;
  input.addEventListener('input', function(){
    clearTimeout(timer);
    timer=setTimeout(function(){ form.submit(); }, 300);
  });
})();
</script>
@endpush
