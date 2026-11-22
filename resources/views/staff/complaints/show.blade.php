@extends('staff.layouts.app')
@section('title', $complaint->ticket_id . ' - Complaint Investigation Workspace')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page staff-complaint-detail" style="background: var(--bg); min-height: calc(100svh - var(--navbar-height)); padding-bottom: 60px;">
    
    @php
        $statusEnum = $complaint->statusEnum;
        $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '_');
        $ageNow = now();
        $ageSeconds = max(0, (int) $complaint->created_at->diffInSeconds($ageNow));
        $ageDays = (int) floor($ageSeconds / 86400);
        $ageMinutes = (int) floor($ageSeconds / 60);
        $ageHours = (int) floor($ageSeconds / 3600);
        $ageLabel = $ageDays >= 1
            ? $ageDays.' day'.($ageDays === 1 ? '' : 's')
            : ($ageSeconds < 3600
                ? max(1, $ageMinutes).' minute'.($ageMinutes === 1 ? '' : 's')
                : $ageHours.' hour'.($ageHours === 1 ? '' : 's'));
    @endphp

    {{-- ════════════ BANNER (publication-requests protocol) ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 16px;">
            <div class="banner-top-row" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="ticket-id">{{ $complaint->ticket_id }}</span>
                    <span class="dept-badge" data-dept="{{ $complaint->department?->code ?? '' }}">{{ $complaint->department?->code ?? __('nav.departments') }}</span>
                    <span class="status-pill {{ $statusKey }}" data-ticket-status-pill style="font-size: 11px; padding: 3px 10px;">
                        <span class="status-pill__dot" aria-hidden="true"></span>
                        <span data-ticket-status-label>{{ $statusEnum->label() }}</span>
                    </span>
                    @if($complaint->confirmedPriorityEnum)
                        @php
                            $priorityValue = $complaint->confirmedPriorityEnum->value;
                            $priorityTone = match($priorityValue) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                default => 'neutral'
                            };
                        @endphp
                        <span class="status-pill status-pill--{{ $priorityTone }}" style="font-size: 11px; padding: 3px 10px;">
                            {{ $complaint->confirmedPriorityEnum->label() }}
                        </span>
                    @endif

                    {{-- How long the office has left on this one. --}}
                    @if($slaSummary['state'] !== \App\Enums\ComplaintSlaState::NotApplicable)
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
                </div>
            </div>

            <h1 class="tr-h1">{{ $complaint->title }}</h1>

            <p class="tr-privacy">
                <span style="font-weight: 600;">Filed:</span> {{ $complaint->created_at->format('M j, Y') }}
                · <span style="font-weight: 600;">Work:</span> {{ $workMode->label() }}
                · <span style="font-weight: 600;">Age:</span> <strong style="color: var(--gold-300);">{{ $ageLabel }} open</strong>
            </p>
        </div>
    </header>

    {{-- ════════════ MAIN CONTAINER ════════════ --}}
    <div class="tr-container" style="max-width: none; padding: 0 16px; margin-top: 20px;">
        
        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="tr-card" style="background: rgba(63,203,111,0.12); border-color: rgba(63,203,111,0.3); color: var(--success); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-weight: 600; font-size: 13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="tr-card" style="background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.3); color: var(--danger); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-weight: 600; font-size: 13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($complaint->isModerationFlagged() || $complaint->hidden_from_public)
            <section class="tr-card" aria-labelledby="complaint-flag-notice-title" style="margin-bottom: 20px; padding: 16px 18px; border: 1px solid color-mix(in srgb, var(--status-progress-dot) 35%, var(--line-soft)); background: var(--status-progress-bg); color: var(--status-progress-text);">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span aria-hidden="true" style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:999px; background: rgba(245,158,11,0.18); color: var(--status-progress-text); flex-shrink:0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </span>
                    <div style="min-width:0; display:grid; gap:6px;">
                        <h2 id="complaint-flag-notice-title" style="margin:0; font-size:14px; font-weight:700; color: var(--navy-900);">{{ __('staff.flag_notice.title') }}</h2>
                        <p style="margin:0; font-size:13px; line-height:1.5;">
                            @if($complaint->isModerationFlagged())
                                <strong>{{ $complaint->moderationLabel() }}</strong>
                                @if(! is_null($complaint->spam_score))
                                    · {{ __('staff.flag_notice.score', ['score' => $complaint->spam_score]) }}
                                @endif
                                @if($complaint->duplicate_of_id && $complaint->duplicateOf)
                                    · {{ __('staff.flag_notice.similar', ['percent' => (int) round(($complaint->similarity_score ?? 0) * 100)]) }}
                                @endif
                            @endif
                            @if($complaint->hidden_from_public)
                                @if($complaint->isModerationFlagged())<br>@endif
                                <strong>{{ __('staff.flag_notice.hidden_public') }}</strong>
                            @endif
                        </p>
                        @if($complaint->isModerationFlagged() && is_array($complaint->spam_reasons) && count($complaint->spam_reasons))
                            <ul style="margin:2px 0 0; padding-left:18px; font-size:12px; line-height:1.45; color: var(--navy-900);">
                                @foreach($complaint->spam_reasons as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($complaint->duplicate_of_id && $complaint->duplicateOf)
                            <a href="{{ route('staff.complaints.show', $complaint->duplicateOf) }}" style="font-size:12px; font-weight:600; color: var(--navy-900); text-decoration: underline;">{{ __('staff.flag_notice.compare', ['ticket' => $complaint->duplicateOf->ticket_id]) }}</a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Back Link (publication-requests protocol) --}}
        <a href="{{ route('staff.dashboard') }}" style="display: inline-flex; align-items: center; gap: 6px; margin: 18px 0 14px; color: var(--navy-700); font-size: 13px; font-weight: 600; text-decoration: none;">
            <span aria-hidden="true">←</span> {{ __('staff.back') }}
        </a>

        {{-- ════════════ LIVE TICKET ════════════
             Another hand may move this case while it is open. The panel
             polls a fingerprint and, when it differs, says who changed what.
             It never replaces the form: a staff member mid-sentence keeps
             every word they typed. --}}
        <div data-live-ticket
             data-endpoint="{{ route('staff.complaints.state', $complaint) }}"
             data-fingerprint="{{ $ticketFingerprint }}"
             data-interval="20000">

            <div class="ticket-live-note" data-live-ticket-notice role="status" aria-live="polite" hidden>
                <span class="ticket-live-note__dot" aria-hidden="true"></span>
                <p class="ticket-live-note__text" data-live-ticket-message></p>
                <div class="ticket-live-note__actions">
                    <a class="ticket-live-note__reload" data-live-ticket-refresh href="{{ route('staff.complaints.show', $complaint) }}">
                        {{ __('staff.live.refresh') }}
                    </a>
                    <button type="button" class="ticket-live-note__dismiss" data-live-ticket-dismiss>
                        {{ __('staff.live.dismiss') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Optimized Responsive Grid (Adapts intelligently across breakpoints) --}}
        <div class="staff-detail-grid">

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
                <div class="tr-card workspace-card staff-detail-card">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <h2 class="tr-h2">{{ __('complaint_show.follow_up.thread_title') }}</h2>
                    </div>
                    @if($conversationMessages->isEmpty())
                        <p style="color: var(--faint); font-size: 13px; margin: 0;">{{ __('complaint_show.follow_up.thread_empty') }}</p>
                    @else
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            @foreach($conversationMessages as $msg)
                                @php
                                    $senderLabel = match($msg->sender_type) {
                                        'admin' => __('complaint_show.follow_up.sender_admin'),
                                        'staff' => __('complaint_show.follow_up.sender_staff'),
                                        default => ($msg->user?->name ?? 'Resident'),
                                    };
                                @endphp
                                <div style="border:1px solid var(--border-subtle,#e5e7eb);border-radius:10px;padding:12px;">
                                    <div style="font-size:12px;color:var(--faint);margin-bottom:4px;">{{ $msg->created_at?->format('M d, Y · g:i A') }} · {{ $senderLabel }}</div>
                                    <div style="font-size:14px;white-space:pre-line;">{{ $msg->body }}</div>
                                    @if($msg->images !== [])
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                            @foreach($msg->images as $idx => $imgPath)
                                                <a href="{{ route('complaints.messages.evidence', [$complaint, $msg, $idx]) }}" target="_blank" rel="noopener"><img src="{{ route('complaints.messages.evidence', [$complaint, $msg, $idx]) }}" alt="Attachment {{ $idx + 1 }}" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid var(--border-subtle,#e5e7eb);"></a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($canPostFollowUpMsg)
                        <form method="POST" action="{{ route('complaints.follow_up.store', $complaint) }}" enctype="multipart/form-data" style="margin-top:14px;display:grid;gap:10px;">
                            @csrf
                            <label style="font-size:13px;font-weight:600;">{{ __('complaint_show.follow_up.body_label') }}</label>
                            <textarea name="body" rows="3" minlength="10" maxlength="2000" required class="tr-input" placeholder="{{ __('complaint_show.follow_up.body_placeholder') }}">{{ old('body') }}</textarea>
                            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                            <div><button type="submit" class="tr-btn tr-btn--primary">{{ __('complaint_show.follow_up.submit') }}</button></div>
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


            {{-- RIGHT COLUMN: who reported, where, and what to do next --}}
            <div class="staff-detail-col staff-detail-col--side">
                {{-- 1. Resident Information Card --}}
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--resident">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <h2 class="tr-h2">Resident information</h2>
                    </div>
                    <div class="report-row__head" style="margin: 10px 0 6px;">
                        <span class="ticket-id">{{ $complaint->ticket_id }}</span>
                        @if($complaint->department)
                            <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                        @endif
                    </div>
                    <p class="report-row__meta" style="margin-top: 0;">
                        {{ $complaint->category_label ?? $complaint->category }}
                        · {{ $complaint->department?->name ?? __('nav.departments') }}
                    </p>
                    @php
                        $residentAlias = $complaint->user?->staff_safe_name ?? 'Resident unavailable';
                        $trust = $complaint->user?->trustScoreSummary();
                    @endphp
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 14px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--navy-800); color: var(--gold-300); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0;">
                            {{ strtoupper(substr($residentAlias, 0, 1)) }}
                        </div>
                        <div>
                            <strong style="display: block; font-size: 14px; color: var(--navy-900);">{{ $residentAlias }}</strong>
                            <span style="font-size: 11px; color: var(--faint);">Identity protected · Staff view</span>
                        </div>
                    </div>
                    @if($trust)
                        <div style="margin-top: 12px; padding: 10px 12px; border: 1px solid var(--line-soft); border-radius: var(--radius-sm); background: var(--surface-soft);">
                            <div style="display: flex; justify-content: space-between; gap: 8px; align-items: center;">
                                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">{{ __('staff.trust.title') }}</span>
                                <strong style="font-size: 13px; color: var(--navy-900);">{{ $trust['score'] }}/100</strong>
                            </div>
                            <p style="margin: 4px 0 0; font-size: 12px; color: var(--text);">{{ $trust['label'] }} · {{ __('staff.trust.summary', ['good' => $trust['good'], 'bad' => $trust['bad']]) }}</p>
                        </div>
                    @endif
                    <p style="font-size: 11px; color: var(--faint); line-height: 1.45; margin: 10px 0 0; border-top: 1px solid var(--line-soft); padding-top: 8px;">
                        Strict privacy controls apply. Personal contact details remain confidential.
                    </p>
                </div>

                {{-- 2. Complaint Location & Map Card --}}
                @php
                    $hasExactPin = $complaint->latitude !== null && $complaint->longitude !== null;
                    $locationBarangay = filled($complaint->barangay) ? $complaint->barangay : $complaint->user?->barangay;
                    $directionsDestination = $hasExactPin
                        ? number_format((float) $complaint->latitude, 6, '.', '').','.number_format((float) $complaint->longitude, 6, '.', '')
                        : null;
                @endphp
                <div class="tr-card workspace-card staff-detail-card staff-detail-card--location location-card" style="overflow: hidden; padding: 0;">
                    <div class="workspace-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--line-soft);">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <h2 class="tr-h2">{{ __('staff.location.title') }}</h2>
                    </div>
                    @if($hasExactPin)
                        <div id="complaint-map" class="map-container"
                             role="region"
                             aria-label="{{ __('staff.location.map_aria') }}"
                             data-latitude="{{ (float) $complaint->latitude }}"
                             data-longitude="{{ (float) $complaint->longitude }}"
                             data-map-unavailable="{{ __('staff.location.map_unavailable') }}"></div>
                    @else
                        <div class="location-card__empty">
                            <span class="location-card__empty-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </span>
                            <strong>{{ __('staff.location.no_pin_title') }}</strong>
                            <p>{{ __('staff.location.no_pin_body') }}</p>
                        </div>
                    @endif
                    @if($hasExactPin || filled($locationBarangay))
                        <div class="location-card__footer">
                            @if(filled($locationBarangay))
                                <span class="location-card__meta">{{ __('staff.location.barangay_label') }}: <strong>{{ $locationBarangay }}</strong></span>
                            @endif
                            @if($hasExactPin)
                                <a class="tr-button location-card__directions"
                                   href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $directionsDestination }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   aria-label="{{ __('staff.location.directions_aria') }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                                    <span>{{ __('staff.location.directions') }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Assignment is available to office verifiers (DA and staff with
                     verification rights) for their own office, so work can be
                     assigned right after verification. Custom picker shows
                     workload and highlights the least-loaded recommendation. --}}
                @if($canAssignComplaint ?? false)
                    @php
                        $selectedAssigneeId = (string) old('assigned_staff_id', $complaint->assigned_staff_id ?? '');
                        $currentAssigneeName = $complaint->assignedStaff?->full_name ?? __('staff.assignment.unassigned');
                    @endphp
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--assignment staff-action-panel" style="border-top: 3px solid var(--gold-500);" data-assign-picker-root>
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                            </div>
                            <h2 class="tr-h2">{{ __('staff.assignment.title') }}</h2>
                        </div>

                        <div class="staff-action-panel__section">
                            <p class="staff-action-panel__hint">{{ __('staff.assignment.lede_verifier') }}</p>
                            <p class="staff-action-panel__hint">
                                <strong>{{ __('staff.assignment.current') }}:</strong>
                                <span data-assign-current>{{ $currentAssigneeName }}</span>
                            </p>

                            @if(($recommendedAssigneeId ?? null) && !$complaint->assigned_staff_id)
                                <div class="assign-reco-banner" role="status">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/></svg>
                                    <span>{{ __('staff.assignment.recommendation_hint') }}</span>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('staff.office.complaints.assign', $complaint) }}" data-assign-form>
                                @csrf
                                <div class="staff-field">
                                    <label id="assigned-staff-label">{{ __('staff.assignment.label') }}</label>
                                    <input type="hidden" name="assigned_staff_id" id="assigned-staff-value" value="{{ $selectedAssigneeId }}">

                                    <div class="assign-picker" data-assign-picker>
                                        <button type="button" class="assign-picker__trigger" data-assign-trigger aria-haspopup="listbox" aria-expanded="false" aria-labelledby="assigned-staff-label">
                                            <span class="assign-picker__trigger-main" data-assign-trigger-text>
                                                @if($selectedAssigneeId === '')
                                                    <span class="assign-picker__placeholder">{{ __('staff.assignment.choose') }}</span>
                                                @else
                                                    {{ $officeAssignees->firstWhere('id', (int) $selectedAssigneeId)?->full_name ?? __('staff.assignment.choose') }}
                                                @endif
                                            </span>
                                            <svg class="assign-picker__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>

                                        <div class="assign-picker__panel" data-assign-panel hidden>
                                            <div class="assign-picker__search-wrap">
                                                <svg class="assign-picker__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                                <input type="search" class="assign-picker__search" data-assign-search placeholder="{{ __('staff.assignment.search_placeholder') }}" autocomplete="off" aria-label="{{ __('staff.assignment.search_placeholder') }}">
                                            </div>

                                            <ul class="assign-picker__list" role="listbox" aria-labelledby="assigned-staff-label" data-assign-list>
                                                <li>
                                                    <button type="button" class="assign-option {{ $selectedAssigneeId === '' ? 'is-selected' : '' }}" role="option" aria-selected="{{ $selectedAssigneeId === '' ? 'true' : 'false' }}" data-value="" data-name="{{ __('staff.assignment.unassigned') }}" data-search="{{ \Illuminate\Support\Str::lower(__('staff.assignment.unassigned')) }}">
                                                        <span class="assign-option__avatar assign-option__avatar--none" aria-hidden="true">—</span>
                                                        <span class="assign-option__body">
                                                            <span class="assign-option__name">{{ __('staff.assignment.unassigned') }}</span>
                                                            <span class="assign-option__meta">{{ __('staff.assignment.unassigned_hint') }}</span>
                                                        </span>
                                                        <span class="assign-option__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                                                    </button>
                                                </li>
                                                @foreach($officeAssignees as $assignee)
                                                    @php
                                                        $isSelected = $selectedAssigneeId === (string) $assignee->id;
                                                        $isRecommended = ($recommendedAssigneeId ?? null) === $assignee->id;
                                                        $initials = collect(explode(' ', trim($assignee->full_name)))->filter()->take(2)->map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)))->implode('');
                                                        $openLoad = (int) ($assignee->open_load ?? 0);
                                                        $overdueLoad = (int) ($assignee->overdue_load ?? 0);
                                                        $search = \Illuminate\Support\Str::lower($assignee->full_name.' '.($assignee->isDepartmentAdmin() ? 'department administrator' : 'staff'));
                                                    @endphp
                                                    <li>
                                                        <button type="button" class="assign-option {{ $isSelected ? 'is-selected' : '' }} {{ $isRecommended ? 'is-recommended' : '' }}" role="option" aria-selected="{{ $isSelected ? 'true' : 'false' }}" data-value="{{ $assignee->id }}" data-name="{{ $assignee->full_name }}" data-search="{{ $search }}">
                                                            <span class="assign-option__avatar" aria-hidden="true">{{ $initials }}</span>
                                                            <span class="assign-option__body">
                                                                <span class="assign-option__top">
                                                                    <span class="assign-option__name">{{ $assignee->full_name }}</span>
                                                                    @if($isRecommended)
                                                                        <span class="assign-badge assign-badge--recommended"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/></svg> {{ __('staff.assignment.recommended') }}</span>
                                                                    @endif
                                                                </span>
                                                                <span class="assign-option__meta">
                                                                    <span class="assign-badge {{ $assignee->isDepartmentAdmin() ? 'assign-badge--role-da' : 'assign-badge--role-staff' }}">{{ $assignee->isDepartmentAdmin() ? __('roles.department_admin') : __('roles.staff') }}</span>
                                                                    <span class="assign-badge assign-badge--load">{{ trans_choice('staff.assignment.open_load', $openLoad, ['count' => $openLoad]) }}</span>
                                                                    @if($overdueLoad > 0)
                                                                        <span class="assign-badge assign-badge--overdue">{{ trans_choice('staff.assignment.overdue_load', $overdueLoad, ['count' => $overdueLoad]) }}</span>
                                                                    @else
                                                                        <span class="assign-badge assign-badge--ok">{{ __('staff.assignment.no_overdue') }}</span>
                                                                    @endif
                                                                </span>
                                                            </span>
                                                            <span class="assign-option__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <p class="assign-picker__empty" data-assign-empty hidden>{{ __('staff.assignment.no_matches') }}</p>
                                        </div>
                                    </div>
                                    @error('assigned_staff_id')<p class="staff-field__error">{{ $message }}</p>@enderror
                                </div>

                                <button type="submit" class="staff-action-panel__submit">{{ __('staff.assignment.save') }}</button>
                            </form>

                            <p class="staff-action-panel__hint">{{ __('staff.assignment.hint') }}</p>
                        </div>
                    </div>

                    <style>
                        [data-assign-picker] { position: relative; }
                        .assign-picker__trigger {
                            width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 10px;
                            padding: 10px 12px; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px;
                            background: var(--surface); color: var(--text-primary, var(--text)); font: inherit; cursor: pointer;
                            text-align: left; transition: border-color .15s ease, box-shadow .15s ease;
                        }
                        .assign-picker__trigger:hover { border-color: var(--navy-300, var(--dept-active-border)); }
                        .assign-picker__trigger:focus-visible { outline: none; border-color: var(--navy-400, var(--primary)); box-shadow: var(--ring, 0 0 0 3px rgba(30,58,95,.15)); }
                        .assign-picker__placeholder { color: var(--text-secondary, var(--muted)); }
                        .assign-picker__chevron { width: 16px; height: 16px; color: var(--text-secondary, var(--muted)); flex: 0 0 16px; }
                        .assign-picker__panel {
                            position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 30;
                            background: var(--surface); border: 1px solid var(--border, var(--dept-card-border)); border-radius: 12px;
                            box-shadow: 0 12px 28px rgba(15,23,42,.14); overflow: hidden;
                        }
                        .assign-picker__panel[hidden] { display: none; }
                        .assign-picker__search-wrap { position: relative; padding: 10px; border-bottom: 1px solid var(--border, var(--dept-card-border)); background: var(--surface-soft, var(--surface)); }
                        .assign-picker__search-icon { position: absolute; right: 22px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-secondary, var(--muted)); pointer-events: none; display: block; }
                        .assign-picker__search {
                            width: 100%; height: 46px; box-sizing: border-box; padding: 0 42px 0 12px; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px;
                            background: var(--surface); color: var(--text-primary, var(--text)); font: inherit; font-size: 14px; line-height: 46px;
                            outline: none; box-shadow: none; -webkit-appearance: none; appearance: none;
                            transition: border-color .15s ease, box-shadow .15s ease;
                        }
                        .assign-picker__search::placeholder { color: var(--text-secondary, var(--muted)); opacity: 1; }
                        .assign-picker__search::-webkit-search-decoration,
                        .assign-picker__search::-webkit-search-cancel-button,
                        .assign-picker__search::-webkit-search-results-button,
                        .assign-picker__search::-webkit-search-results-decoration { -webkit-appearance: none; appearance: none; }
                        .assign-picker__search:hover { border-color: var(--border-strong, var(--navy-300)); }
                        .assign-picker__search:focus, .assign-picker__search:focus-visible {
                            outline: none; border-color: var(--navy-400, var(--primary));
                            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.12);
                        }
                        .assign-picker__list { list-style: none; margin: 0; padding: 6px; max-height: 320px; overflow: auto; }
                        .assign-picker__empty { margin: 0; padding: 14px; text-align: center; color: var(--text-secondary, var(--muted)); font-size: 13px; }
                        .assign-option {
                            width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid transparent; border-radius: 10px;
                            background: transparent; color: inherit; text-align: left; cursor: pointer; font: inherit;
                        }
                        .assign-option:hover { background: var(--surface-soft, rgba(148,163,184,.08)); }
                        .assign-option.is-selected { background: var(--navy-50, rgba(30,58,95,.06)); border-color: var(--navy-200, var(--dept-active-border)); }
                        .assign-option.is-recommended { border-color: rgba(217,119,6,.25); }
                        .assign-option__avatar {
                            width: 34px; height: 34px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
                            background: var(--navy-100, rgba(30,58,95,.12)); color: var(--navy-800, var(--navy-700)); font-size: 12px; font-weight: 700; flex: 0 0 34px;
                        }
                        .assign-option__avatar--none { background: var(--surface-soft, rgba(148,163,184,.15)); color: var(--text-secondary, var(--muted)); }
                        .assign-option__body { min-width: 0; flex: 1; display: grid; gap: 4px; }
                        .assign-option__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
                        .assign-option__name { font-size: 14px; font-weight: 600; color: var(--text-primary, var(--text)); }
                        .assign-option__meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
                        .assign-option__check { width: 18px; height: 18px; color: var(--navy-700, var(--primary)); opacity: 0; flex: 0 0 18px; }
                        .assign-option.is-selected .assign-option__check { opacity: 1; }
                        .assign-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; line-height: 1.4; }
                        .assign-badge--role-da { background: var(--navy-100, rgba(30,58,95,.12)); color: var(--navy-800, var(--navy-700)); }
                        .assign-badge--role-staff { background: var(--surface-soft, rgba(148,163,184,.16)); color: var(--text-secondary, var(--muted)); }
                        .assign-badge--load { background: rgba(14,165,233,.12); color: var(--dept-icon-sky-fg, var(--blue-600)); }
                        .assign-badge--ok { background: var(--dept-icon-green-bg, rgba(22,163,74,.12)); color: var(--dept-icon-green-fg, var(--success)); }
                        .assign-badge--overdue { background: rgba(217,119,6,.14); color: var(--dept-icon-amber-fg, var(--gold-700)); }
                        .assign-badge--recommended { background: rgba(217,119,6,.16); color: var(--dept-icon-amber-fg, var(--gold-700)); }
                        .assign-reco-banner {
                            display: flex; align-items: center; gap: 8px; margin: 0 0 10px; padding: 9px 10px;
                            border: 1px solid rgba(217,119,6,.25); border-radius: 10px;
                            background: rgba(217,119,6,.08); color: var(--text-primary, var(--text)); font-size: 13px;
                        }
                    </style>

                    <script>
                    (function () {
                        var root = document.querySelector('[data-assign-picker-root]');
                        if (!root) return;
                        var picker = root.querySelector('[data-assign-picker]');
                        var trigger = root.querySelector('[data-assign-trigger]');
                        var triggerText = root.querySelector('[data-assign-trigger-text]');
                        var panel = root.querySelector('[data-assign-panel]');
                        var search = root.querySelector('[data-assign-search]');
                        var list = root.querySelector('[data-assign-list]');
                        var empty = root.querySelector('[data-assign-empty]');
                        var input = root.querySelector('#assigned-staff-value');
                        if (!picker || !trigger || !panel || !list || !input) return;

                        function open() {
                            panel.hidden = false;
                            trigger.setAttribute('aria-expanded', 'true');
                            if (search) { search.value = ''; filter(''); search.focus(); }
                        }
                        function close() {
                            panel.hidden = true;
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                        function toggle() { panel.hidden ? open() : close(); }
                        function filter(q) {
                            q = (q || '').toLowerCase().trim();
                            var visible = 0;
                            list.querySelectorAll('[data-search]').forEach(function (btn) {
                                var match = !q || (btn.getAttribute('data-search') || '').indexOf(q) !== -1;
                                btn.closest('li').style.display = match ? '' : 'none';
                                if (match) visible++;
                            });
                            if (empty) empty.hidden = visible !== 0;
                        }
                        function select(btn) {
                            var value = btn.getAttribute('data-value') || '';
                            var name = btn.getAttribute('data-name') || '';
                            input.value = value;
                            list.querySelectorAll('.assign-option').forEach(function (opt) {
                                var sel = opt === btn;
                                opt.classList.toggle('is-selected', sel);
                                opt.setAttribute('aria-selected', sel ? 'true' : 'false');
                            });
                            triggerText.textContent = name || @json(__('staff.assignment.choose'));
                            close();
                            trigger.focus();
                        }

                        trigger.addEventListener('click', toggle);
                        if (search) search.addEventListener('input', function () { filter(search.value); });
                        list.addEventListener('click', function (e) {
                            var btn = e.target.closest('.assign-option');
                            if (btn) select(btn);
                        });
                        document.addEventListener('click', function (e) {
                            if (!panel.hidden && !picker.contains(e.target)) close();
                        });
                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape' && !panel.hidden) { close(); trigger.focus(); }
                        });
                    })();
                    </script>
                @endif

                {{-- 2. Public presentation — moderation, not publication. The
                     resident's visibility choice is theirs alone; the Mayor's
                     office withholds or redacts the public copy. Every other
                     department settles and verifies the work and does not
                     moderate public presentation. --}}
                @if($user->canVerifyComplaints() && \App\Services\ComplaintPrivacyService::canApprove($user))
                    <x-moderation-panel
                        class="moderation-panel--light staff-detail-card staff-detail-card--moderation"
                        :complaint="$complaint"
                        :action="route('staff.complaints.moderate', $complaint)"
                        id-prefix="staff"
                    />
                @endif

                {{-- 2b. Reports that name somebody: the name stays in the
                     office and with the Mayor's office, and publication is the
                     Mayor's office to decide. Resolving a verified report sends
                     it there automatically; the panel is status only. --}}
                @if($complaint->isPrivacyGated())
                    <x-publication-panel
                        class="moderation-panel--light staff-detail-card staff-detail-card--publication"
                        :complaint="$complaint"
                        :can-approve="\App\Services\ComplaintPrivacyService::canApprove($user)"
                    />
                @endif

                {{-- 2c. The work is paused — waiting on funds, materials, a
                     budget decision, another body. The reason is recorded, the
                     resident is told, and the deadline pauses with it. The
                     panel itself decides what a finished report may still do:
                     a hold already in place can be taken off, it can never
                     be taken again. --}}
                @if($complaint->isVerified())
                    <x-hold-panel
                        class="staff-detail-card staff-detail-card--hold"
                        :complaint="$complaint"
                        :can-manage="$user->role === 'admin' || $complaint->department_id === $user->department_id"
                    />
                @endif

                {{-- 3. The work that is left is the resident's to close.
                     A resolved report is waiting for the resident, not for
                     another office status — closing is their decision, or the
                     system's after 7 days. The form is not offered until
                     there is one. --}}
                @if($complaint->isVerified() && $complaint->statusEnum === \App\Enums\ComplaintStatus::Resolved)
                    <div class="tr-card workspace-card staff-detail-card staff-detail-card--closure" style="border-top: 3px solid var(--status-resolved-dot);">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon" style="background: color-mix(in srgb, var(--status-resolved-dot) 12%, transparent); color: var(--status-resolved-dot);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/><path d="M4 12l5 5L16 9"/></svg>
                            </div>
                            <h2 class="tr-h2">{{ __('staff.closure.waiting_title') }}</h2>
                        </div>
                        <p style="margin: 0; font-size: 13.5px; line-height: 1.6; color: var(--navy-900);">{{ __('staff.closure.waiting_body') }}</p>
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>

<style>
    .workspace-card {
        background: var(--surface);
        border: 1px solid var(--line-soft);
        box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,0.06));
        padding: 20px;
        border-radius: 10px;
    }
    .workspace-card__header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--line-soft);
    }
    .workspace-card__header h2.tr-h2 {
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.01em;
        line-height: 1.25;
        color: var(--text);
        margin: 0;
    }
    .workspace-card__icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: rgba(16,41,75,0.08);
        color: var(--navy-800);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .map-container {
        height: 280px;
        width: 100%;
        isolation: isolate;
        z-index: 0;
    }

    .map-unavailable {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        margin: 0;
        padding: 24px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.5;
        text-align: center;
    }

    /* ═══════════════════════════════════════════════════════════
       THE BANNER IS NAVY. The chips that read on a white card read
       as dark-on-dark here, so they get an on-navy treatment:
       the ticket id takes the gold accent, the priority pill takes
       its family hue as a tint with white text.
       ═══════════════════════════════════════════════════════════ */
    .staff-complaint-detail .tr-banner .ticket-id {
        color: var(--gold-300);
    }
    .staff-complaint-detail .tr-banner .status-pill--neutral {
        background: color-mix(in srgb, var(--surface) 18%, transparent);
        color: var(--surface);
    }
    .staff-complaint-detail .tr-banner .status-pill--info {
        background: color-mix(in srgb, var(--status-submitted-dot) 32%, transparent);
        color: var(--surface);
    }
    .staff-complaint-detail .tr-banner .status-pill--warning {
        background: color-mix(in srgb, var(--status-progress-dot) 32%, transparent);
        color: var(--surface);
    }
    .staff-complaint-detail .tr-banner .status-pill--danger {
        background: color-mix(in srgb, var(--status-rejected-dot) 34%, transparent);
        color: var(--surface);
    }

    /* ═══════════════════════════════════════════════════════════
       COMPLAINT DETAIL — two balanced columns under the full-width
       header. Each column is its own flex stack, so the columns start
       at the same top edge and a short card never opens a hole in the
       other column (a shared grid row would).
       ═══════════════════════════════════════════════════════════ */
    .staff-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.75fr);
        gap: 24px;
        align-items: start;
        width: 100%;
    }
    /* 1024–1279px: the staff sidebar is still docked, so the workspace is
       narrow. The sidebar column keeps a usable minimum and the complaint
       column takes the rest — the cards are never squeezed below ~260px. */
    @media (min-width: 1024px) and (max-width: 1279px) {
        .staff-detail-grid {
            grid-template-columns: minmax(0, 1fr) minmax(260px, 0.6fr);
            gap: 20px;
        }
    }
    .staff-detail-col {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0;
    }
    .staff-detail-card,
    .staff-detail-col > * {
        min-width: 0;
        max-width: none;
        box-sizing: border-box;
    }

    /* Tablet and below: one column. The column wrappers dissolve so every
       card is a grid item, every card takes the full content width, and the
       cards keep the phone reading order — who reported it and where, then
       the complaint, then the actions. */
    @media (max-width: 1023px) {
        .staff-detail-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 16px;
        }
        .staff-detail-col { display: contents; }
        .staff-detail-grid > *,
        .staff-detail-col > * {
            width: 100%;
            min-width: 0;
            max-width: none;
            grid-column: 1 / -1;
            justify-self: stretch;
            box-sizing: border-box;
        }
        .staff-detail-card--resident { order: 1; }
        .staff-detail-card--location { order: 2; }
        .staff-detail-card--description { order: 3; }
        .staff-detail-card--timeline { order: 4; }
        .staff-detail-card--response { order: 5; }
        .staff-detail-card--evidence { order: 6; }
        .staff-detail-card--review { order: 7; }
        .staff-detail-card--marking { order: 8; }
        .staff-detail-card--followup { order: 9; }
        .staff-detail-card--assignment { order: 10; }
        .staff-detail-card--hold { order: 11; }
        .staff-detail-card--moderation,
        .staff-detail-card--publication,
        .staff-detail-card--closure { order: 12; }
    }

    @media (max-width: 599px) {
        .workspace-card {
            padding: 16px;
        }
        .review-status-subgrid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .review-locked-summary {
            grid-template-columns: 1fr !important;
        }
    }

    .review-status-subgrid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
    .review-locked-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .review-locked-summary__note {
        grid-column: 1 / -1;
    }

    /* Collapsible review/verification body — tokens only, no new colours. */
    .review-panel-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 12px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--navy-700);
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
    }
    .review-panel-toggle:hover { background: var(--navy-50); }
    .review-panel-toggle:focus-visible { outline: none; box-shadow: var(--ring-inset); }
    .review-toggle__icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .review-toggle__icon { transition: none; }
    }
    .review-panel-toggle[aria-expanded="false"] .review-toggle__icon { transform: rotate(180deg); }
</style>
@endsection

@push('scripts')
<script>
    (() => {
        const mapElement = document.getElementById('complaint-map');
        if (!mapElement) return;

        const buildComplaintMap = () => {
            if (mapElement.dataset.mapInitialized === 'true') return;
            mapElement.dataset.mapInitialized = 'true';

            const latitude = Number.parseFloat(mapElement.dataset.latitude);
            const longitude = Number.parseFloat(mapElement.dataset.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

            const map = window.L.map(mapElement, { scrollWheelZoom: false }).setView([latitude, longitude], 16);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            window.L.marker([latitude, longitude]).addTo(map);

            // Leaflet measures once, when the map is built. A phone's URL bar
            // collapses and a webfont reflows the card after that, so the map
            // re-measures without animating (a resize is not a user gesture).
            const remeasure = () => map.invalidateSize({ animate: false });
            remeasure();
            window.addEventListener('resize', remeasure);
            window.addEventListener('orientationchange', remeasure);

            if ('IntersectionObserver' in window) {
                new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) remeasure();
                    });
                }, { root: null, threshold: 0.1 }).observe(mapElement);
            }
        };

        if (window.whenLeafletReady) {
            window.whenLeafletReady(buildComplaintMap, {
                // The pin is still reachable through the directions action,
                // so a missing map must say that rather than leave an empty box.
                onUnavailable: () => {
                    mapElement.dataset.mapInitialized = 'false';
                    const message = document.createElement('p');
                    message.className = 'map-unavailable';
                    message.textContent = mapElement.dataset.mapUnavailable || '';
                    mapElement.replaceChildren(message);
                },
            });
        } else {
            document.addEventListener('DOMContentLoaded', buildComplaintMap);
        }

        // Collapsible review/verification body. State lives in localStorage so
        // the staff member's choice survives reloads and Livewire re-renders.
        // Visibility uses [hidden] (never style.display), per §7 of the design
        // system; listeners are bound once per panel.
        (() => {
            const PREFIX = 'daet.review-panel.';
            const init = () => {
                document.querySelectorAll('[data-review-panel]').forEach((panel) => {
                    if (panel.dataset.reviewInit === 'true') return;
                    const toggle = panel.querySelector('[data-review-toggle]');
                    const body = panel.querySelector('[data-review-body]');
                    const ticket = panel.dataset.reviewTicket;
                    if (!toggle || !body || !ticket) return;

                    const key = PREFIX + ticket;
                    const text = toggle.querySelector('.review-toggle__text');

                    const setExpanded = (expanded) => {
                        body.toggleAttribute('hidden', !expanded);
                        toggle.setAttribute('aria-expanded', String(expanded));
                        text.textContent = expanded
                            ? '{{ __('staff.verification.minimize') }}'
                            : '{{ __('staff.verification.show') }}';
                        toggle.setAttribute('aria-label', expanded
                            ? '{{ __('staff.verification.minimize_label') }}'
                            : '{{ __('staff.verification.show_label') }}');
                        try { localStorage.setItem(key, String(expanded)); } catch (e) {}
                    };

                    try {
                        const saved = localStorage.getItem(key);
                        if (saved !== null) setExpanded(saved === 'true');
                    } catch (e) {}

                    toggle.addEventListener('click', () => {
                        setExpanded(toggle.getAttribute('aria-expanded') === 'false');
                    });

                    panel.dataset.reviewInit = 'true';
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            // Livewire-safe: re-apply to any newly rendered panels after a
            // client-side navigation / partial refresh so the toggle keeps
            // working without re-binding existing ones.
            if (window.Livewire) {
                window.Livewire.hook('message.processed', () => init());
            }
        })();
    })();
</script>
@endpush
