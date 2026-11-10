{{--
    The staff queue's rows. Swapped in place when a case changes, so a staff
    member never works a case another member has already moved.

    See docs/design-system.md §5.36.
--}}
            <div class="tr-register-head">
                <h2 class="tr-h2" id="queue-list-title">{{ __('staff.queue') }} — {{ $complaints->total() }} {{ \Illuminate\Support\Str::plural('complaint', $complaints->total()) }}</h2>
                @if($complaints->hasPages())
                    <p class="tr-results">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }}</p>
                @endif
            </div>

            {{-- ════════════ QUEUE LIST (Redesigned Cards, 3 per page) ════════════ --}}
            <ul class="report-list" id="complaint-table-body">
                @forelse($complaints as $complaint)
                    @php
                        $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '_');
                        $reviewKey = \Illuminate\Support\Str::slug((string) $complaint->review_status, '_');
                        $ageNow = now();
                        $ageSeconds = max(0, (int) $complaint->created_at->diffInSeconds($ageNow));
                        $ageDays = (int) floor($ageSeconds / 86400);
                        $ageLabel = $ageDays >= 1
                            ? $ageDays.' day'.($ageDays === 1 ? '' : 's')
                            : ($ageSeconds < 3600
                                ? max(1, (int) floor($ageSeconds / 60)).' minute'.(floor($ageSeconds / 60) === 1.0 ? '' : 's')
                                : (int) floor($ageSeconds / 3600).' hour'.(floor($ageSeconds / 3600) === 1.0 ? '' : 's'));
                        $priorityValue = $complaint->confirmedPriorityEnum?->value ?? $complaint->suggested_priority ?? 'unset';
                        $priority = $complaint->confirmedPriorityEnum?->label() ?? $complaint->suggestedPriorityEnum->label();
                        $waiting = $complaint->informationRequests->first();
                        $waitingDays = $waiting ? max(0, (int) floor($waiting->created_at->diffInSeconds($ageNow) / 86400)) : null;
                        $slaSummary = $slaSummaries[$complaint->id] ?? null;
                    @endphp
                    <li class="staff-queue-item {{ $ageDays > 14 ? 'staff-queue-item--overdue' : ($ageDays > 7 ? 'staff-queue-item--aging' : '') }}"
                        data-report-row
                        data-report-id="{{ $complaint->ticket_id }}"
                        data-department="{{ $department?->name ?? '' }}"
                        data-category="{{ $complaint->category }}"
                        data-status="{{ $statusKey }}"
                        data-search="{{ \Illuminate\Support\Str::lower($complaint->ticket_id.' '.$complaint->title.' '.$complaint->description.' '.$complaint->category) }}"
                        data-iso-date="{{ $complaint->created_at?->toAtomString() ?? '' }}"
                    >
                        <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-row" wire:navigate>
                            
                            {{-- Top Section: Category Icon, Title, Ticket ID & Category --}}
                            <div class="staff-row__top">
                                <div class="staff-row__icon-wrapper">
                                    @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                </div>
                                <div class="staff-row__text">
                                    <h3 class="staff-row__title">{{ \Illuminate\Support\Str::limit($complaint->title, 90) }}</h3>
                                    <div class="staff-row__meta">
                                        <span class="ticket-id" style="font-family: var(--font-mono, monospace); font-weight: 700; color: #10294B;">{{ $complaint->ticket_id }}</span>
                                        <span aria-hidden="true"> · </span>
                                        <span>{{ str($complaint->category)->replace('_', ' ')->title() }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Middle Section: Status, Review Status & Priority Badges --}}
                            <div class="staff-row__badges">
                                <span class="status-pill {{ $statusKey }}">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->statusEnum->label() }}
                                </span>

                                @if($complaint->isModerationFlagged())
                                    <span class="status-pill rejected">
                                        <span class="status-pill__dot" aria-hidden="true"></span>
                                        {{ $complaint->moderationLabel() }}
                                    </span>
                                @endif

                                <span class="status-pill {{ $reviewKey }}">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->reviewStatusEnum->label() }}
                                </span>

                                @if($waiting)
                                    <span class="staff-row__subtext" style="color: #D97706; font-weight: 600;">
                                        Waiting {{ $waitingDays }}d{{ $waiting->isStale() ? ' · overdue' : '' }}
                                    </span>
                                @endif

                                {{-- How long the office has left on this one. A ticket
                                     nobody started must not look exactly like a
                                     ticket being worked on today. --}}
                                @if($slaSummary && $slaSummary['state'] !== \App\Enums\ComplaintSlaState::NotApplicable)
                                    <span class="sla-chip {{ $slaSummary['state']->badgeClass() }}">
                                        <span>{{ $slaSummary['action'] === 'complete_work'
                                            ? __('staff.sla.action_finish')
                                            : __('staff.sla.action_start') }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $slaSummary['days'] < 0
                                            ? trans_choice('staff.sla.days_overdue', abs($slaSummary['days']))
                                            : trans_choice('staff.sla.days_left', $slaSummary['days']) }}</span>
                                    </span>
                                @endif

                                <span class="staff-priority staff-priority--{{ \Illuminate\Support\Str::slug($priorityValue) }}">
                                    {{ $priority }}
                                </span>

                                @if($complaint->suggestedPriorityEnum && $complaint->suggestedPriorityEnum->value !== $priorityValue)
                                    <span class="staff-row__subtext">Suggested: {{ $complaint->suggestedPriorityEnum->label() }}</span>
                                @endif
                            </div>

                            {{-- Bottom / Right Section: Age & Filing Duration --}}
                            <div class="staff-row__age">
                                <span class="staff-age">{{ $ageLabel }} open</span>
                            </div>

                        </a>
                    </li>
                @empty
                    <li>
                        <div class="empty-state">
                            <span class="empty-state__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                            </span>
                            <strong>{{ __('staff.empty_title') }}</strong>
                            <p>{{ __('staff.empty_desc') }}</p>
                        </div>
                    </li>
                @endforelse
            </ul>
