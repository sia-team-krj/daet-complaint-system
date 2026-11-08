@extends(auth()->user()->role === 'citizen' ? 'layouts.app' : 'layouts.app')

@section('title', 'Notifications - Daet Listens')
@section('content')
<div class="notifications-page">
    <header class="notifications-page__header">
        <div class="notifications-page__heading">
            <h1 class="notifications-page__title">Notifications</h1>
            <p class="notifications-page__subtitle" aria-live="polite" aria-atomic="true">
                @if($unreadCount > 0)
                    {{ $unreadCount }} unread of {{ $totalCount }}
                @else
                    You're all caught up
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('notifications.read-all') }}" class="notifications-page__mark-all-form">
            @csrf
            <button type="submit" class="notifications-mark-all" @disabled($unreadCount === 0)>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12l4 4L14 8"></path>
                    <path d="M10 16l2 2 8-8"></path>
                </svg>
                Mark all read
            </button>
        </form>
    </header>

    <nav class="notifications-tabs" aria-label="Filter notifications">
        <ul>
            <li>
                <a
                    href="{{ route('notifications.index', ['filter' => 'all']) }}"
                    class="notification-tab {{ $filter === 'all' ? 'is-active' : '' }}"
                    @if($filter === 'all') aria-current="page" @endif
                    data-notifications-filter="all"
                >All</a>
            </li>
            <li>
                <a
                    href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    class="notification-tab {{ $filter === 'unread' ? 'is-active' : '' }}"
                    @if($filter === 'unread') aria-current="page" @endif
                    data-notifications-filter="unread"
                >Unread ({{ $unreadCount }})</a>
            </li>
        </ul>
    </nav>

    @if($notifications->total() === 0)
        <div class="notifications-page__empty">
            <span class="notifications-page__empty-icon" aria-hidden="true">
                @include('layouts.partials.notification-icon', ['iconKey' => 'bell'])
            </span>
            <h2>You're all caught up</h2>
            <p>New updates on your complaints will show up here.</p>
        </div>
    @else
        <div class="notifications-list" id="notifications-list" aria-busy="false">
            @foreach($groups as $groupKey => $group)
                <section class="notification-group" aria-labelledby="notification-group-{{ $groupKey }}">
                    <h2 class="notification-group__label" id="notification-group-{{ $groupKey }}">{{ $group['label'] }}</h2>
                    <ul class="notification-group__list">
                        @foreach($group['items'] as $notification)
                            @php
                                [$entity, $remainder] = $notification->headlineParts();
                                $iconKey = $notification->iconKey();
                                $fullDate = $notification->created_at->format('F j, Y g:i A');
                                $relative = $notification->created_at->diffForHumans();
                                $readLabel = $notification->isRead() ? 'Read' : 'Unread';
                            @endphp
                            <li>
                                <a
                                    href="{{ route('notifications.read', $notification) }}"
                                    class="notification-row {{ $notification->isRead() ? 'is-read' : 'is-unread' }}"
                                    aria-label="{{ $readLabel }}: {{ trim(($entity !== '' ? $entity . ' ' : '') . $remainder) }}, {{ $notification->categoryLabel() }}, {{ $fullDate }}"
                                >
                                    <span class="notification-row__icon notification-row__icon--{{ $iconKey }}" aria-hidden="true">
                                        @include('layouts.partials.notification-icon', ['iconKey' => $iconKey])
                                    </span>
                                    <span class="notification-row__body">
                                        <span class="notification-row__headline">
                                            @if($entity !== '')<span class="notification-row__id">{{ $entity }}</span> {{ $remainder }}@else{{ $remainder }}@endif
                                        </span>
                                        <span class="notification-row__meta">
                                            <span class="notification-row__pill notification-row__pill--{{ $iconKey }}">{{ $notification->categoryLabel() }}</span>
                                            <time
                                                class="notification-row__time"
                                                datetime="{{ $notification->created_at->toIso8601String() }}"
                                                title="{{ $fullDate }}"
                                                aria-label="{{ $fullDate }}"
                                            >{{ $relative }}</time>
                                        </span>
                                    </span>
                                    <span class="notification-row__trailing" aria-hidden="true">
                                        @if(!$notification->isRead())
                                            <span class="notification-row__dot"></span>
                                        @endif
                                        <svg class="notification-row__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif

    <div class="notifications-skeleton" id="notifications-skeleton" hidden aria-hidden="true">
        @for($i = 0; $i < 3; $i++)
            <div class="notifications-skeleton__row">
                <span class="notifications-skeleton__circle"></span>
                <span class="notifications-skeleton__lines">
                    <span class="notifications-skeleton__line notifications-skeleton__line--long"></span>
                    <span class="notifications-skeleton__line notifications-skeleton__line--short"></span>
                </span>
            </div>
        @endfor
    </div>

    @if($notifications->hasPages())
        <div class="admin-pagination">{{ $notifications->links('pagination::workspace', ['ariaLabel' => 'Notification pages']) }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Show the 3-row skeleton while a filter tab navigates, then let the
    // freshly loaded list take over. The list is server-rendered, so this
    // only ever runs on the way to the next page — it never hides content
    // from a no-JS visit.
    document.querySelectorAll('[data-notifications-filter]').forEach(function (link) {
        link.addEventListener('click', function () {
            var list = document.getElementById('notifications-list');
            var skeleton = document.getElementById('notifications-skeleton');
            if (list) list.setAttribute('aria-busy', 'true');
            if (skeleton) skeleton.hidden = false;
        });
    });
</script>
@endpush
