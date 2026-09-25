@extends('admin.layouts.app')

@section('title', $complaint->ticket_id . ' — Admin')
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
                        <div><dt>Department</dt><dd>{{ $complaint->department?->name ?? 'Unassigned' }}</dd></div>
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
                <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="admin-panel-body admin-form-stack">
                    @csrf
                    @method('PUT')

                    <label class="admin-field">
                        <span>Status</span>
                        <select name="status" class="admin-input">
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

                    <label class="admin-field">
                        <span>Public visibility</span>
                        <span class="admin-checkbox-field">
                            <input type="hidden" name="is_public" value="0">
                            <input type="checkbox" name="is_public" value="1" @checked($complaint->is_public)>
                            <span>Show on the public transparency register after verification</span>
                        </span>
                    </label>

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
                                    {{ $staff->full_name }} — {{ $staff->department?->name ?? 'Unassigned' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field">
                        <span>Internal note</span>
                        <textarea name="staff_note" class="admin-input admin-textarea" rows="5" placeholder="Record a concise update for the audit trail."></textarea>
                    </label>

                    <button type="submit" class="admin-button admin-button--primary">Save update</button>
                </form>
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
