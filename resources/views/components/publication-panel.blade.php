@props([
    'complaint',
    'canRequest' => false,
    'canApprove' => false,
    // The named party is shown to the handling office. The Mayor's office
    // decides on the redacted copy, so its review page turns this off.
    'showNamedParty' => true,
    // The panel's opening line. Overridden where the panel is not sitting on
    // the ticket (the decider's review page).
    'lede' => null,
])

{{--
    Publication of a report that names somebody.

    A complaint in a privacy-gated category — how you were treated at the LGU,
    the plastic policy — names a person or a business. Its name is shown here
    because this panel is inside the office; it never leaves the office. The
    complaint is filed private and reaches the public register only when the
    office has settled it, the information has been verified, the sensitive
    parts have been redacted, and the Mayor's office has approved it.

    `canRequest` — the owning office asks the Mayor's office to decide.
    `canApprove` — the Mayor's office (or an administrator) decides.
    Docs: docs/design-system.md §5.40.
--}}
@php
    $status = $complaint->publicationStatus();
    // What is still missing before the report can be asked for — the office's
    // three jobs. Whether the Mayor's office has approved is the final gate,
    // not a job the office can do; the state line carries it instead.
    $blockers = \App\Services\ComplaintPrivacyService::publicationBlockers($complaint, includeApproval: false)['reasons'];
    $namedParty = $complaint->namedParty();
    $namedPartyPosition = $complaint->namedPartyPosition();
    // The note is the office's request while the decision is outstanding, and
    // the Mayor's office's reason once it has decided.
    $noteLabel = $status === \App\Enums\PublicationStatus::Pending
        ? __('staff.publication.request_note')
        : __('staff.publication.note');
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
        @elseif ($status === \App\Enums\PublicationStatus::Pending)
            {{ __('staff.publication.pending_body') }}
        @elseif ($status === \App\Enums\PublicationStatus::Approved)
            {{ __('staff.publication.approved_body') }}
        @else
            {{ __('staff.publication.declined_body') }}
        @endif
    </p>

    @if ($status !== \App\Enums\PublicationStatus::Approved)
        <ul class="moderation-panel__list">
            @foreach ($blockers as $blocker)
                <li>{{ $blocker }}</li>
            @endforeach
        </ul>
    @endif

    @if ($complaint->publication_note)
        <div class="moderation-field">
            <label>{{ $noteLabel }}</label>
            <p class="moderation-panel__consent">{{ $complaint->publication_note }}</p>
        </div>
    @endif

    @if ($canRequest && $status !== \App\Enums\PublicationStatus::Approved)
        <form data-loading-submit method="POST" action="{{ route('staff.complaints.publication-request', $complaint) }}">
            @csrf
            <div class="moderation-field">
                <label for="publication-note-request">{{ __('staff.publication.request_note') }}</label>
                <textarea id="publication-note-request" name="publication_note" rows="3" maxlength="1000"
                          placeholder="{{ __('staff.publication.request_note_placeholder') }}">{{ old('publication_note') }}</textarea>
            </div>
            <button type="submit" class="admin-button admin-button--primary" @disabled($blockers !== [])>
                {{ __('staff.publication.request') }}
            </button>
            @if ($blockers !== [])
                <p class="moderation-panel__hint">{{ __('staff.publication.request_blocked') }}</p>
            @endif
        </form>
    @endif

    @if ($canApprove && $status === \App\Enums\PublicationStatus::Pending)
        <div class="moderation-panel__actions">
            <form data-loading-submit method="POST" action="{{ route('staff.publication-requests.approve', $complaint) }}">
                @csrf
                <button type="submit" class="admin-button admin-button--primary" @disabled($blockers !== [])>{{ __('staff.publication.approve') }}</button>
            </form>
            @if ($blockers !== [])
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
    @endif
</div>
