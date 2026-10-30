<aside id="staff-sidebar" class="staff-sidebar" aria-label="Staff navigation">
    <div class="staff-sidebar-header">
        <button type="button" class="staff-sidebar-close" data-staff-sidebar-toggle aria-label="Close staff navigation">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <div class="staff-department-context">
        <span class="staff-department-label">Assigned department</span>
        <strong>{{ auth()->user()->department?->name ?? 'Department pending' }}</strong>
        @if(auth()->user()->department?->code)<span>{{ auth()->user()->department->code }}</span>@endif
    </div>

    <nav class="staff-sidebar-nav" aria-label="Staff sections">
        <p class="staff-sidebar-section-label">Workspace</p>
        <ul>
            <li>
                <a href="{{ route('staff.dashboard') }}" class="{{ request()->routeIs('staff.dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('staff.dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('staff.complaints.index') }}" class="{{ request()->routeIs('staff.complaints.index') ? 'is-active' : '' }}" @if(request()->routeIs('staff.complaints.index')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>
                    <span>Complaint queue</span>
                </a>
            </li>
            <li>
                <a href="{{ route('staff.activity.index') }}" class="{{ request()->routeIs('staff.activity.index') ? 'is-active' : '' }}" @if(request()->routeIs('staff.activity.index')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                    <span>My activity</span>
                </a>
            </li>
        </ul>

        {{-- Gated on a capability, not a role: the first link in the workspace
             that only some staff members see. It leads to the same queue with
             the pending review filter pre-applied - no separate dashboard. --}}
        @if(auth()->user()->canVerifyComplaints())
            <p class="staff-sidebar-section-label">Verification</p>
            <ul>
                <li>
                    <a href="{{ route('staff.complaints.index', ['review' => 'pending']) }}" class="{{ request()->query('review') === 'pending' ? 'is-active' : '' }}" @if(request()->query('review') === 'pending') aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4"></path><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"></path></svg>
                        <span>Needs verification</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.complaints.index', ['review' => 'needs_information']) }}" class="{{ request()->query('review') === 'needs_information' ? 'is-active' : '' }}" @if(request()->query('review') === 'needs_information') aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16.5v.01"></path></svg>
                        <span>Waiting on resident</span>
                    </a>
                </li>
            </ul>
        @endif

        <p class="staff-sidebar-section-label">Account</p>
        <ul>
            <li>
                <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'is-active' : '' }}" @if(request()->routeIs('profile')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"></circle><path d="M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path></svg>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>


</aside>
