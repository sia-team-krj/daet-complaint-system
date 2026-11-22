<div class="staff-ticket-page" data-ticket-workspace="{{ $workspaceMode ?? 'verifier' }}" data-available-workspaces="{{ implode(',', $availableWorkspaceModes ?? ['verifier']) }}">
    @php
        $statusEnum = $complaint->statusEnum;
        $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '_');
        $ageNow = now();
        $ageSeconds = max(0, (int) $complaint->created_at->diffInSeconds($ageNow));
        $ageDays = (int) floor($ageSeconds / 86400);
        $ageMinutes = (int) floor($ageSeconds / 60);
        $ageHours = (int) floor($ageSeconds / 3600);
        $ageLabel = $ageDays >= 1
            ? $ageDays.' day'.($ageDays === 1 ? '' : 's')
            : ($ageSeconds < 3600
                ? max(1, $ageMinutes).' minute'.($ageMinutes === 1 ? '' : 's')
                : $ageHours.' hour'.($ageHours === 1 ? '' : 's'));
        $workspaceMode = $workspaceMode ?? 'verifier';
        $availableWorkspaceModes = $availableWorkspaceModes ?? [$workspaceMode];
    @endphp

    <div class="staff-ticket-shell">

            @if(count($availableWorkspaceModes) > 1)
                <div class="staff-workspace-switch" role="group" aria-label="{{ __('staff.workspace.switch_label', ['default' => 'Workspace view']) }}">
                    <span class="staff-workspace-switch__label">{{ __('staff.workspace.label', ['default' => 'View']) }}</span>
                    @foreach($availableWorkspaceModes as $mode)
                        @php
                            $isActive = $workspaceMode === $mode;
                            $label = $mode === 'verifier'
                                ? __('staff.workspace.verifier', ['default' => 'Verifier workspace'])
                                : __('staff.workspace.field', ['default' => 'Field work']);
                            $url = request()->fullUrlWithQuery(['workspace' => $mode]);
                        @endphp
                        <a href="{{ $url }}" class="staff-workspace-switch__btn {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="true" @endif data-workspace-mode="{{ $mode }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endif

        @if(count($availableWorkspaceModes) > 1)
            <script>
                (function () {
                    var params = new URLSearchParams(window.location.search);
                    if (params.has('workspace')) return;
                    if (/(^|;\s*)daet_ticket_workspace=/.test(document.cookie)) return;
                    if (window.innerWidth > 768) return;
                    var root = document.querySelector('[data-ticket-workspace]');
                    if (!root) return;
                    var modes = (root.getAttribute('data-available-workspaces') || '').split(',');
                    if (modes.indexOf('field') === -1) return;
                    if (root.getAttribute('data-ticket-workspace') === 'field') return;
                    params.set('workspace', 'field');
                    window.location.replace(window.location.pathname + '?' + params.toString() + window.location.hash);
                })();
            </script>
        @endif

        <header class="staff-ticket-hero">
            <div class="staff-ticket-hero__top">
                <div class="staff-ticket-hero__badges">
                    <span class="ticket-id">{{ $complaint->ticket_id }}</span>
                    <span class="dept-badge" data-dept="{{ $complaint->department?->code ?? '' }}">{{ $complaint->department?->code ?? __('nav.departments') }}</span>
                    <span class="status-pill {{ $statusKey }}" data-ticket-status-pill>
                        <span class="status-pill__dot" aria-hidden="true"></span>
                        <span data-ticket-status-label>{{ $statusEnum->label() }}</span>
                    </span>
                    @if($complaint->confirmedPriorityEnum)
                        @php
                            $priorityValue = $complaint->confirmedPriorityEnum->value;
                            $priorityTone = match($priorityValue) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                default => 'neutral'
                            };
                        @endphp
                        <span class="status-pill status-pill--{{ $priorityTone }}">{{ $complaint->confirmedPriorityEnum->label() }}</span>
                    @endif
                    @if($slaSummary['state'] !== \App\Enums\ComplaintSlaState::NotApplicable)
                        <span class="sla-chip {{ $slaSummary['state']->badgeClass() }}">
                            <span>{{ $slaSummary['action'] === 'complete_work' ? __('staff.sla.action_finish') : __('staff.sla.action_start') }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ $slaSummary['days'] < 0 ? trans_choice('staff.sla.days_overdue', abs($slaSummary['days'])) : trans_choice('staff.sla.days_left', $slaSummary['days']) }}</span>
                        </span>
                    @endif
                </div>
            </div>
            <h1 class="staff-ticket-hero__title">{{ $complaint->title }}</h1>
            <div class="staff-ticket-hero__meta">
                <span><span class="staff-ticket-hero__meta-label">Filed</span> {{ $complaint->created_at->format('M j, Y') }}</span>
                <span class="staff-ticket-hero__sep" aria-hidden="true">·</span>
                <span><span class="staff-ticket-hero__meta-label">Work</span> {{ $workMode->label() }}</span>
                <span class="staff-ticket-hero__sep" aria-hidden="true">·</span>
                <span><span class="staff-ticket-hero__meta-label">Age</span> <strong class="staff-ticket-hero__age">{{ $ageLabel }} open</strong></span>
            </div>
        </header>

        @if(session('status'))
            <div class="staff-ticket-alert staff-ticket-alert--success" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="staff-ticket-alert staff-ticket-alert--error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($complaint->isModerationFlagged() || $complaint->hidden_from_public)
            <section class="staff-ticket-alert staff-ticket-alert--warning" aria-labelledby="complaint-flag-notice-title">
                <span class="staff-ticket-alert__icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <div class="staff-ticket-alert__body">
                    <h2 id="complaint-flag-notice-title" class="staff-ticket-alert__title">{{ __('staff.flag_notice.title') }}</h2>
                    <p>
                        @if($complaint->isModerationFlagged())
                            <strong>{{ $complaint->moderationLabel() }}</strong>
                            @if(! is_null($complaint->spam_score)) · {{ __('staff.flag_notice.score', ['score' => $complaint->spam_score]) }} @endif
                            @if($complaint->duplicate_of_id && $complaint->duplicateOf) · {{ __('staff.flag_notice.similar', ['percent' => (int) round(($complaint->similarity_score ?? 0) * 100)]) }} @endif
                        @endif
                        @if($complaint->hidden_from_public)
                            @if($complaint->isModerationFlagged())<br>@endif
                            <strong>{{ __('staff.flag_notice.hidden_public') }}</strong>
                        @endif
                    </p>
                    @if($complaint->isModerationFlagged() && is_array($complaint->spam_reasons) && count($complaint->spam_reasons))
                        <ul class="staff-ticket-alert__list">
                            @foreach($complaint->spam_reasons as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if($complaint->duplicate_of_id && $complaint->duplicateOf)
                        <a href="{{ route('staff.complaints.show', $complaint->duplicateOf) }}" class="staff-ticket-alert__link">{{ __('staff.flag_notice.compare', ['ticket' => $complaint->duplicateOf->ticket_id]) }}</a>
                    @endif
                </div>
            </section>
        @endif

        {{-- ════════════ LIVE TICKET ════════════
             Another hand may move this case while it is open. The panel
             polls a fingerprint and, when it differs, says who changed what.
             It never replaces the form: a staff member mid-sentence keeps
             every word they typed. --}}
        <div data-live-ticket
             data-endpoint="{{ route('staff.complaints.state', $complaint) }}"
             data-fingerprint="{{ $ticketFingerprint }}"
             data-interval="20000">

            <div class="ticket-live-note" data-live-ticket-notice role="status" aria-live="polite" hidden>
                <span class="ticket-live-note__dot" aria-hidden="true"></span>
                <p class="ticket-live-note__text" data-live-ticket-message></p>
                <div class="ticket-live-note__actions">
                    <a class="ticket-live-note__reload" data-live-ticket-refresh href="{{ route('staff.complaints.show', $complaint) }}">
                        {{ __('staff.live.refresh') }}
                    </a>
                    <button type="button" class="ticket-live-note__dismiss" data-live-ticket-dismiss>
                        {{ __('staff.live.dismiss') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Optimized Responsive Grid (Adapts intelligently across breakpoints) --}}
        <div class="staff-detail-grid">
