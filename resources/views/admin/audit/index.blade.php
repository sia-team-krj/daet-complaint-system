@extends('admin.layouts.app')

@section('title', 'Audit Trail - Admin')
@section('admin-content')
<div class="admin-page admin-page--audit">
    <header class="admin-page-header admin-page-header--audit">
        <div>
            <h1 class="admin-page-title">Audit trail</h1>
            <p class="admin-page-description admin-page-description--audit">One immutable record of staff and administrator actions across every department, complaint, invitation, and account change.</p>
        </div>
    </header>

    @php
        $auditActiveFilters = collect([
            'department_id' => request('department_id'),
            'actor_id' => request('actor_id'),
            'action' => request('action'),
            'search' => request('search'),
        ])->filter(fn ($v) => filled($v));
    @endphp
    <section class="admin-filter-panel admin-filter-panel--audit" aria-label="Audit trail filters">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="admin-filter-grid admin-filter-grid--audit admin-audit-filters" data-filter-form>
            <details class="admin-audit-filters-collapsible" data-audit-filters-mobile @if($auditActiveFilters->except(['search'])->isNotEmpty()) open @endif>
                <summary class="admin-audit-filters-toggle">
                    <span class="admin-audit-filters-toggle__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18"/><path d="M6 12h12"/><path d="M10 19h4"/></svg>
                    </span>
                    <span class="admin-audit-filters-toggle__label">Filters</span>
                    @if($auditActiveFilters->except(['search'])->isNotEmpty())
                        <span class="admin-audit-filters-badge">{{ $auditActiveFilters->except(['search'])->count() }}</span>
                    @endif
                    <span class="admin-audit-filters-toggle__chevron" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="admin-audit-filters-collapsible__body">
                    <label class="admin-field admin-audit-filter-dept">
                        <span>Department</span>
                        <select name="department_id" class="admin-input" data-audit-department-filter>
                            <option value="">All departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field admin-audit-filter-actor">
                        <span>Actor</span>
                        <select name="actor_id" class="admin-input" data-audit-actor-filter>
                            <option value="" data-placeholder="all">All staff and admins</option>
                            @php
                                $admins = $actors->filter(fn($a) => $a->role === 'admin');
                                $staff = $actors->filter(fn($a) => $a->role === 'staff');
                                $citizens = $actors->filter(fn($a) => $a->role === 'citizen');
                            @endphp
                            @if($admins->isNotEmpty())
                                <optgroup label="Administrators">
                                    @foreach($admins as $actor)
                                        <option value="{{ $actor->id }}" data-department-id="{{ $actor->department_id ?? '' }}" data-role="{{ $actor->role }}" @selected((string) request('actor_id') === (string) $actor->id)>{{ $actor->full_name }} ({{ ucfirst($actor->role) }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($staff->isNotEmpty())
                                <optgroup label="Staff">
                                    @foreach($staff as $actor)
                                        <option value="{{ $actor->id }}" data-department-id="{{ $actor->department_id ?? '' }}" data-role="{{ $actor->role }}" @selected((string) request('actor_id') === (string) $actor->id)>{{ $actor->full_name }} ({{ ucfirst($actor->role) }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($citizens->isNotEmpty())
                                <optgroup label="Citizens">
                                    @foreach($citizens as $actor)
                                        <option value="{{ $actor->id }}" data-department-id="{{ $actor->department_id ?? '' }}" data-role="{{ $actor->role }}" @selected((string) request('actor_id') === (string) $actor->id)>{{ $actor->full_name }} ({{ ucfirst($actor->role) }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </label>

                    <label class="admin-field admin-audit-filter-action">
                        <span>Action</span>
                        <select name="action" class="admin-input">
                            <option value="">All actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace(['.', '_'], ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="admin-filter-actions admin-audit-filter-actions">
                        <button type="submit" class="admin-button admin-button--primary">Filter trail</button>
                        @if($auditActiveFilters->isNotEmpty())
                            <a href="{{ route('admin.audit.index') }}" class="admin-button admin-button--secondary">Clear</a>
                        @endif
                    </div>
                </div>
            </details>

            <label class="admin-field admin-field--wide admin-audit-filter-search">
                <span>Search</span>
                <input type="search" name="search" class="admin-input" value="{{ request('search') }}" placeholder="Search ticket or description">
            </label>
        </form>
    </section>

    @php
        $auditTz = config('app.timezone');
        $auditActionLabels = [
            'auth.email_verified' => 'Email verified',
            'auth.login' => 'Signed in',
            'auth.logout' => 'Signed out',
            'auth.password_reset' => 'Password reset',
            'complaint.assignment_updated' => 'Assignment updated',
            'complaint.filed' => 'Complaint filed',
            'complaint.information_provided' => 'Information provided',
            'complaint.information_requested' => 'Information requested',
            'complaint.internal_note_added' => 'Internal note added',
            'complaint.moderated' => 'Moderated',
            'complaint.moderation_flagged' => 'Flagged for review',
            'complaint.note_added' => 'Note added',
            'complaint.reassigned' => 'Reassigned',
            'complaint.reviewed' => 'Review updated',
            'complaint.status_updated' => 'Status changed',
            'complaint.verification_delegated' => 'Verification delegated',
            'complaint.visibility_changed' => 'Visibility changed',
            'department.archived' => 'Department archived',
            'department.created' => 'Department created',
            'department.restored' => 'Department restored',
            'department.updated' => 'Department updated',
            'invitation.batch_issued' => 'Invitations issued',
            'invitation.generated' => 'Invitation generated',
            'invitation.redeemed' => 'Invitation redeemed',
            'invitation.revoked' => 'Invitation revoked',
            'profile.email_change_requested' => 'Email change requested',
            'profile.password_changed' => 'Password changed',
            'profile.updated' => 'Profile updated',
            'staff.activated' => 'Staff activated',
            'staff.created' => 'Staff account created',
            'staff.deactivated' => 'Staff deactivated',
            'staff.department_updated' => 'Staff moved',
            'staff.verification_granted' => 'Verification granted',
            'staff.verification_revoked' => 'Verification revoked',
            'user.activated' => 'User activated',
            'user.deactivated' => 'User deactivated',
            'user.department_updated' => 'User moved',
            'user.profile_updated' => 'User updated',
            'user.role_updated' => 'Role changed',
        ];
        $auditPrevDay = null;
    @endphp

    <section class="admin-table-wrap admin-audit-timeline-wrap" aria-label="Activity log entries">
        <table class="admin-table admin-table--audit admin-table--audit-mobile">
            <thead>
                <tr><th>Subject</th><th>Action</th><th>Actor</th><th>Department</th><th>Details</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $subject = $log->subject;
                        $actionKey = (string) $log->action;
                        $actionHuman = str($actionKey)->replace(['.', '_'], ' ')->title()->toString();
                        $actionLabel = $auditActionLabels[$actionKey] ?? $actionHuman;
                        $actionCat = 'neutral';
                        $ak = $actionKey;
                        if (str_contains($ak, 'moderation') || str_contains($ak, 'flagged')) $actionCat = 'warning';
                        elseif (str_starts_with($ak, 'auth.')) $actionCat = 'auth';
                        elseif (str_contains($ak, 'resolved')) $actionCat = 'resolved';
                        elseif (str_contains($ak, 'delete') || str_contains($ak, 'remove') || str_contains($ak, 'revoke') || str_contains($ak, 'deactivat') || str_contains($ak, 'archived')) $actionCat = 'danger';
                        elseif (str_contains($ak, 'create') || str_contains($ak, 'invite') || str_contains($ak, 'generated') || str_contains($ak, 'redeemed') || str_contains($ak, 'filed') || str_contains($ak, 'activat') || str_contains($ak, 'restored')) $actionCat = 'success';
                        elseif (str_contains($ak, 'update') || str_contains($ak, 'assign') || str_contains($ak, 'status') || str_contains($ak, 'review') || str_contains($ak, 'moderated') || str_contains($ak, 'changed') || str_contains($ak, 'requested') || str_contains($ak, 'provided') || str_contains($ak, 'delegated') || str_contains($ak, 'moved') || str_contains($ak, 'note')) $actionCat = 'info';
                        $actorName = $log->actor?->full_name ?? $log->actor_name ?? 'System';
                        $actorRole = ucfirst((string) ($log->actor?->role ?? $log->actor_role ?? 'System'));
                        $deptName = $log->department?->name ?? 'System-wide';
                        $logAt = $log->created_at?->copy()->setTimezone($auditTz);
                        $dayKey = $logAt?->toDateString();
                        $today = now($auditTz)->toDateString();
                        $yesterday = now($auditTz)->subDay()->toDateString();
                        $dayLabel = $dayKey === $today ? 'Today' : ($dayKey === $yesterday ? 'Yesterday' : ($logAt?->format('M j, Y') ?? ''));
                        $showDay = $loop->first || $dayKey !== $auditPrevDay;
                        $auditPrevDay = $dayKey;
                        $isComplaintSubject = $subject instanceof \App\Models\Complaint;
                        $subjectIsActorUser = $subject instanceof \App\Models\User && $log->actor_id && (int) $subject->getKey() === (int) $log->actor_id;
                        $showSubjectChip = $isComplaintSubject || ($subject && ! $subjectIsActorUser);
                        $subjectMutedGeneric = $subject && ! $isComplaintSubject && $subjectIsActorUser;
                        $detailsText = (string) $log->description;
                        $actorInDetails = $actorName !== '' && $actorName !== 'System' && str_contains(mb_strtolower($detailsText), mb_strtolower($actorName));
                        $metaParts = $actorInDetails ? [$actorRole] : [$actorName, $actorRole];
                        if ($deptName !== 'System-wide') $metaParts[] = $deptName;
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
                        <td class="admin-audit-cell--subject admin-audit-desktop-only" data-label="Subject">
                            @if($isComplaintSubject)
                                <a href="{{ route('admin.complaints.show', $subject) }}" class="admin-ticket-id">{{ $subject->ticket_id }}</a>
                            @elseif($subject)
                                <span class="admin-ticket-id">{{ class_basename($subject) }} #{{ $subject->getKey() }}</span>
                            @else
                                <span class="admin-muted-cell">System record</span>
                            @endif
                        </td>
                        <td class="admin-audit-cell--action admin-audit-desktop-only" data-label="Action">
                            <span class="admin-status admin-status--{{ str($log->action)->before('.')->slug() }}">{{ $actionHuman }}</span>
                        </td>
                        <td class="admin-audit-cell--actor admin-audit-desktop-only" data-label="Actor">
                            <span class="admin-audit-actor">{{ $actorName }}</span>
                            <span class="admin-audit-role">{{ $actorRole }}</span>
                        </td>
                        <td class="admin-audit-cell--department admin-audit-desktop-only" data-label="Department">{{ $deptName }}</td>
                        <td class="admin-audit-cell--details admin-audit-desktop-only" data-label="Details">
                            <div class="admin-audit-comment">
                                {{ $log->description }}
                                @if($log->metadata)
                                    <details class="admin-audit-metadata">
                                        <summary>View details</summary>
                                        <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </td>
                        <td class="admin-audit-cell--date admin-audit-desktop-only" data-label="Date">
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
                                        <summary>View details</summary>
                                        <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="admin-audit-empty">
                        <td colspan="6" class="admin-empty-cell admin-audit-empty__cell">
                            <p class="admin-audit-empty__title">No audit entries match</p>
                            <p class="admin-audit-empty__hint">Try clearing a filter.</p>
                            @if($auditActiveFilters->isNotEmpty())
                                <a href="{{ route('admin.audit.index') }}" class="admin-button admin-button--secondary admin-audit-empty__clear">Clear filters</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($logs->hasPages())
        <div class="admin-pagination admin-pagination--queue admin-pagination--audit">
            <p class="admin-pagination__info-mobile">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</p>
            {{ $logs->links() }}
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.admin-audit-filter-search input[name="search"]');
    const filterForm = document.querySelector('.admin-audit-filters');
    if (searchInput && filterForm) {
        const saved = sessionStorage.getItem('auditSearchFocus');
        if (saved === '1') {
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
            sessionStorage.removeItem('auditSearchFocus');
        }
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                sessionStorage.setItem('auditSearchFocus', '1');
                filterForm.submit();
            }, 500);
        });
    }

    // Desktop: force <details> open (closed details hides content even with display:contents)
    const detailsEls = document.querySelectorAll('details.admin-audit-filters-collapsible[data-audit-filters-mobile]');
    const mq = window.matchMedia('(min-width: 641px)');
    function syncDetails() {
        detailsEls.forEach(function(d) {
            if (mq.matches) { d.open = true; d.dataset.wasOpenMobile = d.dataset.wasOpenMobile || (d.hasAttribute('open') ? '1' : '0'); }
            else { /* restore server-rendered default: keep current state on resize back */ }
        });
    }
    if (mq.addEventListener) mq.addEventListener('change', syncDetails); else mq.addListener(syncDetails);
    syncDetails();

    // Dependent filter: Department -> Actor (hidden + disabled for Safari; no auto-submit)
    const deptSelect = document.querySelector('[data-audit-department-filter]');
    const actorSelect = document.querySelector('[data-audit-actor-filter]');
    if (deptSelect && actorSelect) {
        function filterActors() {
            const deptId = deptSelect.value;
            const deptName = deptId ? (deptSelect.selectedOptions[0]?.textContent || 'Department') : '';
            const placeholder = actorSelect.querySelector('option[value=""]');
            if (placeholder) placeholder.textContent = deptId ? ('All members of ' + deptName) : 'All staff and admins';
            let currentValid = false;
            actorSelect.querySelectorAll('option, optgroup').forEach(function(el) {
                if (el.tagName === 'OPTGROUP') {
                    let anyVisible = false;
                    el.querySelectorAll('option').forEach(function(opt) {
                        const od = opt.getAttribute('data-department-id') || '';
                        const show = !deptId || od === deptId;
                        opt.hidden = !show;
                        opt.disabled = !show;
                        if (show) anyVisible = true;
                        if (show && opt.selected && opt.value) currentValid = true;
                    });
                    el.hidden = !anyVisible;
                    el.disabled = !anyVisible;
                } else if (el.value !== '') {
                    const od = el.getAttribute('data-department-id') || '';
                    const show = !deptId || od === deptId;
                    el.hidden = !show;
                    el.disabled = !show;
                    if (show && el.selected) currentValid = true;
                }
            });
            if (!currentValid && actorSelect.value) actorSelect.value = '';
        }
        deptSelect.addEventListener('change', filterActors);
        filterActors();
    }
});
</script>
@endsection
