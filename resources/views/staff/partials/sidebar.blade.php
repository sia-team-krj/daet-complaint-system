@php
    $sidebarUser = auth()->user();
    $sidebarDept = $sidebarUser?->department;
    $workspaceActive = request()->routeIs('staff.dashboard', 'staff.verification.*', 'staff.complaints.*', 'staff.activity.*', 'staff.department-activity.*');
    $officeActive = request()->routeIs('staff.office.*');
    $publicationActive = request()->routeIs('staff.publication-requests.*');
@endphp
<aside id="staff-sidebar" class="staff-sidebar" aria-label="{{ __('nav.navigation') }}">
    <div class="staff-sidebar__panel">
        <div class="staff-sidebar__header">
            <div class="staff-sidebar__heading">
                <span class="staff-sidebar__eyebrow">{{ __('nav.workspace') }}</span>
                <span class="staff-sidebar__department" title="{{ $sidebarDept?->name ?? __('nav.department_pending') }}">{{ $sidebarDept?->name ?? __('nav.department_pending') }}</span>
                @if($sidebarDept?->code)
                    <span class="staff-sidebar__dept-code dept-chip" data-dept="{{ $sidebarDept->code }}">{{ $sidebarDept->code }}</span>
                @endif
            </div>
            <div class="staff-sidebar__header-actions">
                <button type="button" class="staff-sidebar__drawer-close" data-staff-sidebar-toggle aria-label="{{ __('nav.close_navigation') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <button type="button" class="staff-sidebar__collapse" data-staff-sidebar-collapse
                        aria-label="{{ __('nav.collapse_sidebar') }}" title="{{ __('nav.collapse_sidebar') }}"
                        aria-controls="staff-sidebar" aria-expanded="true"
                        data-tooltip="{{ __('nav.collapse_sidebar') }}"
                        data-tooltip-expand="{{ __('nav.expand_sidebar') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6"></path></svg>
                </button>
            </div>
        </div>

        <nav class="staff-sidebar__nav" aria-label="{{ __('nav.navigation') }}">
            <div class="staff-sidebar__scroll">
                <ul class="staff-sidebar__list">
                    <li>
                        <a class="staff-sidebar__link {{ request()->routeIs('staff.dashboard') ? 'is-active' : '' }}"
                           href="{{ route('staff.dashboard') }}" @if(request()->routeIs('staff.dashboard')) aria-current="page" @endif
                           data-tooltip="{{ __('nav.dashboard') }}">
                            <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                            <span class="staff-sidebar__label">{{ __('nav.dashboard') }}</span>
                        </a>
                    </li>
                </ul>

                <details class="staff-sidebar-section {{ $workspaceActive ? 'is-current' : '' }}" data-sidebar-section="workspace" open>
                    <summary class="staff-sidebar-section__summary" aria-expanded="true" aria-controls="staff-sidebar-workspace" data-tooltip="{{ __('nav.workspace') }}">
                        <span class="staff-sidebar-section__head">
                            <svg class="staff-sidebar__icon staff-sidebar-section__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M3 12h18"></path></svg>
                            <span class="staff-sidebar__label staff-sidebar-section__label">{{ __('nav.workspace') }}</span>
                        </span>
                        <svg class="staff-sidebar-section__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                    </summary>
                    <div class="staff-sidebar-section__body" id="staff-sidebar-workspace">
                        <div class="staff-sidebar-section__inner">
                            <ul class="staff-sidebar__list staff-sidebar__list--nested">
                                @if($sidebarUser?->canVerifyComplaints())
                                    <li>
                                        <a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.verification.*') ? 'is-active' : '' }}"
                                           href="{{ route('staff.verification.index', ['review' => 'pending']) }}" @if(request()->routeIs('staff.verification.*')) aria-current="page" @endif
                                           data-tooltip="{{ __('nav.needs_verification') }}">
                                            <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4"></path><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"></path></svg>
                                            <span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.needs_verification') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.complaints.*') ? 'is-active' : '' }}"
                                       href="{{ route('staff.complaints.index') }}" @if(request()->routeIs('staff.complaints.*')) aria-current="page" @endif
                                       data-tooltip="{{ $sidebarUser?->canVerifyComplaints() ? __('nav.complaint_queue') : __('nav.my_work') }}">
                                        <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>
                                        <span class="staff-sidebar__label staff-sidebar__label--wrap">{{ $sidebarUser?->canVerifyComplaints() ? __('nav.complaint_queue') : __('nav.my_work') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.activity.*') ? 'is-active' : '' }}"
                                       href="{{ route('staff.activity.index') }}" @if(request()->routeIs('staff.activity.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.my_activity') }}">
                                        <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                                        <span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.my_activity') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.department-activity.*') ? 'is-active' : '' }}"
                                       href="{{ route('staff.department-activity.index') }}" @if(request()->routeIs('staff.department-activity.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.department_activity') }}">
                                        <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg>
                                        <span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.department_activity') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </details>

                @if(auth()->user()->isDepartmentAdmin())
                    <details class="staff-sidebar-section {{ $officeActive ? 'is-current' : '' }}" data-sidebar-section="office" open>
                        <summary class="staff-sidebar-section__summary" aria-expanded="true" aria-controls="staff-sidebar-office" data-tooltip="{{ __('nav.office') }}">
                            <span class="staff-sidebar-section__head">
                                <svg class="staff-sidebar__icon staff-sidebar-section__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V8l6-4 6 4v13"></path><path d="M10 10h4"></path></svg>
                                <span class="staff-sidebar__label staff-sidebar-section__label">{{ __('nav.office') }}</span>
                            </span>
                            <svg class="staff-sidebar-section__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                        </summary>
                        <div class="staff-sidebar-section__body" id="staff-sidebar-office">
                            <div class="staff-sidebar-section__inner">
                                <ul class="staff-sidebar__list staff-sidebar__list--nested">
                                    <li><a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.office.index') ? 'is-active' : '' }}" href="{{ route('staff.office.index') }}" @if(request()->routeIs('staff.office.index')) aria-current="page" @endif data-tooltip="{{ __('nav.manage_office') }}"><svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V8l6-4 6 4v13"></path><path d="M10 10h4"></path></svg><span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.manage_office') }}</span></a></li>
                                    <li><a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.office.categories.*') ? 'is-active' : '' }}" href="{{ route('staff.office.categories.index') }}" @if(request()->routeIs('staff.office.categories.*')) aria-current="page" @endif data-tooltip="{{ __('nav.manage_categories') }}"><svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Z"></path><path d="m2 17 10 5 10-5"></path><path d="m2 12 10 5 10-5"></path></svg><span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.manage_categories') }}</span></a></li>
                                    <li><a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.office.statistics.*') ? 'is-active' : '' }}" href="{{ route('staff.office.statistics.index') }}" @if(request()->routeIs('staff.office.statistics.*')) aria-current="page" @endif data-tooltip="{{ __('nav.office_statistics') }}"><svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"></path><path d="M7 14l3-3 3 3 4-5"></path></svg><span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.office_statistics') }}</span></a></li>
                                    <li><a class="staff-sidebar__link staff-sidebar__link--nested {{ request()->routeIs('staff.office.audit.*') ? 'is-active' : '' }}" href="{{ route('staff.office.audit.index') }}" @if(request()->routeIs('staff.office.audit.*')) aria-current="page" @endif data-tooltip="{{ __('nav.office_audit') }}"><svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg><span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('nav.office_audit') }}</span></a></li>
                                </ul>
                            </div>
                        </div>
                    </details>
                @endif

                @if(\App\Services\ComplaintPrivacyService::canApprove(auth()->user()))
                    <ul class="staff-sidebar__list staff-sidebar__list--solo">
                        <li>
                            <a class="staff-sidebar__link {{ $publicationActive ? 'is-active' : '' }}"
                               href="{{ route('staff.publication-requests.index') }}" @if($publicationActive) aria-current="page" @endif
                               data-tooltip="{{ __('staff.publication.queue_title') }}">
                                <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <span class="staff-sidebar__label staff-sidebar__label--wrap">{{ __('staff.publication.queue_title') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif
            </div>

            <div class="staff-sidebar__footer">
                <ul class="staff-sidebar__list">
                    <li>
                        <a class="staff-sidebar__link {{ request()->routeIs('settings*') ? 'is-active' : '' }}"
                           href="{{ route('settings', ['tab' => 'preferences']) }}" @if(request()->routeIs('settings*')) aria-current="page" @endif
                           data-tooltip="{{ __('nav.settings') }}">
                            <svg class="staff-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            <span class="staff-sidebar__label">{{ __('nav.settings') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>
