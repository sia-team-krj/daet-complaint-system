@php
    $manageActive = request()->routeIs([
        'admin.complaints.*',
        'admin.departments.*',
        'admin.categories.*',
        'admin.identity.*',
        'admin.audit.*',
        'admin.feedback.*',
        'admin.faqs.*',
    ]);
    $peopleActive = request()->routeIs([
        'admin.users.*',
        'admin.staff.*',
        'admin.invitations.*',
    ]);
@endphp

<aside id="admin-sidebar" class="admin-sidebar" aria-label="{{ __('nav.administrator') }}">
    <div class="admin-sidebar__panel">
        <div class="admin-sidebar__header">
            <span class="admin-sidebar__eyebrow">{{ __('nav.workspace') }}</span>
            <button type="button" class="admin-sidebar__collapse" data-admin-sidebar-collapse
                    aria-label="{{ __('nav.collapse_sidebar') }}" title="{{ __('nav.collapse_sidebar') }}"
                    aria-controls="admin-sidebar" aria-expanded="true"
                    data-tooltip="{{ __('nav.collapse_sidebar') }}"
                    data-tooltip-expand="{{ __('nav.expand_sidebar') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6"></path></svg>
            </button>
        </div>

        <nav class="admin-sidebar__nav" aria-label="{{ __('nav.navigation') }}">
            <div class="admin-sidebar__scroll">
                <ul class="admin-sidebar__list">
                    <li>
                        <a class="admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                           href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                           data-tooltip="{{ __('nav.overview') }}">
                            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                            <span class="admin-sidebar__label">{{ __('nav.overview') }}</span>
                        </a>
                    </li>
                </ul>

                {{-- Manage: complaint operations and platform content. --}}
                <details class="admin-sidebar-section {{ $manageActive ? 'is-current' : '' }}"
                         data-sidebar-section="manage" open>
                    <summary class="admin-sidebar-section__summary" aria-expanded="true"
                             aria-controls="admin-sidebar-manage" data-tooltip="{{ __('nav.manage') }}">
                        <span class="admin-sidebar-section__head">
                            <svg class="admin-sidebar__icon admin-sidebar-section__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M3 12h18"></path></svg>
                            <span class="admin-sidebar__label admin-sidebar-section__label">{{ __('nav.manage') }}</span>
                        </span>
                        <svg class="admin-sidebar-section__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                    </summary>
                    <div class="admin-sidebar-section__body" id="admin-sidebar-manage">
                        <div class="admin-sidebar-section__inner">
                            <ul class="admin-sidebar__list admin-sidebar__list--nested">
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.complaints.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.complaints.index') }}" @if(request()->routeIs('admin.complaints.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.all_complaints') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.all_complaints') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.departments.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.departments.index') }}" @if(request()->routeIs('admin.departments.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.departments') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M6 21V5l6-3 6 3v16"></path><path d="M9 9h1"></path><path d="M14 9h1"></path><path d="M9 13h1"></path><path d="M14 13h1"></path><path d="M9 17h1"></path><path d="M14 17h1"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.departments') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.categories.index') }}" @if(request()->routeIs('admin.categories.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.complaint_categories') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Z"></path><path d="m2 17 10 5 10-5"></path><path d="m2 12 10 5 10-5"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.complaint_categories') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.identity.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.identity.index') }}" @if(request()->routeIs('admin.identity.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('identity.admin.queue_title') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 4v5c0 4.4-3 8.3-7 9-4-.7-7-4.6-7-9V7z"></path><path d="m9 12 2 2 4-4"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('identity.admin.queue_title') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.audit.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.audit.index') }}" @if(request()->routeIs('admin.audit.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.audit_trail') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.audit_trail') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.feedback.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.feedback.index') }}" @if(request()->routeIs('admin.feedback.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.resident_feedback') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.resident_feedback') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.faqs.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.faqs.index') }}" @if(request()->routeIs('admin.faqs.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.faqs') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-2.9 2.5-2.9 4"></path><path d="M12 17h.01"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.faqs') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </details>

                {{-- People: account and staff administration. --}}
                <details class="admin-sidebar-section {{ $peopleActive ? 'is-current' : '' }}"
                         data-sidebar-section="people" open>
                    <summary class="admin-sidebar-section__summary" aria-expanded="true"
                             aria-controls="admin-sidebar-people" data-tooltip="{{ __('nav.people') }}">
                        <span class="admin-sidebar-section__head">
                            <svg class="admin-sidebar__icon admin-sidebar-section__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M22 11h-6"></path></svg>
                            <span class="admin-sidebar__label admin-sidebar-section__label">{{ __('nav.people') }}</span>
                        </span>
                        <svg class="admin-sidebar-section__chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                    </summary>
                    <div class="admin-sidebar-section__body" id="admin-sidebar-people">
                        <div class="admin-sidebar-section__inner">
                            <ul class="admin-sidebar__list admin-sidebar__list--nested">
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.user_management') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="m16 11 2 2 4-4"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.user_management') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.staff.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.staff.index') }}" @if(request()->routeIs('admin.staff.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.staff_accounts') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.staff_accounts') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="admin-sidebar__link admin-sidebar__link--nested {{ request()->routeIs('admin.invitations.*') ? 'is-active' : '' }}"
                                       href="{{ route('admin.invitations.index') }}" @if(request()->routeIs('admin.invitations.*')) aria-current="page" @endif
                                       data-tooltip="{{ __('nav.staff_invitations') }}">
                                        <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>
                                        <span class="admin-sidebar__label admin-sidebar__label--wrap">{{ __('nav.staff_invitations') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </details>
            </div>

            <div class="admin-sidebar__footer">
                <ul class="admin-sidebar__list">
                    <li>
                        <a class="admin-sidebar__link {{ request()->routeIs('settings*') ? 'is-active' : '' }}"
                           href="{{ route('settings', ['tab' => 'preferences']) }}" @if(request()->routeIs('settings*')) aria-current="page" @endif
                           data-tooltip="{{ __('nav.settings') }}">
                            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            <span class="admin-sidebar__label">{{ __('nav.settings') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>
