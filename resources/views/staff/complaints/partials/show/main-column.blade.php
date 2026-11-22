            {{-- LEFT COLUMN: the complaint itself --}}
            <div class="staff-detail-col staff-detail-col--main">
                {{-- 1. Complaint Description Card --}}
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--description">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h2 class="tr-h2">Complaint description</h2>
                    </div>
                    <p style="font-size: 14px; line-height: 1.75; color: var(--navy-900); margin: 0; white-space: pre-line;">{{ $complaint->description }}</p>
                </div>

                {{-- Conversation thread --}}
                @php
                    $conversationMessages = $complaint->messages->sortBy('created_at')->values();
                    $canPostFollowUpMsg = app(\App\Services\ComplaintFollowUpService::class)->canPostMessage($complaint, auth()->user());
                @endphp
                <div class="tr-card workspace-card staff-detail-card staff-conversation">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div class="staff-conversation__heading">
                            <h2 class="tr-h2">{{ __('complaint_show.follow_up.thread_title') }}</h2>
                        </div>
                    </div>

                    @if($conversationMessages->isEmpty())
                        <div class="staff-conversation__empty">
                            <p>{{ __('complaint_show.follow_up.thread_empty') }}</p>
                        </div>
                    @else
                        <div class="staff-conversation__thread" role="log" aria-label="{{ __('complaint_show.follow_up.thread_title') }}">
                            @foreach($conversationMessages as $msg)
                                @php
                                    $isResident = $msg->sender_type === 'resident' || (!$msg->sender_type && $msg->user);
                                    $senderLabel = match($msg->sender_type) {
                                        'admin' => __('complaint_show.follow_up.sender_admin'),
                                        'staff' => __('complaint_show.follow_up.sender_staff'),
                                        default => ($msg->user?->name ?? 'Resident'),
                                    };
                                @endphp
                                <article class="staff-conversation__msg {{ $isResident ? 'staff-conversation__msg--resident' : 'staff-conversation__msg--office' }}">
                                    <div class="staff-conversation__msg-meta">
                                        <span class="staff-conversation__sender">{{ $senderLabel }}</span>
                                        <span class="staff-conversation__time">{{ $msg->created_at?->format('M d, Y · g:i A') }}</span>
                                    </div>
                                    <div class="staff-conversation__body">{{ $msg->body }}</div>
                                    @if($msg->images !== [])
                                        <div class="staff-conversation__attachments">
                                            @foreach($msg->images as $idx => $imgPath)
                                                <a href="{{ route('complaints.messages.evidence', [$complaint, $msg, $idx]) }}" target="_blank" rel="noopener" class="staff-conversation__attachment">
                                                    <img src="{{ route('complaints.messages.evidence', [$complaint, $msg, $idx]) }}" alt="Attachment {{ $idx + 1 }}" loading="lazy">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif

                    @if($canPostFollowUpMsg)
                        <form method="POST" action="{{ route('complaints.follow_up.store', $complaint) }}" enctype="multipart/form-data" class="staff-conversation__form">
                            @csrf
                            <div class="staff-conversation__form-head">
                                <label class="staff-conversation__form-label" for="follow-up-body">{{ __('complaint_show.follow_up.body_label') }}</label>
                            </div>
                            <textarea id="follow-up-body" name="body" rows="4" minlength="10" maxlength="2000" required class="tr-input staff-conversation__textarea" placeholder="{{ __('complaint_show.follow_up.body_placeholder') }}">{{ old('body') }}</textarea>
                            <div class="staff-conversation__form-footer">
                                <label class="staff-conversation__file-btn" aria-label="{{ __('complaint_show.follow_up.attach_images', ['default' => 'Attach images']) }}">
                                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                </label>
                                <button type="submit" class="staff-conversation__file-btn staff-conversation__submit staff-conversation__submit--icon" aria-label="{{ __('complaint_show.follow_up.submit') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    <span class="sr-only">{{ __('complaint_show.follow_up.submit') }}</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                {{-- 3. Activity History Timeline Card --}}
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--timeline">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h2 class="tr-h2">Activity history timeline</h2>
                    </div>

                    @if($complaint->logs->isEmpty())
                        <p style="color: var(--faint); font-size: 13px; margin: 0; text-align: center; padding: 12px 0;">No activity logs recorded yet.</p>
                    @else
                        <div style="position: relative; padding-left: 18px; display: flex; flex-direction: column; gap: 16px;">
                            <div style="position: absolute; left: 4px; top: 6px; bottom: 6px; width: 2px; background: var(--line);"></div>
                            @foreach($complaint->logs->sortByDesc('created_at') as $log)
                                <div style="position: relative; font-size: 12px;">
                                    <div style="position: absolute; left: -18px; top: 3px; width: 8px; height: 8px; border-radius: 50%; background: {{ $log->is_internal ? 'var(--status-progress-dot)' : 'var(--navy-800)' }}; border: 2px solid var(--surface);"></div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 3px;">
                                        <strong style="color: var(--navy-900);">{{ $log->actor->staff_actor_name ?? 'System' }}</strong>
                                        <span style="font-size: 10px; color: var(--faint);">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($log->previous_status && $log->new_status)
                                        <div style="display: inline-block; padding: 2px 6px; background: var(--bg); border-radius: 10px; font-size: 10px; font-weight: 600; color: var(--navy-800); margin-bottom: 4px;">
                                            {{ $log->previous_status }} &rarr; {{ $log->new_status }}
                                        </div>
                                    @endif
                                    @if($log->comment)
                                        <p style="margin: 3px 0; color: var(--muted); line-height: 1.45;">{{ $log->comment }}</p>
                                    @endif

                                    {{-- The proof this update carried, and where each
                                         photograph was taken. A photo of a repair
                                         only means something if the place it was
                                         taken is on the record. --}}
                                    @if($log->documentation_images)
                                        @php $evidenceRows = $log->documentation_evidence; @endphp
                                        <div class="proof-timeline">
                                            @foreach($log->documentation_images as $documentationIndex => $documentationPath)
                                                @php $evidenceRow = $evidenceRows[$documentationIndex] ?? null; @endphp
                                                <figure class="proof-timeline__item">
                                                    <a href="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" target="_blank" rel="noopener">
                                                        <img src="{{ route('complaints.updates.evidence', [$complaint, $log, $documentationIndex]) }}" alt="{{ __('staff.proof.timeline_alt', ['index' => $documentationIndex + 1]) }}" loading="lazy">
                                                    </a>
                                                    @if($evidenceRow?->hasLocation())
                                                        <figcaption class="proof-timeline__location">
                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                            <span>{{ $evidenceRow->locationLabel() }}</span>
                                                        </figcaption>
                                                    @else
                                                        <figcaption class="proof-timeline__location proof-timeline__location--none">
                                                            {{ __('staff.proof.no_location') }}
                                                        </figcaption>
                                                    @endif
                                                </figure>
                                            @endforeach
                                        </div>
                                    @endif
                                    <span style="font-size: 10px; color: {{ $log->is_internal ? 'var(--status-progress-dot)' : 'var(--success)' }}; font-weight: 600;">
                                        {{ $log->is_internal ? 'Internal Staff Note' : 'Public Update' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- 3. Resident Evidence Photos Card --}}
                @if($complaint->evidence_images && count($complaint->evidence_images) > 0)
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--evidence">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <h2 class="tr-h2">Resident evidence photos ({{ count($complaint->evidence_images) }})</h2>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px;">
                            @foreach($complaint->evidence_images as $imageIndex => $imagePath)
                                <a href="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" style="display: block; border-radius: 6px; overflow: hidden; border: 1px solid var(--line-soft); background: var(--surface-soft); aspect-ratio: 1;">
                                    <img src="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" alt="Evidence {{ $imageIndex + 1 }}" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 4. Review and Verification — editable until settled (Verified/Rejected/Duplicate),
                     then shown as a read-only record for everyone. --}}
                @php
                    $reviewLocked = in_array($complaint->review_status, [
                        \App\Enums\ComplaintReviewStatus::Verified->value,
                        \App\Enums\ComplaintReviewStatus::Rejected->value,
                        \App\Enums\ComplaintReviewStatus::Duplicate->value,
                    ], true);
                    $recordedMethod = $complaint->verificationMethodEnum()?->label() ?? 'Not recorded';
                    $recordedNotes = trim((string) ($complaint->review_notes ?? ''));
                @endphp
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--review" style="border-top: 3px solid var(--gold-300);" data-review-panel data-review-ticket="{{ $complaint->ticket_id }}">
                    <div class="workspace-card__header" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"/></svg>
                            </div>
                            <h2 class="tr-h2" style="margin:0;">Review and verification</h2>
                        </div>
                        @if(! $reviewLocked && $complaint->isVerified() && $user->canVerifyComplaints())
                            <button type="button" class="review-panel-toggle" data-review-toggle
                                    aria-expanded="false"
                                    aria-label="{{ __('staff.verification.minimize_label') }}">
                                <svg class="review-toggle__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 12 18 9"/></svg>
                                <span class="review-toggle__text">{{ __('staff.verification.minimize') }}</span>
                            </button>
                        @endif
                    </div>

                    <div class="review-status-subgrid" style="margin-bottom: {{ $reviewLocked ? '12px' : '16px' }};">
                        <div style="padding: 10px; background: var(--surface-soft); border-radius: 6px; border: 1px solid var(--line-soft);">
                            <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 2px;">Review status</span>
                            <strong style="font-size: 12px; color: var(--navy-900);">{{ $complaint->reviewStatusEnum->label() }}</strong>
                        </div>
                        <div style="padding: 10px; background: var(--surface-soft); border-radius: 6px; border: 1px solid var(--line-soft);">
                            <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 2px;">Suggested priority</span>
                            <strong style="font-size: 12px; color: var(--navy-900);">{{ $complaint->suggestedPriorityEnum->label() }}</strong>
                        </div>
                        <div style="padding: 10px; background: var(--surface-soft); border-radius: 6px; border: 1px solid var(--line-soft);">
                            <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 2px;">Confirmed priority</span>
                            <strong style="font-size: 12px; color: var(--navy-800);">{{ $complaint->confirmedPriorityEnum?->label() ?? 'Not confirmed' }}</strong>
                        </div>
                    </div>

                    @if($reviewLocked)
                        <div class="review-locked-summary">
                            <div style="padding: 10px 12px; background: var(--surface-soft); border-radius: 6px; border: 1px solid var(--line-soft);">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 4px;">Verification method</span>
                                <p style="margin: 0; font-size: 13px; color: var(--navy-900);">{{ $recordedMethod }}</p>
                            </div>
                            <div style="padding: 10px 12px; background: var(--surface-soft); border-radius: 6px; border: 1px solid var(--line-soft);">
                                <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 4px;">Review notes</span>
                                @if($recordedNotes !== '')
                                    <p style="margin: 0; font-size: 13px; line-height: 1.55; color: var(--navy-900); white-space: pre-line;">{{ $recordedNotes }}</p>
                                @else
                                    <p style="margin: 0; font-size: 13px; color: var(--faint);">No review notes recorded.</p>
                                @endif
                            </div>
                            <p style="margin: 2px 0 0; font-size: 12px; color: var(--faint); line-height: 1.45;">
                                This review decision is final and can no longer be changed here.
                            </p>
                        </div>
                    @else
                        {{-- Collapsible body: the editable form (verifier) / read-only line (non-verifier).
                             Collapsed by default once the complaint is verified, so the settled
                             review no longer takes up space. The choice is remembered in
                             localStorage and survives reloads / Livewire re-renders. --}}
                        <div class="review-panel__body" data-review-body @if($complaint->isVerified() && $user->canVerifyComplaints()) hidden @endif>
                            @if($user->canVerifyComplaints())
                            <form method="POST" action="{{ route('staff.complaints.review', $complaint) }}" style="display: flex; flex-direction: column; gap: 16px;">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 6px;" for="review_status">Review decision</label>
                                    <select name="review_status" id="review_status" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--navy-900); font-size: 13px;" required>
                                        @foreach($reviewStatusOptions as $reviewOption)
                                            <option value="{{ $reviewOption->value }}" @selected($complaint->review_status === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 6px;" for="confirmed_priority">Confirmed priority</label>
                                    <select name="confirmed_priority" id="confirmed_priority" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--navy-900); font-size: 13px;">
                                        <option value="">Not confirmed</option>
                                        @foreach($priorityOptions as $priorityOption)
                                            <option value="{{ $priorityOption->value }}" @selected($complaint->confirmed_priority === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 6px;" for="verification_method">Verification method</label>
                                    <select name="verification_method" id="verification_method" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--navy-900); font-size: 13px;">
                                        <option value="">Not recorded</option>
                                        @foreach(\App\Enums\VerificationMethod::cases() as $methodOption)
                                            <option value="{{ $methodOption->value }}" @selected($complaint->verification_method === $methodOption->value)>{{ $methodOption->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 6px;" for="review_notes">Review notes</label>
                                    <textarea name="review_notes" id="review_notes" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--navy-900); font-size: 13px;" placeholder="Record investigation findings...">{{ old('review_notes', $complaint->review_notes) }}</textarea>
                                </div>
                                <button type="submit" class="tr-button" style="width: 100%; justify-content: center; min-height: 44px; background: var(--navy-800); color: var(--surface); font-weight: 700; border-radius: 6px; font-size: 13px;">
                                    Save review decision
                                </button>
                            </form>
                            @else
                            {{-- The page never advertises a control the service would
                                 refuse: non-verifiers see the state of the review, not
                                 a form that bounces them. The server refuses the write
                                 too — ComplaintVerificationService::authorize. --}}
                            <div class="staff-action-panel__state" style="margin: 0;">
                                <p style="margin: 0;">{{ __('staff.verification.read_only_body') }}</p>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Office marking — a field officer or inspector may reject
                     a report as misleading or spam. The field officer always
                     writes a description and a comment; the panel returns as
                     the read-only record once the report is rejected. --}}
                @include('staff.complaints.partials.marking-panel', [
                    'complaint' => $complaint,
                    'user' => $user,
                    'canMark' => $user->isDepartmentStaff()
                        && $complaint->statusEnum->isOpen()
                        && ! $complaint->isMarked(),
                ])

                {{-- 5. Verification follow-up — the two actions the November
                     rewrite dropped: ask the resident for better evidence, and
                     hand verification to another verifier. The server has
                     always supported both; this is the surface that lets a
                     staff member reach them. --}}
                @if($user->canVerifyComplaints() && $verificationOpen)
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--followup staff-action-panel" style="border-top: 3px solid var(--status-review-dot);">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"/></svg>
                            </div>
                            <h2 class="tr-h2">{{ __('staff.verification.follow_up') }}</h2>
                        </div>

                        <div class="staff-action-panel__section">
                            @if($openInformationRequest)
                                @if($openInformationRequest->isOpen())
                                    <div class="staff-action-panel__state staff-action-panel__state--waiting">
                                        <strong>{{ __('staff.verification.waiting_title') }}</strong>
                                        <p>{{ __('staff.verification.waiting_body') }}</p>
                                    </div>
                                @else
                                    <div class="staff-action-panel__state">
                                        <strong>{{ __('staff.verification.answered_title') }}</strong>
                                        <p>{{ __('staff.verification.answered_body') }}</p>
                                    </div>
                                @endif

                                <p class="staff-action-panel__hint"><strong>{{ __('staff.verification.round', ['round' => $openInformationRequest->round, 'max' => \App\Services\ComplaintVerificationService::MAX_INFORMATION_ROUNDS]) }}</strong></p>
                                <blockquote class="staff-action-panel__quote">{{ $openInformationRequest->question }}</blockquote>

                                @if($openInformationRequest->isAnswered())
                                    <p class="staff-action-panel__hint" style="margin-top: 12px;"><strong>{{ __('staff.verification.reply_title') }}</strong></p>
                                    <blockquote class="staff-action-panel__quote">{{ $openInformationRequest->response_text }}</blockquote>
                                    @php $replyPhotos = (array) ($openInformationRequest->response_image_paths ?? []); @endphp
                                    @if($replyPhotos !== [])
                                        <p class="staff-action-panel__hint" style="margin-top: 12px;"><strong>{{ __('staff.verification.reply_photos') }}</strong></p>
                                        <div class="staff-action-panel__photos">
                                            @foreach($replyPhotos as $replyPhotoIndex => $replyPhotoPath)
                                                <a href="{{ route('complaints.information.evidence', [$complaint, $openInformationRequest, $replyPhotoIndex]) }}" target="_blank" rel="noopener" class="staff-action-panel__photo">
                                                    <img src="{{ route('complaints.information.evidence', [$complaint, $openInformationRequest, $replyPhotoIndex]) }}" alt="{{ __('staff.verification.reply_photo_alt', ['index' => $replyPhotoIndex + 1]) }}" loading="lazy">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            @elseif($awaitingResident)
                                <div class="staff-action-panel__state staff-action-panel__state--waiting">
                                    <strong>{{ __('staff.verification.waiting_title') }}</strong>
                                    <p>{{ trim((string) $complaint->review_notes) !== ''
                                        ? __('staff.verification.orphan_waiting')
                                        : __('staff.verification.orphan_no_question') }}</p>
                                </div>
                            @elseif($canAskForInformation)
                                <h3 class="staff-action-panel__section-title">{{ __('staff.verification.ask_title') }}</h3>
                                <p class="staff-action-panel__hint">{{ __('staff.verification.ask_body', ['round' => $informationRounds + 1, 'max' => \App\Services\ComplaintVerificationService::MAX_INFORMATION_ROUNDS]) }}</p>

                                <form method="POST" action="{{ route('staff.complaints.verification.request-information', $complaint) }}">
                                    @csrf

                                    <div class="staff-field">
                                        <label for="verification-question">{{ __('staff.verification.question_label') }}</label>
                                        <textarea id="verification-question" name="question" rows="3" required minlength="15" maxlength="1000" placeholder="{{ __('staff.verification.question_placeholder') }}">{{ old('question') }}</textarea>
                                        <p class="staff-field__hint">{{ __('staff.verification.question_hint') }}</p>
                                        @error('question')<p class="staff-field__error">{{ $message }}</p>@enderror
                                    </div>

                                    <button type="submit" class="staff-action-panel__submit">{{ __('staff.verification.ask_submit') }}</button>
                                </form>
                            @elseif($informationRounds >= \App\Services\ComplaintVerificationService::MAX_INFORMATION_ROUNDS)
                                <p class="staff-action-panel__hint">{{ __('staff.verification.rounds_exhausted') }}</p>
                            @endif
                        </div>

                        @if($canDelegateVerification)
                            <div class="staff-action-panel__section">
                                <h3 class="staff-action-panel__section-title">{{ __('staff.verification.delegate_title') }}</h3>
                                <p class="staff-action-panel__hint">{{ __('staff.verification.delegate_body') }}</p>

                                <form method="POST" action="{{ route('staff.complaints.verification.delegate', $complaint) }}">
                                    @csrf

                                    <div class="staff-field">
                                        <label for="verifying-staff">{{ __('staff.verification.delegate_label') }}</label>
                                        <select id="verifying-staff" name="verifying_staff_id" required>
                                            <option value="">{{ __('staff.verification.delegate_choose') }}</option>
                                            @foreach($delegationCandidates as $verifier)
                                                <option value="{{ $verifier->id }}" @selected((int) old('verifying_staff_id', $complaint->verifying_staff_id) === $verifier->id)>{{ $verifier->full_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('verifying_staff_id')<p class="staff-field__error">{{ $message }}</p>@enderror
                                    </div>

                                    <button type="submit" class="staff-action-panel__submit">{{ __('staff.verification.delegate_submit') }}</button>
                                </form>
                            </div>
                        @endif

                        @if($complaint->verifyingStaff?->full_name)
                            <p class="staff-action-panel__hint" style="margin: 14px 0 0;">
                                {{ __('staff.verification.delegate_current', ['name' => $complaint->verifyingStaff->full_name]) }}
                            </p>
                        @endif
                    </div>
                @endif
                @php
                    // Only the statuses this complaint can legally move to by
                    // hand. The server refuses every other transition, so
                    // offering one here only gives the staff member a dead end.
                    $availableStatuses = $complaint->statusEnum->allowedManualTransitions();
                    // The browser selects the first option that is not marked
                    // selected, and this dropdown never marks one — so this is
                    // the status the form opens on.
                    $initialStatus = collect($availableStatuses)->first()?->value;
                @endphp

                @if($complaint->isVerified() && $availableStatuses !== [])
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--response" style="border-top: 3px solid var(--success);">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon" style="background: rgba(63,203,111,0.12); color: var(--success);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <h2 class="tr-h2">Add official response</h2>
                        </div>
                        <form method="POST" action="{{ route('staff.complaints.update', $complaint) }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                            @csrf
                            @method('PUT')
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 6px;" for="status">Status update</label>
                                <select name="status" id="status" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--navy-900); font-size: 13px;" required>
                                    @foreach($availableStatuses as $statusOption)
                                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--faint); margin-bottom: 6px;" for="note">Public response note</label>
                                <textarea name="note" id="note" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 6px; background: var(--surface); color: var(--navy-900); font-size: 13px;" required minlength="10" placeholder="Write update visible to resident...">{{ old('note') }}</textarea>
                            </div>

                            {{-- The internal half of the update: context for the
                                 office, written to the same immutable log spine
                                 but never shown to the resident or the public. --}}
                            <div class="staff-field">
                                <label for="internal_note">{{ __('staff.internal_note.label') }} <span style="font-weight: 600; text-transform: none; letter-spacing: 0;">{{ __('staff.internal_note.optional') }}</span></label>
                                <textarea name="internal_note" id="internal_note" rows="2" maxlength="2000" placeholder="{{ __('staff.internal_note.placeholder') }}">{{ old('internal_note') }}</textarea>
                                <p class="staff-field__hint">{{ __('staff.internal_note.hint') }}</p>
                                @error('internal_note')<p class="staff-field__error">{{ $message }}</p>@enderror
                            </div>

                            {{-- The proof the resident will see. The office cannot say
                                 the work is under way, or that it is done, without a
                                 photograph of it. --}}
                            <x-proof-capture
                                name="documentation_images"
                                camera-name="documentation_camera_photos"
                                status-select="status"
                                :initial-status="$initialStatus"
                                :require-statuses="$documentationRequiredStatuses"
                            />

                            <button type="submit" class="tr-button" style="width: 100%; justify-content: center; min-height: 44px; background: var(--success); color: var(--surface); font-weight: 700; border-radius: 6px; font-size: 13px;">
                                Send response &amp; update status
                            </button>
                        </form>
                    </div>
                @endif

            </div>

