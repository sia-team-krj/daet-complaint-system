@props([
    'complaint',
    'canApprove' => false,
    // The review page turns this on: the decision controls belong there,
    // beside the redaction editor, never on the handling office's ticket.
    'decide' => false,
    // The named party is shown to the handling office and to the Mayor's
    // office. It is never rendered on the public page.
    'showNamedParty' => true,
    // The panel's opening line. Overridden where the panel is not sitting on
    // the ticket (the decider's review page).
    'lede' => null,
])

{{--
    Publication of a report that names somebody.

    A complaint in a privacy-gated category — how you were treated at the LGU,
    the plastic policy — names a person or a business. The handling office
    resolves and verifies it; that handoff is automatic. The Mayor's office
    redacts the public copy and decides whether it is published.

    `canApprove` — the Mayor's office (or an administrator) may open the
    decision. `decide` — render the approve/decline controls here, on the
    review page where the redaction editor lives.
    Docs: docs/design-system.md §5.40.
--}}
@php
    use App\Enums\PublicationStatus;
    use App\Services\ComplaintPrivacyService;

    $status = $complaint->publicationStatus();
    $handoffBlockers = ComplaintPrivacyService::handoffBlockers($complaint);
    $approvalBlockers = ComplaintPrivacyService::approvalBlockers($complaint);
    $namedParty = $complaint->namedParty();
    $namedPartyPosition = $complaint->namedPartyPosition();
@endphp

<div {{ $attributes->merge(['class' => 'moderation-panel moderation-panel--light']) }}>
    <div class="moderation-panel__header">
        <h3 class="moderation-panel__title">{{ __('staff.publication.title') }}</h3>
        <p class="moderation-panel__lede">{{ $lede ?? __('staff.publication.lede') }}</p>
    </div>

    @if ($showNamedParty && $namedParty)
        <div class="moderation-field">
            <label>{{ __('staff.publication.named_party') }}</label>
            <p class="moderation-panel__consent">
                <strong>{{ $namedParty }}</strong>
                @if ($namedPartyPosition)
                    — {{ $namedPartyPosition }}
                @endif
                <br>{{ __('staff.publication.named_party_hint') }}
            </p>
        </div>
    @endif

    <p class="moderation-panel__consent">
        <strong>{{ $status?->label() ?? __('staff.publication.not_requested') }}</strong>
        @if ($status === null)
            {{ __('staff.publication.not_requested_body') }}
        @elseif ($status === PublicationStatus::Pending)
            {{ __('staff.publication.pending_body') }}
        @elseif ($status === PublicationStatus::Approved)
            {{ __('staff.publication.approved_body') }}
        @else
            {{ __('staff.publication.declined_body') }}
        @endif
    </p>

    @if ($status === null && $handoffBlockers !== [])
        <ul class="moderation-panel__list">
            @if (! ComplaintPrivacyService::isSettled($complaint))
                <li>{{ __('staff.publication.not_ready_settle') }}</li>
            @endif
            @if (! ComplaintPrivacyService::isVerified($complaint))
                <li>{{ __('staff.publication.not_ready_verify') }}</li>
            @endif
        </ul>
        <p class="moderation-panel__hint">{{ __('staff.publication.handoff_hint') }}</p>
    @elseif ($status === null)
        <p class="moderation-panel__hint">{{ __('staff.publication.ready_body') }}</p>
    @endif

    @if ($complaint->publication_note)
        <div class="moderation-field">
            <label>{{ __('staff.publication.note') }}</label>
            <p class="moderation-panel__consent">{{ $complaint->publication_note }}</p>
        </div>
    @endif

    @if ($status === PublicationStatus::Pending && $decide)
        <div class="moderation-panel__actions">
            <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.approve', $complaint) }}">
                @csrf
                <button type="submit" class="admin-button admin-button--primary" @disabled($approvalBlockers !== [])>{{ __('staff.publication.approve') }}</button>
            </form>
            @if ($approvalBlockers !== [])
                <p class="moderation-panel__hint">{{ __('staff.publication.approve_blocked') }}</p>
            @endif
            <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.decline', $complaint) }}">
                @csrf
                <div class="moderation-field">
                    <label for="publication-note-decline">{{ __('staff.publication.decline_note') }}</label>
                    <textarea id="publication-note-decline" name="publication_note" rows="3" maxlength="1000" required
                              placeholder="{{ __('staff.publication.decline_note_placeholder') }}">{{ old('publication_note') }}</textarea>
                </div>
                <button type="submit" class="admin-button admin-button--secondary">{{ __('staff.publication.decline') }}</button>
            </form>
        </div>
    @elseif ($status === PublicationStatus::Pending && $canApprove)
        <div class="moderation-panel__actions">
            <a class="admin-button admin-button--primary" href="{{ route('staff.publication-requests.show', $complaint) }}">
                {{ __('staff.publication.open_review') }}
            </a>
        </div>
    @endif
</div>
