{{--
    The office's trail: every action anyone in the office has taken.

    One markup for the dashboard card (a preview of the last few entries) and
    the department activity page (the whole trail, paginated). The entries are
    grouped by ticket, so a burst of updates on one complaint reads as one
    line rather than five.

    Expects:
      $entries — a collection of stdClass: display_title, display_detail,
                 display_note, display_count, created_at, complaint
      $extra   — true for the entries the dashboard card hides until
                 "show more" is pressed
--}}
@forelse($entries as $i => $entry)
    @php
        $complaint = $entry->complaint;
        $complaintUrl = $complaint ? route('staff.complaints.show', $complaint) : null;
        $ticketLabel = is_object($complaint) ? ($complaint->ticket_id ?? '') : ($entry->complaint_ticket_id ?? '');
        $isExtra = ($extra ?? false) && $i >= 4;
    @endphp
    @if ($complaintUrl)
        <a href="{{ $complaintUrl }}"
           class="dept-activity-item dept-activity-item--link {{ $isExtra ? 'dept-activity-item--extra' : '' }}"
           aria-label="{{ __('staff.dashboard.view_complaint', ['ticket' => $ticketLabel]) }}">
            <span class="dept-activity-item__dot" aria-hidden="true"></span>
            <div class="dept-activity-item-main">
                <div class="dept-activity-item__copy">
                    <p class="dept-activity-item__title">{{ $entry->display_title }}</p>
                    <p class="dept-activity-item__note">{{ $entry->display_detail }}</p>
                    @if ($entry->display_note)
                        <p class="dept-activity-item__note dept-activity-item__note--line">{{ __('staff.dashboard.note_label') }} {{ $entry->display_note }}</p>
                    @endif
                </div>
                <time datetime="{{ $entry->created_at?->toAtomString() }}" class="dept-activity-item__time">{{ $entry->created_at?->diffForHumans() }}</time>
            </div>
        </a>
    @else
        <div class="dept-activity-item {{ $isExtra ? 'dept-activity-item--extra' : '' }}">
            <span class="dept-activity-item__dot" aria-hidden="true"></span>
            <div class="dept-activity-item-main">
                <div class="dept-activity-item__copy">
                    <p class="dept-activity-item__title">{{ $entry->display_title }}</p>
                    <p class="dept-activity-item__note">{{ $entry->display_detail }}</p>
                    @if ($entry->display_note)
                        <p class="dept-activity-item__note dept-activity-item__note--line">{{ __('staff.dashboard.note_label') }} {{ $entry->display_note }}</p>
                    @endif
                </div>
                <time datetime="{{ $entry->created_at?->toAtomString() }}" class="dept-activity-item__time">{{ $entry->created_at?->diffForHumans() }}</time>
            </div>
        </div>
    @endif
@empty
    <p class="dept-activity__empty">{{ __('staff.dashboard.no_activity') }}</p>
@endforelse
