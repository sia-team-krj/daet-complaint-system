{{--
    The audit trail table and its pager.

    Rendered on its own so the pager can be swapped in without a page load:
    the endpoint answers with this same fragment, so the table markup exists in
    exactly one file whichever way the page is drawn.
--}}
    @php
        $auditTz = config('app.timezone');
        $auditPrevDay = null;
    @endphp

    <section class="admin-table-wrap admin-audit-timeline-wrap" aria-label="{{ __('audit.activity_log_entries') }}">
        <table class="admin-table admin-table--audit admin-table--audit-mobile">
            <thead>
                <tr><th>{{ __('audit.col_subject') }}</th><th>{{ __('audit.col_action') }}</th><th>{{ __('audit.col_actor') }}</th><th>{{ __('audit.col_department') }}</th><th>{{ __('audit.col_details') }}</th><th>{{ __('audit.col_date') }}</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $subject = $log->subject;
                        $actionKey = (string) $log->action;
                        $actionLabel = $actionLabelFor($actionKey);
                        $actionCat = 'neutral';
                        $ak = $actionKey;
                        if (str_contains($ak, 'moderation') || str_contains($ak, 'flagged')) $actionCat = 'warning';
                        elseif (str_starts_with($ak, 'auth.')) $actionCat = 'auth';
                        elseif (str_contains($ak, 'resolved')) $actionCat = 'resolved';
                        elseif (str_contains($ak, 'delete') || str_contains($ak, 'remove') || str_contains($ak, 'revoke') || str_contains($ak, 'deactivat') || str_contains($ak, 'archived')) $actionCat = 'danger';
                        elseif (str_contains($ak, 'create') || str_contains($ak, 'invite') || str_contains($ak, 'generated') || str_contains($ak, 'redeemed') || str_contains($ak, 'filed') || str_contains($ak, 'activat') || str_contains($ak, 'restored')) $actionCat = 'success';
                        elseif (str_contains($ak, 'update') || str_contains($ak, 'assign') || str_contains($ak, 'status') || str_contains($ak, 'review') || str_contains($ak, 'moderated') || str_contains($ak, 'changed') || str_contains($ak, 'requested') || str_contains($ak, 'provided') || str_contains($ak, 'delegated') || str_contains($ak, 'moved') || str_contains($ak, 'note')) $actionCat = 'info';
                        $systemName = __('audit.system');
                        $department = $log->department;
                        $actorName = $log->actor?->full_name ?? $log->actor_name ?? $systemName;
                        $actorRole = ucfirst((string) ($log->actor?->role ?? $log->actor_role ?? $systemName));
                        $deptName = $department?->name ?? __('audit.system_wide');
                        $logAt = $log->created_at?->copy()->setTimezone($auditTz);
                        $dayKey = $logAt?->toDateString();
                        $today = now($auditTz)->toDateString();
                        $yesterday = now($auditTz)->subDay()->toDateString();
                        $dayLabel = $dayKey === $today ? __('audit.today') : ($dayKey === $yesterday ? __('audit.yesterday') : ($logAt?->format('M j, Y') ?? ''));
                        $showDay = $loop->first || $dayKey !== $auditPrevDay;
                        $auditPrevDay = $dayKey;
                        $isComplaintSubject = $subject instanceof \App\Models\Complaint;
                        $subjectIsActorUser = $subject instanceof \App\Models\User && $log->actor_id && (int) $subject->getKey() === (int) $log->actor_id;
                        $showSubjectChip = $isComplaintSubject || ($subject && ! $subjectIsActorUser);
                        $subjectMutedGeneric = $subject && ! $isComplaintSubject && $subjectIsActorUser;
                        $detailsText = (string) $log->description;
                        $actorInDetails = $actorName !== '' && $actorName !== $systemName && str_contains(mb_strtolower($detailsText), mb_strtolower($actorName));
                        $metaParts = $actorInDetails ? [$actorRole] : [$actorName, $actorRole];
                        if ($department) $metaParts[] = $deptName;
                        $metaText = implode(' · ', $metaParts);
                    @endphp

                    @php
                        $isLastInAuditPage = $loop->last;
                        $nextDayKey = null;
                        if (! $isLastInAuditPage) {
                            $nextLog = $logs[$loop->index + 1] ?? null;
                            $nextDayKey = $nextLog?->created_at?->copy()->setTimezone($auditTz)?->toDateString();
                        }
                        $isLastInGroup = $isLastInAuditPage || ($nextDayKey !== $dayKey);
                    @endphp

                    @if($showDay)
                        <tr class="admin-audit-day-heading admin-audit-mobile-only" aria-hidden="true">
                            <td colspan="6"><span class="admin-audit-day-heading__label">{{ $dayLabel }}</span></td>
                        </tr>
                    @endif

                    <tr class="admin-audit-entry {{ $showDay ? 'admin-audit-entry--first-in-group' : '' }} {{ $isLastInGroup ? 'admin-audit-entry--last-in-group' : '' }}">
                        <td class="admin-audit-cell--subject admin-audit-desktop-only" data-label="{{ __('audit.col_subject') }}">
                            @if($isComplaintSubject)
                                <a href="{{ route('admin.complaints.show', $subject) }}" class="admin-ticket-id">{{ $subject->ticket_id }}</a>
                            @elseif($subject)
                                <span class="admin-ticket-id">{{ class_basename($subject) }} #{{ $subject->getKey() }}</span>
                            @else
                                <span class="admin-muted-cell">{{ __('audit.system_record') }}</span>
                            @endif
                        </td>
                        <td class="admin-audit-cell--action admin-audit-desktop-only" data-label="{{ __('audit.col_action') }}">
                            <span class="admin-status admin-status--{{ str($log->action)->before('.')->slug() }}">{{ $actionLabel }}</span>
                        </td>
                        <td class="admin-audit-cell--actor admin-audit-desktop-only" data-label="{{ __('audit.col_actor') }}">
                            <span class="admin-audit-actor">{{ $actorName }}</span>
                            <span class="admin-audit-role">{{ $actorRole }}</span>
                        </td>
                        <td class="admin-audit-cell--department admin-audit-desktop-only" data-label="{{ __('audit.col_department') }}">{{ $deptName }}</td>
                        <td class="admin-audit-cell--details admin-audit-desktop-only" data-label="{{ __('audit.col_details') }}">
                            <div class="admin-audit-comment">
                                {{ $log->description }}
                                @if($log->metadata)
                                    <details class="admin-audit-metadata">
                                        <summary>{{ __('audit.view_details') }}</summary>
                                        <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </td>
                        <td class="admin-audit-cell--date admin-audit-desktop-only" data-label="{{ __('audit.col_date') }}">
                            <span class="admin-muted-cell"><time datetime="{{ $logAt?->toIso8601String() }}">{{ $logAt?->format('M j, Y g:i A') }}</time></span>
                        </td>

                        <td class="admin-audit-cell--mobile admin-audit-mobile-only" colspan="6">
                            <div class="admin-audit-entry__card">
                                <div class="admin-audit-entry__row1">
                                    <span class="admin-audit-chip admin-audit-chip--{{ $actionCat }}">{{ $actionLabel }}</span>
                                    <span class="admin-audit-entry__row1-right">
                                        @if($isComplaintSubject)
                                            <a href="{{ route('admin.complaints.show', $subject) }}" class="admin-audit-entry__subject-chip admin-audit-entry__subject-chip--row1">{{ $subject->ticket_id }}</a>
                                        @endif
                                        <time class="admin-audit-entry__time" datetime="{{ $logAt?->toIso8601String() }}" title="{{ $logAt?->format('M j, Y g:i A') }}">{{ $logAt?->format('g:i A') }}</time>
                                    </span>
                                </div>
                                <div class="admin-audit-entry__details">{{ $log->description }}</div>
                                <div class="admin-audit-entry__meta">
                                    <span class="admin-audit-entry__meta-text">{{ $metaText }}</span>
                                    @if(! $isComplaintSubject && $showSubjectChip)
                                        <span class="admin-audit-entry__subject-muted">{{ class_basename($subject) }} #{{ $subject->getKey() }}</span>
                                    @elseif(! $isComplaintSubject && $subjectMutedGeneric)
                                        {{-- Generic subject equals actor (e.g. User #1 signed in); details already identify the actor. Kept out of mobile row 3; desktop subject cell retains it. --}}
                                    @endif
                                </div>
                                @if($log->metadata)
                                    <details class="admin-audit-metadata admin-audit-metadata--mobile">
                                        <summary>{{ __('audit.view_details') }}</summary>
                                        <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="admin-audit-empty">
                        <td colspan="6" class="admin-empty-cell admin-audit-empty__cell">
                            @if($auditActiveFilters->isNotEmpty())
                                <p class="admin-audit-empty__title">{{ __('audit.no_entries_title') }}</p>
                                <p class="admin-audit-empty__hint">{{ __('audit.no_entries_hint') }}</p>
                            @elseif($selection['active'])
                                <p class="admin-audit-empty__title">{{ __('audit.no_entries_selection_title') }}</p>
                                <p class="admin-audit-empty__hint">{{ __('audit.no_entries_selection_hint', ['name' => $selection['label']]) }}</p>
                            @else
                                <p class="admin-audit-empty__title">{{ __('audit.no_entries_yet_title') }}</p>
                            @endif
                            @if($auditActiveFilters->isNotEmpty() || $selection['active'])
                                <a href="{{ route('admin.audit.index') }}" class="admin-button admin-button--secondary admin-audit-empty__clear">
                                    {{ $auditActiveFilters->isNotEmpty() ? __('audit.clear_filters') : __('audit.show_everyone') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($logs->hasPages())
        <div class="admin-pagination admin-pagination--queue admin-pagination--audit">
            <p class="admin-pagination__info-mobile">{{ __('audit.page_of', ['current' => $logs->currentPage(), 'last' => $logs->lastPage()]) }}</p>
            {{ $logs->links('pagination::workspace', ['ariaLabel' => __('audit.pagination_label')]) }}
        </div>
    @endif
