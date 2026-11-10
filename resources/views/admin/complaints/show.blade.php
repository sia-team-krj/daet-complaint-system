@extends('admin.layouts.app')

@section('title', $complaint->ticket_id . ' - Admin')
@section('admin-content')
<div class="admin-page">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Overview</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('admin.complaints.index') }}">All complaints</a>
        <span aria-hidden="true">/</span>
        <span>{{ $complaint->ticket_id }}</span>
    </nav>

    @include('admin.partials.flash')

    @if($complaint->isModerationFlagged())
        <section class="admin-moderation-panel" aria-labelledby="moderation-title">
            <div>
                <h2 id="moderation-title">Moderation review required</h2>
                <p>{{ $complaint->moderationLabel() }} · Score {{ $complaint->spam_score }}/100 @if($complaint->duplicate_of_id) · {{ (int) round($complaint->similarity_score * 100) }}% similar @endif</p>
            </div>
            @if($complaint->spam_reasons)
                <ul>
                    @foreach($complaint->spam_reasons as $reason)<li>{{ $reason }}</li>@endforeach
                </ul>
            @endif
            @if($complaint->duplicate_of_id && $complaint->duplicateOf)
                <a href="{{ route('admin.complaints.show', $complaint->duplicateOf) }}" class="admin-text-link">Compare with {{ $complaint->duplicateOf->ticket_id }}</a>
            @endif
        </section>
    @endif

    <div class="admin-detail-layout">
        <div class="admin-detail-main">
            <section class="admin-panel" aria-labelledby="complaint-summary-title">
                <div class="admin-panel-heading">
                    <div>
                        <span class="admin-ticket-id">{{ $complaint->ticket_id }}</span>
                        <h1 id="complaint-summary-title" class="admin-panel-title">{{ $complaint->title }}</h1>
                    </div>
                    <span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                </div>
                <div class="admin-panel-body">
                    <dl class="admin-detail-list">
                        <div><dt>Category</dt><dd>{{ str($complaint->category)->replace('_', ' ')->title() }}</dd></div>
                        <div><dt>Review status</dt><dd>{{ $complaint->reviewStatusEnum->label() }}</dd></div>
                        <div><dt>Suggested priority</dt><dd>{{ $complaint->suggestedPriorityEnum->label() }}</dd></div>
                        <div><dt>Confirmed priority</dt><dd>{{ $complaint->confirmedPriorityEnum?->label() ?? 'Awaiting confirmation' }}</dd></div>
                        <div><dt>Department</dt><dd>@if($complaint->department)<span class="dept-indicator" data-dept="{{ $complaint->department->code }}"><span class="dept-dot" data-dept="{{ $complaint->department->code }}" aria-hidden="true"></span><span class="dept-indicator__name">{{ $complaint->department->name }} <span class="dept-chip" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span></span></span>@else Unassigned @endif</dd></div>
                        <div><dt>Work</dt><dd>{{ $workMode->label() }}</dd></div>
                        <div><dt>Reviewed by</dt><dd>{{ $complaint->reviewedBy?->full_name ?? 'Not reviewed yet' }}</dd></div>
                        <div><dt>Filed</dt><dd>{{ $complaint->created_at?->format('M j, Y g:i A') }}</dd></div>
                    </dl>
                    <div class="admin-prose-block">
                        <h2>Resident description</h2>
                        <p>{{ $complaint->description }}</p>
                    </div>
                    @if($complaint->address_text)
                        <div class="admin-prose-block">
                            <h2>Reported location</h2>
                            <p>{{ $complaint->address_text }}</p>
                        </div>
                    @endif

                    {{--
                        The exact position, for whoever is dispatched.

                        An admin was seeing nothing here: this block only ever
                        existed for address_text, and the exact position reached
                        it only by accident, because the filing form wrote the
                        coordinate into that field. Stopping that would have left
                        the administrator with no position at all, so it is stated
                        here explicitly. The public transparency page shows no
                        more than the barangay, which makes this the only place
                        the precise point appears - as it should be.
                    --}}
                    @if($complaint->latitude !== null && $complaint->longitude !== null)
                        <div class="admin-prose-block">
                            <h2>Exact coordinates</h2>
                            <p class="admin-coords">
                                {{ number_format((float) $complaint->latitude, 6) }},
                                {{ number_format((float) $complaint->longitude, 6) }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="resident-details-title">
                <div class="admin-panel-heading">
                    <h2 id="resident-details-title" class="admin-panel-title">Resident details</h2>
                </div>
                <div class="admin-panel-body">
                    <dl class="admin-detail-list">
                        <div><dt>Name</dt><dd>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $complaint->user?->email ?? 'Not provided' }}</dd></div>
                        <div><dt>Contact</dt><dd>{{ $complaint->user?->contact_number ?? 'Not provided' }}</dd></div>
                        <div><dt>Barangay</dt><dd>{{ $complaint->user?->barangay ?? 'Not provided' }}</dd></div>
                    </dl>
                </div>
            </section>

            @if($complaint->evidence_images)
                <section class="admin-panel" aria-labelledby="evidence-title">
                    <div class="admin-panel-heading">
                        <div>
                            <h2 id="evidence-title" class="admin-panel-title">Evidence photos</h2>
                            <p class="admin-section-description">{{ $complaint->evidence_image_count }} {{ \Illuminate\Support\Str::plural('photo', $complaint->evidence_image_count) }} attached to this report.</p>
                        </div>
                    </div>
                    <div class="admin-panel-body">
                        <div class="admin-evidence-grid">
                            @foreach($complaint->evidence_images as $imageIndex => $imagePath)
                                <a href="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" class="admin-evidence-card">
                                    <img src="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" alt="Complaint evidence photo {{ $imageIndex + 1 }}" loading="lazy">
                                    <span>Photo {{ $imageIndex + 1 }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <aside class="admin-detail-aside">
            <section class="admin-panel" aria-labelledby="complaint-update-title">
                <div class="admin-panel-heading">
                    <h2 id="complaint-update-title" class="admin-panel-title">Update complaint</h2>
                </div>
                <form data-loading-submit method="POST" action="{{ route('admin.complaints.update', $complaint) }}" enctype="multipart/form-data" class="admin-panel-body admin-form-stack">
                    @csrf
                    @method('PUT')

                    <label class="admin-field">
                        <span>Status</span>
                        <select name="status" id="admin-status" class="admin-input">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" @selected($complaint->status === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field">
                        <span>Review status</span>
                        <select name="review_status" class="admin-input">
                            @foreach($reviewStatusOptions as $reviewOption)
                                <option value="{{ $reviewOption->value }}" @selected($complaint->review_status === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field">
                        <span>Confirmed priority</span>
                        <select name="confirmed_priority" class="admin-input">
                            <option value="">Not confirmed</option>
                            @foreach($priorityOptions as $priorityOption)
                                <option value="{{ $priorityOption->value }}" @selected($complaint->confirmed_priority === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{--
                        Public visibility is no longer an administrative field. It
                        is the resident's decision, set when they file and
                        changeable only by them, so there is nothing here to edit.
                        Use the moderation panel below to withhold or redact the
                        public presentation; that is a different power and the
                        one this screen legitimately has.
                    --}}
                    <div class="admin-field">
                        <span>Public visibility</span>
                        <span class="admin-checkbox-field">
                            <strong>{{ $complaint->is_public ? 'Published by the resident' : 'Kept private by the resident' }}</strong>
                            <span>Not editable here. An admin can moderate the public page but cannot consent on a resident's behalf.</span>
                        </span>
                    </div>

                    <label class="admin-field">
                        <span>Review notes</span>
                        <textarea name="review_notes" class="admin-input admin-textarea" rows="3" placeholder="Optional verification notes.">{{ old('review_notes', $complaint->review_notes) }}</textarea>
                    </label>

                    <label class="admin-field">
                        <span>Department</span>
                        <select name="department_id" class="admin-input" data-complaint-department>
                            <option value="">Unassigned</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) $complaint->department_id === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field">
                        <span>Assigned staff</span>
                        <select name="assigned_staff_id" class="admin-input" data-assigned-staff>
                            <option value="">Unassigned</option>
                            @foreach($staffMembers as $staff)
                                <option value="{{ $staff->id }}" data-department-id="{{ $staff->department_id }}" @selected((string) $complaint->assigned_staff_id === (string) $staff->id)>
                                    {{ $staff->full_name }} - {{ $staff->department?->name ?? 'Unassigned' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field">
                        <span>Internal note</span>
                        <textarea name="staff_note" class="admin-input admin-textarea" rows="5" placeholder="Record a concise update for the audit trail."></textarea>
                    </label>

                    {{-- The same proof the department owes: labor work is proven by
                         a photograph of it, and an administrator settles a
                         ticket no more loosely than the office does. --}}
                    <x-proof-capture
                        name="documentation_images"
                        camera-name="documentation_camera_photos"
                        status-select="admin-status"
                        :initial-status="$complaint->status"
                        :require-statuses="$documentationRequiredStatuses"
                    />

                    <button type="submit" class="admin-button admin-button--primary">Save update</button>
                </form>
            </section>

            @php
                $adminOutstandingRequest = $complaint->informationRequests()->whereIn('status', ['open', 'answered'])->latest('id')->first();
            @endphp
            <section class="admin-panel" aria-labelledby="ask-resident-title">
                <div class="admin-panel-heading">
                    <h2 id="ask-resident-title" class="admin-panel-title">Ask the resident for more evidence</h2>
                </div>
                <div class="admin-panel-body">
                    @if($adminOutstandingRequest)
                        <p class="admin-section-description"><strong>{{ $adminOutstandingRequest->status->label() }} · round {{ $adminOutstandingRequest->round }}</strong></p>
                        <p class="admin-section-description">{{ $adminOutstandingRequest->question }}</p>
                        @if($adminOutstandingRequest->response_text)
                            <p class="admin-section-description"><strong>Resident replied:</strong> {{ $adminOutstandingRequest->response_text }}</p>
                        @endif
                    @else
                        <p class="admin-section-description">Asking moves the report to <em>Needs information</em> and the resident gets the words you type — so make it specific about what would settle it. The resident replies on their ticket page.</p>
                        <form data-loading-submit method="POST" action="{{ route('admin.complaints.verification.request-information', $complaint) }}" class="admin-form-stack">
                            @csrf
                            <label class="admin-field">
                                <span>What&rsquo;s missing?</span>
                                <textarea name="question" class="admin-input admin-textarea" rows="3" minlength="15" maxlength="1000" required placeholder="The streetlight photo is too dark to read the pole number. Please send a closer shot.">{{ old('question') }}</textarea>
                                @error('question')<p class="admin-field-error">{{ $message }}</p>@enderror
                            </label>
                            <button type="submit" class="admin-button admin-button--secondary">Ask the resident</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="complaint-history-title">
                <div class="admin-panel-heading">
                    <h2 id="complaint-history-title" class="admin-panel-title">Audit history</h2>
                </div>
                <div class="admin-panel-body">
                    <ol class="admin-timeline">
                        @forelse($complaint->logs->sortByDesc('created_at') as $log)
                            <li class="admin-timeline-item">
                                <div class="admin-timeline-marker" aria-hidden="true"></div>
                                <div>
                                    <p class="admin-timeline-status">{{ $log->previous_status ?? 'Complaint filed' }} <span aria-hidden="true">→</span> {{ $log->new_status }}</p>
                                    @if($log->comment)<p class="admin-timeline-comment">{{ $log->comment }}</p>@endif
                                    @if($log->documentation_images)
                                        @php $evidenceRows = $log->documentation_evidence; @endphp
                                        <div class="admin-evidence-grid admin-timeline-evidence">
                                            @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                @php $evidenceRow = $evidenceRows[$documentationIndex] ?? null; @endphp
                                                <a href="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener" class="admin-evidence-card">
                                                    <img src="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('staff.proof.timeline_alt', ['index' => $documentationIndex + 1]) }}" loading="lazy">
                                                    <span>Proof {{ $documentationIndex + 1 }}</span>
                                                    @if($evidenceRow?->hasLocation())
                                                        <span class="admin-evidence-card__location">{{ $evidenceRow->locationLabel() }}</span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="admin-timeline-meta">{{ $log->actor?->full_name ?? 'System' }} · {{ $log->created_at?->format('M j, Y g:i A') }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="admin-empty-cell">No audit entries yet.</li>
                        @endforelse
                    </ol>
                </div>
            </section>
        </aside>
    </div>
</div>

{{--
    Admin moderation. Same capability and same limits as the department
    verifier's: withhold or redact, with a reason, and never publish.
--}}
<x-moderation-panel
    :complaint="$complaint"
    :action="route('admin.complaints.moderate', $complaint)"
    id-prefix="admin"
/>

@endsection

@push('scripts')
<script>
    (() => {
        const department = document.querySelector('[data-complaint-department]');
        const assignedStaff = document.querySelector('[data-assigned-staff]');
        if (!department || !assignedStaff) return;

        const syncStaffOptions = () => {
            const departmentId = department.value;
            let selectedIsVisible = false;

            [...assignedStaff.options].forEach((option) => {
                if (!option.value) return;
                const isVisible = departmentId !== '' && option.dataset.departmentId === departmentId;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
                if (isVisible && option.selected) selectedIsVisible = true;
            });

            if (!selectedIsVisible) assignedStaff.value = '';
        };

        department.addEventListener('change', syncStaffOptions);
        syncStaffOptions();
    })();
</script>
@endpush
