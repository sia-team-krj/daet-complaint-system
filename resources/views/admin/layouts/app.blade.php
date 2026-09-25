@extends('layouts.app')

@section('content')
<div class="admin-shell">
    @include('admin.partials.sidebar')

    <button
        type="button"
        class="admin-sidebar-overlay"
        data-admin-sidebar-toggle
        aria-label="Close admin navigation"
        tabindex="-1"
    ></button>

    <section class="admin-workspace" aria-label="Administration workspace">
        <div class="admin-mobile-toolbar">
            <button
                type="button"
                class="admin-mobile-menu-button"
                data-admin-sidebar-toggle
                aria-controls="admin-sidebar"
                aria-expanded="false"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="4" y1="6" x2="20" y2="6"></line>
                    <line x1="4" y1="12" x2="20" y2="12"></line>
                    <line x1="4" y1="18" x2="20" y2="18"></line>
                </svg>
                <span>Admin menu</span>
            </button>
            <span class="admin-mobile-toolbar-title">Daet Listens Admin</span>
        </div>

        <div class="admin-content">
            @yield('admin-content')
        </div>
    </section>
</div>
@endsection
