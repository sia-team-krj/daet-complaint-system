@extends('layouts.app')

@section('content')
<div class="staff-shell">
    @include('staff.partials.sidebar')

    <button
        type="button"
        class="staff-sidebar-overlay"
        data-staff-sidebar-toggle
        aria-label="Close staff navigation"
        tabindex="-1"
    ></button>

    <section class="staff-workspace" aria-label="Department workspace">
        <div class="staff-mobile-toolbar">
            <button
                type="button"
                class="staff-mobile-menu-button"
                data-staff-sidebar-toggle
                aria-controls="staff-sidebar"
                aria-expanded="false"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="4" y1="6" x2="20" y2="6"></line>
                    <line x1="4" y1="12" x2="20" y2="12"></line>
                    <line x1="4" y1="18" x2="20" y2="18"></line>
                </svg>
                <span>Staff menu</span>
            </button>
            <span class="staff-mobile-toolbar-title">{{ auth()->user()->department?->name ?? 'Department workspace' }}</span>
        </div>

        <div class="staff-content">
            @yield('staff-content')
        </div>
    </section>
</div>
@endsection
