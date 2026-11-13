<li class="an-feed__item">
    <span class="an-feed__dot {{ $entry['is_internal'] ? 'is-internal' : '' }}" aria-hidden="true"></span>
    <div class="an-feed__body">
        <p class="an-feed__text">
            @if($entry['action'] === 'filed')
                {{ __('analytics.activity.filed', [
                    'actor' => $entry['actor'],
                    'ticket' => $entry['ticket_id'] ?? __('analytics.activity.ticket_fallback'),
                ]) }}
            @elseif($entry['action'] === 'status_changed')
                {{ __('analytics.activity.status_changed', [
                    'actor' => $entry['actor'],
                    'ticket' => $entry['ticket_id'] ?? __('analytics.activity.ticket_fallback'),
                    'from' => $entry['from'],
                    'to' => $entry['to'],
                ]) }}
            @elseif($entry['action'] === 'note')
                {{ __('analytics.activity.note', [
                    'actor' => $entry['actor'],
                    'ticket' => $entry['ticket_id'] ?? __('analytics.activity.ticket_fallback'),
                ]) }}
            @else
                {{ __('analytics.activity.updated', [
                    'actor' => $entry['actor'],
                    'ticket' => $entry['ticket_id'] ?? __('analytics.activity.ticket_fallback'),
                ]) }}
            @endif
        </p>
        @if($entry['comment'])
            <p class="an-feed__comment">“{{ $entry['comment'] }}”</p>
        @endif
        <p class="an-feed__meta">
            @if($entry['complaint_id'])
                <a href="{{ route($routeNames['complaint'], $entry['complaint_id']) }}">{{ $entry['title'] ?: $entry['ticket_id'] }}</a>
                <span aria-hidden="true">·</span>
            @endif
            <time datetime="{{ $entry['created_at']?->toIso8601String() }}">{{ $entry['created_at']?->locale(app()->getLocale())->diffForHumans() }}</time>
        </p>
    </div>
</li>
