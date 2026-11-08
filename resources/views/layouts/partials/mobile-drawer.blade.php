{{--
  Shared authenticated mobile drawer (≤640px). Desktop: display:none.
  Nav items are copied from each role's existing navigation (admin sidebar,
  staff sidebar, navbar links) to keep desktop output unchanged. If you
  change nav items, update the source partials too:
  - admin: resources/views/admin/partials/sidebar.blade.php
  - staff: resources/views/staff/partials/sidebar.blade.php
  - citizen: resources/views/layouts/navbar.blade.php
--}}
@php
    $role = auth()->user()->role;
    $name = auth()->user()->full_name;
    $roleLabel = match ($role) {
        'admin' => __('nav.administrator'),
        'staff' => __('nav.staff') . (auth()->user()->department?->name ? ' · ' . auth()->user()->department->name : ''),
        default => __('nav.citizen'),
    };
    $displayName = trim((string) $name) === trim((string) $roleLabel) ? (auth()->user()->email ?? $name) : $name;
@endphp

<div id="mobile-nav-overlay" class="mobile-nav-overlay" data-mobile-drawer-toggle aria-label="{{ __('nav.close_navigation') }}" tabindex="-1"></div>

<aside id="mobile-nav-drawer" class="mobile-nav-drawer" aria-label="{{ __('nav.navigation') }}" aria-hidden="true" inert>
    <div class="mobile-nav-drawer__account">
        <div class="mobile-nav-drawer__avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
        <div class="mobile-nav-drawer__account-copy">
            <span class="mobile-nav-drawer__name">{{ $displayName }}</span>
            <span class="mobile-nav-drawer__role">{{ $roleLabel }}</span>
        </div>
    </div>

    <nav class="mobile-nav-drawer__nav" aria-label="{{ __('nav.main_navigation') }}">
        @if($role === 'admin')
            <p class="mobile-nav-drawer__group">{{ __('nav.operations') }}</p>
            <ul class="mobile-nav-drawer__list">
                <li><a href="{{ route('admin.dashboard') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg><span>{{ __('nav.overview') }}</span></a></li>
                <li><a href="{{ route('admin.complaints.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.complaints.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.complaints.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg><span>{{ __('nav.all_complaints') }}</span></a></li>
                <li><a href="{{ route('admin.departments.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.departments.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.departments.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5l6-3 6 3v16"></path><path d="M9 9h1"></path><path d="M14 9h1"></path><path d="M9 13h1"></path><path d="M14 13h1"></path><path d="M9 17h1"></path><path d="M14 17h1"></path></svg><span>{{ __('nav.departments') }}</span></a></li>
                <li><a href="{{ route('admin.audit.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.audit.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.audit.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg><span>{{ __('nav.audit_trail') }}</span></a></li>
            </ul>
            <p class="mobile-nav-drawer__group">{{ __('nav.people') }}</p>
            <ul class="mobile-nav-drawer__list">
                <li><a href="{{ route('admin.users.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M22 11h-6"></path></svg><span>{{ __('nav.user_management') }}</span></a></li>
                <li><a href="{{ route('admin.staff.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.staff.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.staff.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg><span>{{ __('nav.staff_accounts') }}</span></a></li>
                <li><a href="{{ route('admin.invitations.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('admin.invitations.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.invitations.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"></path><path d="m22 8-10 6L2 8"></path></svg><span>{{ __('nav.staff_invitations') }}</span></a></li>
            </ul>
        @elseif($role === 'staff')
            <p class="mobile-nav-drawer__group">{{ __('nav.workspace') }}</p>
            <ul class="mobile-nav-drawer__list">
                <li><a href="{{ route('staff.dashboard') }}" class="mobile-nav-drawer__item {{ request()->routeIs('staff.dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('staff.dashboard')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg><span>{{ __('nav.dashboard') }}</span></a></li>
                <li><a href="{{ route('staff.complaints.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('staff.complaints.index') ? 'is-active' : '' }}" @if(request()->routeIs('staff.complaints.index')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg><span>{{ __('nav.complaint_queue') }}</span></a></li>
                <li><a href="{{ route('staff.activity.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('staff.activity.index') ? 'is-active' : '' }}" @if(request()->routeIs('staff.activity.index')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg><span>{{ __('nav.my_activity') }}</span></a></li>
            </ul>
            @if(auth()->user()->canVerifyComplaints())
                <p class="mobile-nav-drawer__group">{{ __('nav.verification') }}</p>
                <ul class="mobile-nav-drawer__list">
                    <li><a href="{{ route('staff.complaints.index', ['review' => 'pending']) }}" class="mobile-nav-drawer__item {{ request()->query('review') === 'pending' ? 'is-active' : '' }}" @if(request()->query('review') === 'pending') aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4"></path><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"></path></svg><span>{{ __('nav.needs_verification') }}</span></a></li>
                    <li><a href="{{ route('staff.complaints.index', ['review' => 'needs_information']) }}" class="mobile-nav-drawer__item {{ request()->query('review') === 'needs_information' ? 'is-active' : '' }}" @if(request()->query('review') === 'needs_information') aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16.5v.01"></path></svg><span>{{ __('nav.waiting_on_resident') }}</span></a></li>
                </ul>
            @endif
            <p class="mobile-nav-drawer__group">{{ __('nav.account') }}</p>
            <ul class="mobile-nav-drawer__list">
            </ul>
        @else
            <p class="mobile-nav-drawer__group">{{ __('nav.menu') }}</p>
            <ul class="mobile-nav-drawer__list">
                <li><a href="{{ route('dashboard') }}" class="mobile-nav-drawer__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg><span>{{ __('nav.dashboard') }}</span></a></li>
                <li><a href="{{ url('/transparency') }}" class="mobile-nav-drawer__item {{ request()->is('transparency*') ? 'is-active' : '' }}" @if(request()->is('transparency*')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"></path><path d="m7 14 3-3 3 3 5-6"></path></svg><span>{{ __('nav.transparency') }}</span></a></li>
                <li><a href="{{ route('complaints.index') }}" class="mobile-nav-drawer__item {{ request()->routeIs('complaints.index') ? 'is-active' : '' }}" @if(request()->routeIs('complaints.index')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg><span>{{ __('nav.my_complaints') }}</span></a></li>
                @if(!request()->routeIs('dashboard'))
                <li><a href="{{ route('complaints.create') }}" class="mobile-nav-drawer__item {{ request()->routeIs('complaints.create') ? 'is-active' : '' }}" @if(request()->routeIs('complaints.create')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg><span>{{ __('nav.file_complaint') }}</span></a></li>
                @endif
            </ul>
        @endif
    </nav>

    <div class="mobile-nav-drawer__actions">
        <div class="mobile-nav-drawer__language">
            <span class="mobile-nav-drawer__group">{{ __('nav.language') }}</span>
            @include('layouts.partials.language-switcher', ['variant' => 'mobile'])
        </div>
        <a href="{{ route('settings', ['tab' => 'preferences']) }}" class="mobile-nav-drawer__item mobile-nav-drawer__action {{ request()->routeIs('settings*') ? 'is-active' : '' }}" @if(request()->routeIs('settings*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span>{{ __('nav.settings') }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="mobile-nav-drawer__logout-form">
            @csrf
            <button type="submit" class="mobile-nav-drawer__item mobile-nav-drawer__action mobile-nav-drawer__action--danger">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>{{ __('nav.log_out') }}</span>
            </button>
        </form>
    </div>
</aside>
