            {{-- RIGHT COLUMN: who reported, where, and what to do next --}}
            <div class="staff-detail-col staff-detail-col--side">
                {{-- 1. Resident Information Card --}}
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--resident">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <h2 class="tr-h2">Resident information</h2>
                    </div>
                    <div class="report-row__head" style="margin: 10px 0 6px;">
                        <span class="ticket-id">{{ $complaint->ticket_id }}</span>
                        @if($complaint->department)
                            <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                        @endif
                    </div>
                    <p class="report-row__meta" style="margin-top: 0;">
                        {{ $complaint->category_label ?? $complaint->category }}
                        · {{ $complaint->department?->name ?? __('nav.departments') }}
                    </p>
                    @php
                        $residentAlias = $complaint->user?->staff_safe_name ?? 'Resident unavailable';
                        $trust = $complaint->user?->trustScoreSummary();
                    @endphp
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 14px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--navy-800); color: var(--gold-300); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0;">
                            {{ strtoupper(substr($residentAlias, 0, 1)) }}
                        </div>
                        <div>
                            <strong style="display: block; font-size: 14px; color: var(--navy-900);">{{ $residentAlias }}</strong>
                            <span style="font-size: 11px; color: var(--faint);">Identity protected · Staff view</span>
                        </div>
                    </div>
                    @if($trust)
                        <div style="margin-top: 12px; padding: 10px 12px; border: 1px solid var(--line-soft); border-radius: var(--radius-sm); background: var(--surface-soft);">
                            <div style="display: flex; justify-content: space-between; gap: 8px; align-items: center;">
                                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">{{ __('staff.trust.title') }}</span>
                                <strong style="font-size: 13px; color: var(--navy-900);">{{ $trust['score'] }}/100</strong>
                            </div>
                            <p style="margin: 4px 0 0; font-size: 12px; color: var(--text);">{{ $trust['label'] }} · {{ __('staff.trust.summary', ['good' => $trust['good'], 'bad' => $trust['bad']]) }}</p>
                        </div>
                    @endif
                    <p style="font-size: 11px; color: var(--faint); line-height: 1.45; margin: 10px 0 0; border-top: 1px solid var(--line-soft); padding-top: 8px;">
                        Strict privacy controls apply. Personal contact details remain confidential.
                    </p>
                </div>

                {{-- 2. Complaint Location & Map Card --}}
                @php
                    $hasExactPin = $complaint->latitude !== null && $complaint->longitude !== null;
                    $locationBarangay = filled($complaint->barangay) ? $complaint->barangay : $complaint->user?->barangay;
                    $directionsDestination = $hasExactPin
                        ? number_format((float) $complaint->latitude, 6, '.', '').','.number_format((float) $complaint->longitude, 6, '.', '')
                        : null;
                @endphp
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--location location-card" style="overflow: hidden; padding: 0;">
                    <div class="workspace-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--line-soft);">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <h2 class="tr-h2">{{ __('staff.location.title') }}</h2>
                    </div>
                    @if($hasExactPin)
                        <div id="complaint-map" class="map-container"
                             role="region"
                             aria-label="{{ __('staff.location.map_aria') }}"
                             data-latitude="{{ (float) $complaint->latitude }}"
                             data-longitude="{{ (float) $complaint->longitude }}"
                             data-map-unavailable="{{ __('staff.location.map_unavailable') }}"
                             style="isolation: isolate; z-index: 0;"></div>
                    @else
                        <div class="location-card__empty">
                            <span class="location-card__empty-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </span>
                            <strong>{{ __('staff.location.no_pin_title') }}</strong>
                            <p>{{ __('staff.location.no_pin_body') }}</p>
                        </div>
                    @endif
                    @if($hasExactPin || filled($locationBarangay))
                        <div class="location-card__footer">
                            @if(filled($locationBarangay))
                                <span class="location-card__meta">{{ __('staff.location.barangay_label') }}: <strong>{{ $locationBarangay }}</strong></span>
                            @endif
                            @if($hasExactPin)
                                <a class="tr-button location-card__directions"
                                   href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $directionsDestination }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   aria-label="{{ __('staff.location.directions_aria') }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                                    <span>{{ __('staff.location.directions') }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Assignment is available to office verifiers (DA and staff with
                     verification rights) for their own office, so work can be
                     assigned right after verification. Custom picker shows
                     workload and highlights the least-loaded recommendation. --}}
                @if($canAssignComplaint ?? false)
                    @php
                        $selectedAssigneeId = (string) old('assigned_staff_id', $complaint->assigned_staff_id ?? '');
                        $currentAssigneeName = $complaint->assignedStaff?->full_name ?? __('staff.assignment.unassigned');
                    @endphp
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--assignment staff-action-panel" style="border-top: 3px solid var(--gold-500);" data-assign-picker-root>
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                            </div>
                            <h2 class="tr-h2">{{ __('staff.assignment.title') }}</h2>
                        </div>

                        <div class="staff-action-panel__section">
                            <p class="staff-action-panel__hint">{{ __('staff.assignment.lede_verifier') }}</p>
                            <p class="staff-action-panel__hint">
                                <strong>{{ __('staff.assignment.current') }}:</strong>
                                <span data-assign-current>{{ $currentAssigneeName }}</span>
                            </p>

                            @if(($recommendedAssigneeId ?? null) && !$complaint->assigned_staff_id)
                                <div class="assign-reco-banner" role="status">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/></svg>
                                    <span>{{ __('staff.assignment.recommendation_hint') }}</span>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('staff.office.complaints.assign', $complaint) }}" data-assign-form>
                                @csrf
                                <div class="staff-field">
                                    <label id="assigned-staff-label">{{ __('staff.assignment.label') }}</label>
                                    <input type="hidden" name="assigned_staff_id" id="assigned-staff-value" value="{{ $selectedAssigneeId }}">

                                    <div class="assign-picker" data-assign-picker>
                                        <button type="button" class="assign-picker__trigger" data-assign-trigger aria-haspopup="listbox" aria-expanded="false" aria-labelledby="assigned-staff-label">
                                            <span class="assign-picker__trigger-main" data-assign-trigger-text>
                                                @if($selectedAssigneeId === '')
                                                    <span class="assign-picker__placeholder">{{ __('staff.assignment.choose') }}</span>
                                                @else
                                                    {{ $officeAssignees->firstWhere('id', (int) $selectedAssigneeId)?->full_name ?? __('staff.assignment.choose') }}
                                                @endif
                                            </span>
                                            <svg class="assign-picker__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>

                                        <div class="assign-picker__panel" data-assign-panel hidden>
                                            <div class="assign-picker__search-wrap">
                                                <svg class="assign-picker__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                                <input type="search" class="assign-picker__search" data-assign-search placeholder="{{ __('staff.assignment.search_placeholder') }}" autocomplete="off" aria-label="{{ __('staff.assignment.search_placeholder') }}">
                                            </div>

                                            <ul class="assign-picker__list" role="listbox" aria-labelledby="assigned-staff-label" data-assign-list>
                                                <li>
                                                    <button type="button" class="assign-option {{ $selectedAssigneeId === '' ? 'is-selected' : '' }}" role="option" aria-selected="{{ $selectedAssigneeId === '' ? 'true' : 'false' }}" data-value="" data-name="{{ __('staff.assignment.unassigned') }}" data-search="{{ \Illuminate\Support\Str::lower(__('staff.assignment.unassigned')) }}">
                                                        <span class="assign-option__avatar assign-option__avatar--none" aria-hidden="true">—</span>
                                                        <span class="assign-option__body">
                                                            <span class="assign-option__name">{{ __('staff.assignment.unassigned') }}</span>
                                                            <span class="assign-option__meta">{{ __('staff.assignment.unassigned_hint') }}</span>
                                                        </span>
                                                        <span class="assign-option__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                                                    </button>
                                                </li>
                                                @foreach($officeAssignees as $assignee)
                                                    @php
                                                        $isSelected = $selectedAssigneeId === (string) $assignee->id;
                                                        $isRecommended = ($recommendedAssigneeId ?? null) === $assignee->id;
                                                        $initials = collect(explode(' ', trim($assignee->full_name)))->filter()->take(2)->map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)))->implode('');
                                                        $openLoad = (int) ($assignee->open_load ?? 0);
                                                        $overdueLoad = (int) ($assignee->overdue_load ?? 0);
                                                        $search = \Illuminate\Support\Str::lower($assignee->full_name.' '.($assignee->isDepartmentAdmin() ? 'department administrator' : 'staff'));
                                                    @endphp
                                                    <li>
                                                        <button type="button" class="assign-option {{ $isSelected ? 'is-selected' : '' }} {{ $isRecommended ? 'is-recommended' : '' }}" role="option" aria-selected="{{ $isSelected ? 'true' : 'false' }}" data-value="{{ $assignee->id }}" data-name="{{ $assignee->full_name }}" data-search="{{ $search }}">
                                                            <span class="assign-option__avatar" aria-hidden="true">{{ $initials }}</span>
                                                            <span class="assign-option__body">
                                                                <span class="assign-option__top">
                                                                    <span class="assign-option__name">{{ $assignee->full_name }}</span>
                                                                    @if($isRecommended)
                                                                        <span class="assign-badge assign-badge--recommended"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/></svg> {{ __('staff.assignment.recommended') }}</span>
                                                                    @endif
                                                                </span>
                                                                <span class="assign-option__meta">
                                                                    <span class="assign-badge {{ $assignee->isDepartmentAdmin() ? 'assign-badge--role-da' : 'assign-badge--role-staff' }}">{{ $assignee->isDepartmentAdmin() ? __('roles.department_admin') : __('roles.staff') }}</span>
                                                                    <span class="assign-badge assign-badge--load">{{ trans_choice('staff.assignment.open_load', $openLoad, ['count' => $openLoad]) }}</span>
                                                                    @if($overdueLoad > 0)
                                                                        <span class="assign-badge assign-badge--overdue">{{ trans_choice('staff.assignment.overdue_load', $overdueLoad, ['count' => $overdueLoad]) }}</span>
                                                                    @else
                                                                        <span class="assign-badge assign-badge--ok">{{ __('staff.assignment.no_overdue') }}</span>
                                                                    @endif
                                                                </span>
                                                            </span>
                                                            <span class="assign-option__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <p class="assign-picker__empty" data-assign-empty hidden>{{ __('staff.assignment.no_matches') }}</p>
                                        </div>
                                    </div>
                                    @error('assigned_staff_id')<p class="staff-field__error">{{ $message }}</p>@enderror
                                </div>

                                <button type="submit" class="staff-action-panel__submit">{{ __('staff.assignment.save') }}</button>
                            </form>

                            <p class="staff-action-panel__hint">{{ __('staff.assignment.hint') }}</p>
                        </div>
                    </div>

                    <style>
                        [data-assign-picker] { position: relative; }
                        .assign-picker__trigger {
                            width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 10px;
                            padding: 10px 12px; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px;
                            background: var(--surface); color: var(--text-primary, var(--text)); font: inherit; cursor: pointer;
                            text-align: left; transition: border-color .15s ease, box-shadow .15s ease;
                        }
                        .assign-picker__trigger:hover { border-color: var(--navy-300, var(--dept-active-border)); }
                        .assign-picker__trigger:focus-visible { outline: none; border-color: var(--navy-400, var(--primary)); box-shadow: var(--ring, 0 0 0 3px rgba(30,58,95,.15)); }
                        .assign-picker__placeholder { color: var(--text-secondary, var(--muted)); }
                        .assign-picker__chevron { width: 16px; height: 16px; color: var(--text-secondary, var(--muted)); flex: 0 0 16px; }
                        .assign-picker__panel {
                            position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 30;
                            background: var(--surface); border: 1px solid var(--border, var(--dept-card-border)); border-radius: 12px;
                            box-shadow: 0 12px 28px rgba(15,23,42,.14); overflow: hidden;
                        }
                        .assign-picker__panel[hidden] { display: none; }
                        .assign-picker__search-wrap { position: relative; padding: 10px; border-bottom: 1px solid var(--border, var(--dept-card-border)); background: var(--surface-soft, var(--surface)); }
                        .assign-picker__search-icon { position: absolute; right: 22px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-secondary, var(--muted)); pointer-events: none; display: block; }
                        .assign-picker__search {
                            width: 100%; height: 46px; box-sizing: border-box; padding: 0 42px 0 12px; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px;
                            background: var(--surface); color: var(--text-primary, var(--text)); font: inherit; font-size: 14px; line-height: 46px;
                            outline: none; box-shadow: none; -webkit-appearance: none; appearance: none;
                            transition: border-color .15s ease, box-shadow .15s ease;
                        }
                        .assign-picker__search::placeholder { color: var(--text-secondary, var(--muted)); opacity: 1; }
                        .assign-picker__search::-webkit-search-decoration,
                        .assign-picker__search::-webkit-search-cancel-button,
                        .assign-picker__search::-webkit-search-results-button,
                        .assign-picker__search::-webkit-search-results-decoration { -webkit-appearance: none; appearance: none; }
                        .assign-picker__search:hover { border-color: var(--border-strong, var(--navy-300)); }
                        .assign-picker__search:focus, .assign-picker__search:focus-visible {
                            outline: none; border-color: var(--navy-400, var(--primary));
                            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.12);
                        }
                        .assign-picker__list { list-style: none; margin: 0; padding: 6px; max-height: 320px; overflow: auto; }
                        .assign-picker__empty { margin: 0; padding: 14px; text-align: center; color: var(--text-secondary, var(--muted)); font-size: 13px; }
                        .assign-option {
                            width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid transparent; border-radius: 10px;
                            background: transparent; color: inherit; text-align: left; cursor: pointer; font: inherit;
                        }
                        .assign-option:hover { background: var(--surface-soft, rgba(148,163,184,.08)); }
                        .assign-option.is-selected { background: var(--navy-50, rgba(30,58,95,.06)); border-color: var(--navy-200, var(--dept-active-border)); }
                        .assign-option.is-recommended { border-color: rgba(217,119,6,.25); }
                        .assign-option__avatar {
                            width: 34px; height: 34px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
                            background: var(--navy-100, rgba(30,58,95,.12)); color: var(--navy-800, var(--navy-700)); font-size: 12px; font-weight: 700; flex: 0 0 34px;
                        }
                        .assign-option__avatar--none { background: var(--surface-soft, rgba(148,163,184,.15)); color: var(--text-secondary, var(--muted)); }
                        .assign-option__body { min-width: 0; flex: 1; display: grid; gap: 4px; }
                        .assign-option__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
                        .assign-option__name { font-size: 14px; font-weight: 600; color: var(--text-primary, var(--text)); }
                        .assign-option__meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
                        .assign-option__check { width: 18px; height: 18px; color: var(--navy-700, var(--primary)); opacity: 0; flex: 0 0 18px; }
                        .assign-option.is-selected .assign-option__check { opacity: 1; }
                        .assign-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; line-height: 1.4; }
                        .assign-badge--role-da { background: var(--navy-100, rgba(30,58,95,.12)); color: var(--navy-800, var(--navy-700)); }
                        .assign-badge--role-staff { background: var(--surface-soft, rgba(148,163,184,.16)); color: var(--text-secondary, var(--muted)); }
                        .assign-badge--load { background: rgba(14,165,233,.12); color: var(--dept-icon-sky-fg, var(--blue-600)); }
                        .assign-badge--ok { background: var(--dept-icon-green-bg, rgba(22,163,74,.12)); color: var(--dept-icon-green-fg, var(--success)); }
                        .assign-badge--overdue { background: rgba(217,119,6,.14); color: var(--dept-icon-amber-fg, var(--gold-700)); }
                        .assign-badge--recommended { background: rgba(217,119,6,.16); color: var(--dept-icon-amber-fg, var(--gold-700)); }
                        .assign-reco-banner {
                            display: flex; align-items: center; gap: 8px; margin: 0 0 10px; padding: 9px 10px;
                            border: 1px solid rgba(217,119,6,.25); border-radius: 10px;
                            background: rgba(217,119,6,.08); color: var(--text-primary, var(--text)); font-size: 13px;
                        }
                    </style>

                    <script>
                    (function () {
                        var root = document.querySelector('[data-assign-picker-root]');
                        if (!root) return;
                        var picker = root.querySelector('[data-assign-picker]');
                        var trigger = root.querySelector('[data-assign-trigger]');
                        var triggerText = root.querySelector('[data-assign-trigger-text]');
                        var panel = root.querySelector('[data-assign-panel]');
                        var search = root.querySelector('[data-assign-search]');
                        var list = root.querySelector('[data-assign-list]');
                        var empty = root.querySelector('[data-assign-empty]');
                        var input = root.querySelector('#assigned-staff-value');
                        if (!picker || !trigger || !panel || !list || !input) return;

                        function open() {
                            panel.hidden = false;
                            trigger.setAttribute('aria-expanded', 'true');
                            if (search) { search.value = ''; filter(''); search.focus(); }
                        }
                        function close() {
                            panel.hidden = true;
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                        function toggle() { panel.hidden ? open() : close(); }
                        function filter(q) {
                            q = (q || '').toLowerCase().trim();
                            var visible = 0;
                            list.querySelectorAll('[data-search]').forEach(function (btn) {
                                var match = !q || (btn.getAttribute('data-search') || '').indexOf(q) !== -1;
                                btn.closest('li').style.display = match ? '' : 'none';
                                if (match) visible++;
                            });
                            if (empty) empty.hidden = visible !== 0;
                        }
                        function select(btn) {
                            var value = btn.getAttribute('data-value') || '';
                            var name = btn.getAttribute('data-name') || '';
                            input.value = value;
                            list.querySelectorAll('.assign-option').forEach(function (opt) {
                                var sel = opt === btn;
                                opt.classList.toggle('is-selected', sel);
                                opt.setAttribute('aria-selected', sel ? 'true' : 'false');
                            });
                            triggerText.textContent = name || @json(__('staff.assignment.choose'));
                            close();
                            trigger.focus();
                        }

                        trigger.addEventListener('click', toggle);
                        if (search) search.addEventListener('input', function () { filter(search.value); });
                        list.addEventListener('click', function (e) {
                            var btn = e.target.closest('.assign-option');
                            if (btn) select(btn);
                        });
                        document.addEventListener('click', function (e) {
                            if (!panel.hidden && !picker.contains(e.target)) close();
                        });
                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape' && !panel.hidden) { close(); trigger.focus(); }
                        });
                    })();
                    </script>
                @endif

                {{-- 2. Public presentation — moderation, not publication. The
                     resident's visibility choice is theirs alone; the Mayor's
                     office withholds or redacts the public copy. Every other
                     department settles and verifies the work and does not
                     moderate public presentation. --}}
                @if($user->canVerifyComplaints() && \App\Services\ComplaintPrivacyService::canApprove($user))
                    <x-moderation-panel
                        class="moderation-panel--light staff-detail-card staff-detail-card--moderation"
                        :complaint="$complaint"
                        :action="route('staff.complaints.moderate', $complaint)"
                        id-prefix="staff"
                    />
                @endif

                {{-- 2b. Reports that name somebody: the name stays in the
                     office and with the Mayor's office, and publication is the
                     Mayor's office to decide. Resolving a verified report sends
                     it there automatically; the panel is status only. --}}
                @if($complaint->isPrivacyGated())
                    <x-publication-panel
                        class="moderation-panel--light staff-detail-card staff-detail-card--publication"
                        :complaint="$complaint"
                        :can-approve="\App\Services\ComplaintPrivacyService::canApprove($user)"
                    />
                @endif

                {{-- 2c. The work is paused — waiting on funds, materials, a
                     budget decision, another body. The reason is recorded, the
                     resident is told, and the deadline pauses with it. The
                     panel itself decides what a finished report may still do:
                     a hold already in place can be taken off, it can never
                     be taken again. --}}
                @if($complaint->isVerified())
                    <x-hold-panel
                        class="staff-detail-card staff-detail-card--hold"
                        :complaint="$complaint"
                        :can-manage="$user->role === 'admin' || $complaint->department_id === $user->department_id"
                    />
                @endif

                {{-- 3. The work that is left is the resident's to close.
                     A resolved report is waiting for the resident, not for
                     another office status — closing is their decision, or the
                     system's after 7 days. The form is not offered until
                     there is one. --}}
                @if($complaint->isVerified() && $complaint->statusEnum === \App\Enums\ComplaintStatus::Resolved)
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--closure" style="border-top: 3px solid var(--status-resolved-dot);">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon" style="background: color-mix(in srgb, var(--status-resolved-dot) 12%, transparent); color: var(--status-resolved-dot);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/><path d="M4 12l5 5L16 9"/></svg>
                            </div>
                            <h2 class="tr-h2">{{ __('staff.closure.waiting_title') }}</h2>
                        </div>
                        <p style="margin: 0; font-size: 13.5px; line-height: 1.6; color: var(--navy-900);">{{ __('staff.closure.waiting_body') }}</p>
                    </div>
                @endif
            </div>

