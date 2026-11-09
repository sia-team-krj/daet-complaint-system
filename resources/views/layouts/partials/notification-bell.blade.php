{{--
    Navbar notification bell. Shares the account dropdown's language exactly, so
    the two popovers read as one control family rather than two ideas.

    The badge is rendered server-side and refreshed by a light poll; there is no
    Livewire island here because the rest of the shell is plain Blade.
--}}
@php
    $unreadBadge = \App\Models\Notification::unreadBadgeFor(auth()->id());
@endphp

{{--
    A direct child of .navbar-flex rather than a member of .nav-actions. The
    action list is display:none below 1241px, so anything inside it vanishes on
    tablet and phone. As a sibling it is present at every width.
--}}
<div class="nav-notification">
    <button
        type="button"
        class="nav-bell"
        id="notification-bell"
        data-notification-toggle
        data-notification-count-url="{{ route('notifications.unread-count') }}"
        data-notification-feed-url="{{ route('notifications.feed') }}"
        aria-controls="notification-dropdown"
        aria-expanded="false"
        aria-label="{{ $unreadBadge !== '' ? $unreadBadge.' unread notifications' : 'Notifications' }}"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
        </svg>
        <span
            class="nav-bell__badge"
            data-notification-badge
            @if($unreadBadge === '') hidden @endif
        >{{ $unreadBadge }}</span>
    </button>

    <div class="nav-dropdown nav-notification__dropdown nav-dropdown-wrap" id="notification-dropdown" data-notification-dropdown aria-hidden="true">
        @include('layouts.partials.notification-feed', [
            'notifications' => \App\Models\Notification::query()
                ->where('user_id', auth()->id())
                ->recentFirst()
                ->limit(8)
                ->get(),
            'unreadCount' => \App\Models\Notification::query()
                ->where('user_id', auth()->id())
                ->unread()
                ->count(),
        ])
    </div>
</div>
