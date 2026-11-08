<aside id="staff-sidebar" class="staff-sidebar" aria-label="Staff navigation">
    <div class="staff-sidebar-header">
        <button type="button" class="staff-sidebar-close" data-staff-sidebar-toggle aria-label="{{ __('nav.close_navigation') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <div class="staff-department-context">
        <span class="staff-department-label">{{ __('nav.assigned_department') }}</span>
        <strong>{{ auth()->user()->department?->name ?? __('nav.department_pending') }}</strong>
        @if(auth()->user()->department?->code)<span class="dept-chip" data-dept="{{ auth()->user()->department->code }}">{{ auth()->user()->department->code }}</span>@endif
    </div>

    <nav class="staff-sidebar-nav" aria-label="{{ __('nav.navigation') }}">
        <p class="staff-sidebar-section-label">{{ __('nav.workspace') }}</p>
        <ul>
            <li>
                <a href="{{ route('staff.dashboard') }}" class="{{ request()->routeIs('staff.dashboard') ? 'is-active' : '' }}" @if(request()->routeIs('staff.dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                    <span>{{ __('nav.dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('staff.complaints.index') }}" class="{{ request()->routeIs('staff.complaints.index') ? 'is-active' : '' }}" @if(request()->routeIs('staff.complaints.index')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>
                    <span>{{ __('nav.complaint_queue') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('staff.activity.index') }}" class="{{ request()->routeIs('staff.activity.index') ? 'is-active' : '' }}" @if(request()->routeIs('staff.activity.index')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                    <span>{{ __('nav.my_activity') }}</span>
                </a>
            </li>
        </ul>

        {{-- Gated on a capability, not a role: the first link in the workspace
             that only some staff members see. It leads to the same queue with
             the pending review filter pre-applied - no separate dashboard. --}}
        @if(auth()->user()->canVerifyComplaints())
            <p class="staff-sidebar-section-label">{{ __('nav.verification') }}</p>
            <ul>
                <li>
                    <a href="{{ route('staff.complaints.index', ['review' => 'pending']) }}" class="{{ request()->query('review') === 'pending' ? 'is-active' : '' }}" @if(request()->query('review') === 'pending') aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4"></path><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"></path></svg>
                        <span>{{ __('nav.needs_verification') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.complaints.index', ['review' => 'needs_information']) }}" class="{{ request()->query('review') === 'needs_information' ? 'is-active' : '' }}" @if(request()->query('review') === 'needs_information') aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16.5v.01"></path></svg>
                        <span>{{ __('nav.waiting_on_resident') }}</span>
                    </a>
                </li>
            </ul>
        @endif

        <p class="staff-sidebar-section-label">{{ __('nav.language') }}</p>
        <ul>
            <li class="staff-sidebar-language">
                @include('layouts.partials.language-switcher', ['variant' => 'compact'])
            </li>
        </ul>
        <p class="staff-sidebar-section-label">{{ __('nav.account') }}</p>
        <ul>
            <li>
                <a href="{{ route('settings', ['tab' => 'preferences']) }}" class="{{ request()->routeIs('settings*') ? 'is-active' : '' }}" @if(request()->routeIs('settings*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>{{ __('nav.settings') }}</span>
                </a>
            </li>
        </ul>
    </nav>


</aside>
