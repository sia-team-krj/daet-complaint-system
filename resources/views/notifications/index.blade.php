@extends(auth()->user()->role === 'citizen' ? 'layouts.app' : 'layouts.app')

@section('title', 'Notifications - Daet Listens')
@section('content')
<div class="notifications-page">
    <header class="notifications-page__header">
        <div>
            <h1 class="notifications-page__title">Notifications</h1>
            <p class="notifications-page__subtitle">
                @if($unreadCount > 0)
                    {{ $unreadCount }} unread of {{ $notifications->total() }}.
                @else
                    You're all caught up.
                @endif
            </p>
        </div>

        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="admin-button admin-button--secondary">Mark all read</button>
            </form>
        @endif
    </header>

    <section class="notifications-page__list" aria-label="All notifications">
        @forelse($notifications as $notification)
            @php [$entity, $remainder] = $notification->headlineParts(); @endphp
            <a
                href="{{ route('notifications.read', $notification) }}"
                class="notification-row {{ $notification->isRead() ? 'is-read' : 'is-unread' }}"
            >
                <span class="notification-row__icon {{ $notification->isRead() ? 'is-read' : 'is-unread' }}" aria-hidden="true">
                    @include('layouts.partials.notification-icon', ['iconKey' => $notification->iconKey()])
                </span>
                <span class="notification-row__body">
                    <span class="notification-row__headline">
                        @if($entity !== '')<strong>{{ $entity }}</strong>@endif{{ $remainder }}
                    </span>
                    <span class="notification-row__meta">
                        <span class="notification-row__label">{{ $notification->typeLabel() }}</span>
                        <span class="notification-row__time">{{ $notification->created_at->format('M j, Y g:i A') }}</span>
                    </span>
                </span>
            </a>
        @empty
            <div class="notifications-page__empty">
                <strong>No notifications yet</strong>
                <span>Updates about your complaints and your department's queue will appear here.</span>
            </div>
        @endforelse
    </section>

    @if($notifications->hasPages())
        <div class="admin-pagination">{{ $notifications->links('pagination::workspace', ['ariaLabel' => 'Notification pages']) }}</div>
    @endif
</div>
@endsection
