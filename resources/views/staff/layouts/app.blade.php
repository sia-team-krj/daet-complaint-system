@extends('layouts.app')

@section('content')
<div class="staff-shell @hasSection('staff-aside') staff-shell--with-aside @endif">
    @include('staff.partials.sidebar')

    <button
        type="button"
        class="staff-sidebar-overlay"
        data-staff-sidebar-toggle
        aria-label="Close staff navigation"
        tabindex="-1"
    ></button>

    <section class="staff-workspace" aria-label="Department workspace">
        <div class="staff-content">
            @yield('staff-content')
        </div>
    </section>

    @hasSection('staff-aside')
        <aside class="staff-aside-column" aria-label="{{ __('staff.dashboard.activity_title') }}">
            @yield('staff-aside')
        </aside>
    @endif

    @php
        // The on-field role's tab bar opens on its own home; every other
        // department account keeps the queue as its "My Tasks".
        $isFieldStaff = auth()->user()?->role === 'staff';
        $myTasksHref = $isFieldStaff ? route('staff.field.home') : route('staff.complaints.index');
        $myTasksActive = request()->routeIs('staff.field.home', 'staff.complaints.*');
    @endphp
    <nav class="staff-bottom-nav" aria-label="{{ __('staff.mobile.nav') }}">
        <div class="staff-bottom-nav__grid">
            <a href="{{ $myTasksHref }}" class="staff-bottom-nav__item {{ $myTasksActive ? 'is-active' : '' }}" @if($myTasksActive) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>{{ __('staff.mobile.my_tasks') }}</span>
            </a>
            <a href="{{ route('staff.activity.index') }}" class="staff-bottom-nav__item {{ request()->routeIs('staff.activity.*', 'staff.department-activity.*') ? 'is-active' : '' }}" @if(request()->routeIs('staff.activity.*', 'staff.department-activity.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ __('staff.mobile.activity') }}</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="staff-bottom-nav__item {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}" @if(request()->routeIs('notifications.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span>{{ __('nav.notifications') }}</span>
            </a>
            <a href="{{ route('settings') }}" class="staff-bottom-nav__item {{ request()->routeIs('settings*', 'profile.*') ? 'is-active' : '' }}" @if(request()->routeIs('settings*', 'profile.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>{{ __('nav.profile') }}</span>
            </a>
        </div>
        @hasSection('staff-footer')
            <p class="staff-bottom-nav__caption">@yield('staff-footer')</p>
        @endif
    </nav>
</div>
@endsection
