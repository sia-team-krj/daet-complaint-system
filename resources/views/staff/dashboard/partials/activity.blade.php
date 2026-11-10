<section class="tr-card dept-activity" aria-labelledby="{{ $activityId ?? 'department-activity-title' }}">
    <div class="dept-activity__head">
        <h2 id="{{ $activityId ?? 'department-activity-title' }}" class="dept-section-title">{{ __('staff.dashboard.activity_title') }}</h2>
    </div>
    <div class="dept-activity__feed" id="dept-activity-feed">
        @forelse($recentActivity as $i => $log)
            @php
                $title = $log->display_title ?? __('staff.dashboard.activity_updated', ['actor' => $log->actor?->staff_actor_name ?? __('staff.dashboard.activity_actor_fallback'), 'ticket' => $log->complaint?->ticket_id ?? __('staff.dashboard.activity_ticket_fallback')]);
                $detail = $log->display_detail ?? __('staff.dashboard.activity_status_changed');
                $note = $log->display_note ?? null;
                $complaint = $log->complaint;
                $complaintUrl = $complaint ? route('staff.complaints.show', $complaint) : null;
            @endphp
            <div class="dept-activity-item {{ $i >= 4 ? 'dept-activity-item--extra' : '' }}">
                <span class="dept-activity-item__dot" aria-hidden="true"></span>
                <div class="dept-activity-item-main">
                    <div class="dept-activity-item__copy">
                        @if($complaintUrl)
                            <a href="{{ $complaintUrl }}" class="dept-activity-item__title-link" aria-label="View complaint {{ $complaint?->ticket_id }}">
                        @endif
                        <p class="dept-activity-item__title">{{ $title }}</p>
                        @if($complaintUrl)
                            </a>
                        @endif
                        <p class="dept-activity-item__note">{{ $detail }}</p>
                        @if($note)<p class="dept-activity-item__note dept-activity-item__note--line">{{ __('staff.dashboard.note_label') }} {{ $note }}</p>@endif
                    </div>
                    <time datetime="{{ $log->created_at?->toAtomString() }}" class="dept-activity-item__time">{{ $log->created_at?->diffForHumans() }}</time>
                </div>
            </div>
        @empty
            <p class="dept-activity__empty">{{ __('staff.dashboard.no_activity') }}</p>
        @endforelse
    </div>
    @if($recentActivity->count() > 4)
        <button type="button" class="dept-activity__more" aria-expanded="false" aria-controls="dept-activity-feed">
            {{ __('staff.dashboard.show_more') }}
        </button>
    @endif
    <a href="{{ route('staff.activity.index') }}" class="dept-activity__view-all">{{ __('staff.dashboard.view_all_activity') }}</a>
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
