{{--
    Resident-facing report row — the shared component from the design system
    (docs/design-system.md §5.14), extended with the two things only an owner
    needs: a visibility pill (their complaint is public or private) and a
    "Needs your response" call-out when a verifier is waiting on them.

    Expects: $complaint (Eloquent model with department loaded).
--}}
@php
    use Illuminate\Support\Str;
    use App\Enums\ComplaintStatus;

    $status = $complaint->statusEnum;
    $statusKey = Str::slug($status->value, '_');
    $closed = $statusKey === 'closed';
    $step = match ($statusKey) {
        'submitted' => 1,
        'under_review', 'verified', 'assigned' => 2,
        'in_progress' => 3,
        'resolved' => 4,
        'closed' => 5,
        default => null, // rejected
    };
    $isRejected = $statusKey === 'rejected' || $step === null;
    $verified = $complaint->verificationMethodEnum() !== null;
    $needsResponse = $complaint->informationRequests->firstWhere('status.value', 'open') !== null;
    $isPublic = (bool) $complaint->is_public;
    $barangayRaw = trim((string) $complaint->barangay);

    if ($barangayRaw === '') {
        $account = trim((string) $complaint->user?->barangay);
        $barangayRaw = preg_replace('/^barangay\s+/i', '', $account) ?? $account;
    }

    $barangay = $barangayRaw !== ''
        ? (preg_match('/^barangay\s+/i', $barangayRaw) ? $barangayRaw : "Barangay {$barangayRaw}")
        : null;
    $updated = $complaint->updated_at?->diffForHumans();
@endphp
<li
    data-report-row
    data-report-id="{{ $complaint->ticket_id }}"
    data-search="{{ Str::lower($complaint->ticket_id.' '.$complaint->title.' '.$complaint->department?->name.' '.$complaint->category.' '.$barangay) }}"
    data-department="{{ $complaint->department?->name ?? '' }}"
    data-category="{{ $complaint->category }}"
    data-status="{{ $statusKey }}"
    data-iso-date="{{ $complaint->created_at?->toAtomString() ?? '' }}"
    data-barangay="{{ $complaint->barangay ?? '' }}"
    data-visibility="{{ $isPublic ? 'public' : 'private' }}"
>
    <a class="report-row" href="{{ route('complaints.show', $complaint) }}" wire:navigate>
        <span class="report-row__complaint">
            @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
            <span class="report-row__text">
                <span class="report-row__title">{{ Str::limit(trim($complaint->title), 90) }}</span>
                <span class="report-row__meta">
                    <span class="ticket-id">{{ $complaint->ticket_id }}</span>
                    <span class="report-row__meta-dot" aria-hidden="true">·</span>
                    <span class="visibility-pill {{ $isPublic ? 'visibility-pill--public' : 'visibility-pill--private' }}">{{ __($isPublic ? 'my_complaints.visibility_public' : 'my_complaints.visibility_private') }}</span>
                    @if($needsResponse)
                        <span class="needs-response"><span class="needs-response__dot" aria-hidden="true"></span>{{ __('my_complaints.needs_response') }}</span>
                    @endif
                    @if($barangay)
                        <svg class="report-row__meta-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span class="report-row__brgy">{{ $barangay }}</span>
                    @endif
                </span>
            </span>
        </span>

        <span class="report-row__dept">
            @if($complaint->department?->code)
                <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
            @endif
            <span class="report-row__dept-name">{{ $complaint->department?->name ?? __('transparency.v2.department_pending') }}</span>
        </span>

        <span class="report-row__status">
            <span class="status-pill {{ $statusKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $status->label() }}</span>
            @if($verified)
                <span class="report-row__verified">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>
                    {{ __('my_complaints.verified') }}
                </span>
            @endif
            <span class="progress progress--mini" aria-hidden="true">
                @for($segment = 1; $segment <= 5; $segment++)
                    <span class="progress__seg {{ $isRejected ? '' : ($segment < $step || $closed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '')) }}"></span>
                @endfor
            </span>
        </span>

        <span class="report-row__reported">
            <time datetime="{{ $complaint->created_at?->toAtomString() }}">{{ $complaint->created_at?->translatedFormat('M j, Y') }}</time>
            @if($updated)
                <span class="report-row__updated">{{ __('my_complaints.updated_since', ['time' => $updated]) }}</span>
            @endif
        </span>

        <span
            class="report-row__progressline"
            role="img"
            aria-label="{{ $isRejected ? $status->label() : __('my_complaints.progress_aria', ['step' => $step, 'total' => 5, 'status' => $status->label()]) }}"
        >
            <span class="progress__text">
                @if($isRejected)
                    {{ $status->label() }}
                @else
                    {{ __('my_complaints.step_status', ['step' => $step, 'total' => 5, 'status' => $status->label()]) }}
                @endif
            </span>
            <span class="progress progress--full" aria-hidden="true">
                @for($segment = 1; $segment <= 5; $segment++)
                    <span class="progress__seg {{ $isRejected ? '' : ($segment < $step || $closed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '')) }}"></span>
                @endfor
            </span>
        </span>

        <svg class="report-row__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
</li>
