{{--
    Notification feed body. Rendered inside the navbar dropdown on first paint
    and re-fetched from the same markup by the bell's toggle, so the two can
    never drift apart.

    Row shape follows the familiar social pattern: a circular icon anchor, then
    the subject of the sentence in bold with the rest plain, then the time. That
    carries far more per row than a label/title stack in the same width.
--}}
<div class="notification-feed__header">
    <span class="notification-feed__title">Notifications</span>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="notification-feed__mark-all">Mark all as read</button>
        </form>
    @endif
</div>

@if($notifications->isEmpty())
    <div class="notification-feed__empty">
        @include('layouts.partials.notification-icon', ['iconKey' => 'bell'])
        <span>You're all caught up</span>
        <small>Updates about your complaints and your department's queue will show up here.</small>
    </div>
@else
    <ul class="notification-feed__list">
        @foreach($notifications as $notification)
            @php [$entity, $remainder] = $notification->headlineParts(); @endphp
            <li>
                <a
                    href="{{ route('notifications.read', $notification) }}"
                    class="notification-feed__item {{ $notification->isRead() ? 'is-read' : 'is-unread' }}"
                >
                    <span class="notification-feed__icon {{ $notification->isRead() ? 'is-read' : 'is-unread' }}">
                        @include('layouts.partials.notification-icon', ['iconKey' => $notification->iconKey()])
                    </span>

                    <span class="notification-feed__body">
                        <span class="notification-feed__headline">
                            @if($entity !== '')<strong>{{ $entity }}</strong> {{ $remainder }}@else{{ $remainder }}@endif
                        </span>
                        <span class="notification-feed__meta">
                            <span class="notification-feed__label">{{ $notification->typeLabel() }}</span>
                            <span class="notification-feed__time">{{ $notification->created_at->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                        </span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="notification-feed__footer">
        <a href="{{ route('notifications.index') }}" class="notification-feed__all">See all notifications</a>
    </div>
@endif
