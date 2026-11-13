@props([
    'complaint',
])

{{--
    The order of the gate a report that names somebody must pass through
    before it can appear on the transparency page. Shown to the staff of the
    office handling the report; each step is live state, not a to-do list.

    Settle → verify → redact → the office asks → the Mayor's office approves.
    The name of the party is never rendered here; this card is a status view.

    Docs: docs/design-system.md §5.47.
--}}
@php
    use App\Enums\PublicationStatus;
    use App\Services\ComplaintPrivacyService;

    $isSettled  = ComplaintPrivacyService::isSettled($complaint);
    $isVerified = ComplaintPrivacyService::isVerified($complaint);
    $isRedacted = ComplaintPrivacyService::isRedacted($complaint);

    $status    = $complaint->publicationStatus();
    $askedAt   = $complaint->publication_requested_at;
    $asked     = $askedAt !== null || in_array($status, [PublicationStatus::Pending, PublicationStatus::Approved, PublicationStatus::Declined], true);
    $approved  = $status === PublicationStatus::Approved;
    $declined  = $status === PublicationStatus::Declined;

    $steps = [
        ['done' => $isSettled, 'label' => __('staff.publication.checklist_settled')],
        ['done' => $isVerified, 'label' => __('staff.publication.checklist_verified')],
        ['done' => $isRedacted, 'label' => __('staff.publication.checklist_redacted')],
        ['done' => $asked, 'label' => __('staff.publication.checklist_asked')],
        ['done' => $approved, 'blocked' => $declined, 'label' => __('staff.publication.checklist_approved')],
    ];
@endphp

<div class="moderation-panel moderation-panel--light publication-checklist" aria-labelledby="publication-checklist-title">
    <div class="moderation-panel__header">
        <h3 id="publication-checklist-title" class="moderation-panel__title">{{ __('staff.publication.checklist_title') }}</h3>
        <p class="moderation-panel__lede">{{ __('staff.publication.checklist_lede') }}</p>
    </div>

    <ol class="publication-checklist__steps" role="list">
        @foreach ($steps as $index => $step)
            <li class="publication-checklist__item{{ $step['done'] ? ' is-done' : '' }}{{ ($step['blocked'] ?? false) ? ' is-blocked' : '' }}">
                <span class="publication-checklist__icon" aria-hidden="true">
                    @if ($step['done'])
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.5 5 9l4.5-5.5"/></svg>
                    @elseif (($step['blocked'] ?? false))
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l6 6M9 3l-6 6"/></svg>
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <span class="publication-checklist__label">{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>

    @if ($approved)
        <p class="publication-checklist__verdict is-approved">{{ __('staff.publication.checklist_published_body') }}</p>
    @elseif ($declined)
        <p class="publication-checklist__verdict is-declined">{{ __('staff.publication.checklist_declined_body') }}</p>
    @elseif ($asked)
        <p class="publication-checklist__verdict">{{ __('staff.publication.checklist_waiting_body') }}</p>
    @endif
</div>
