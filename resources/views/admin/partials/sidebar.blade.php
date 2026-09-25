<aside id="admin-sidebar" class="admin-sidebar" aria-label="Admin navigation">
    <div class="admin-sidebar-header">
        <button type="button" class="admin-sidebar-close" data-admin-sidebar-toggle aria-label="Close admin navigation">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <nav class="admin-sidebar-nav" aria-label="Administration">
        <p class="admin-sidebar-section-label">Operations</p>
        <ul>
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                    <span>Overview</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.complaints.index') }}" class="{{ request()->routeIs('admin.complaints.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.complaints.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>
                    <span>All complaints</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.departments.index') }}" class="{{ request()->routeIs('admin.departments.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.departments.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5l6-3 6 3v16"></path><path d="M9 9h1"></path><path d="M14 9h1"></path><path d="M9 13h1"></path><path d="M14 13h1"></path><path d="M9 17h1"></path><path d="M14 17h1"></path></svg>
                    <span>Departments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.audit.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                    <span>Audit trail</span>
                </a>
            </li>
        </ul>

        <p class="admin-sidebar-section-label">People</p>
        <ul>
            <li>
                <a href="{{ route('admin.staff.index') }}" class="{{ request()->routeIs('admin.staff.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.staff.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg>
                    <span>Staff accounts</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.invitations.index') }}" class="{{ request()->routeIs('admin.invitations.*') ? 'is-active' : '' }}" @if(request()->routeIs('admin.invitations.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
                    <span>Staff invitations</span>
                </a>
            </li>
        </ul>
    </nav>


</aside>
