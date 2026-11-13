<section class="tr-card dept-activity" aria-labelledby="{{ $activityId ?? 'department-activity-title' }}">
    <div class="dept-activity__head">
        <h2 id="{{ $activityId ?? 'department-activity-title' }}" class="dept-section-title">{{ __('staff.dashboard.activity_title') }}</h2>
    </div>
    <div class="dept-activity__feed" id="dept-activity-feed">
        @include('staff.partials.activity-feed', ['entries' => $recentActivity, 'extra' => true])
    </div>
    @if($recentActivity->count() > 4)
        <button type="button" class="dept-activity__more" aria-expanded="false" aria-controls="dept-activity-feed">
            {{ __('staff.dashboard.show_more') }}
        </button>
    @endif
    <a href="{{ route('staff.department-activity.index') }}" class="dept-activity__view-all">{{ __('staff.dashboard.view_all_activity') }}</a>
</section>

<script>
(function () {
    var feed = document.getElementById('dept-activity-feed');
    var btn = document.querySelector('.dept-activity__more');
    if (!feed || !btn) return;
    btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        expanded = !expanded;
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        feed.toggleAttribute('data-expanded', expanded);
        btn.textContent = expanded ? '{{ __('staff.dashboard.show_less') }}' : '{{ __('staff.dashboard.show_more') }}';
    });
})();
</script>
