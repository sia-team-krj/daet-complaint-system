@extends('staff.layouts.app')
@section('title', $complaint->ticket_id . ' - Complaint Investigation Workspace')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page staff-complaint-detail" style="background: var(--bg); min-height: calc(100svh - var(--navbar-height)); padding-bottom: 60px;">
    
    {{-- ════════════ BANNER (Staff Dashboard Header Style) ════════════ --}}
    <header class="tr-banner" style="background: linear-gradient(135deg, var(--navy-800) 0%, var(--navy-700) 100%); color: var(--surface); padding: 28px 0 24px; border-bottom: 1px solid rgba(201,168,76,0.2);">
        <div class="tr-container" style="max-width: none; padding: 0 16px;">
            <div class="banner-top-row" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="pt-ticket-pill" style="background: rgba(201,168,76,0.18); color: var(--gold-300); border: 1px solid rgba(201,168,76,0.4); padding: 3px 8px; border-radius: 4px; font-family: var(--font-mono, monospace); font-weight: 700; font-size: 12px;">
                        {{ $complaint->ticket_id }}
                    </span>
                    <span class="pt-chip-neutral" style="background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                        {{ $complaint->category }}
                    </span>
                </div>
                @php
                    $statusEnum = $complaint->statusEnum;
                    $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '_');
                    $ageNow = now();
                    $ageSeconds = max(0, (int) $complaint->created_at->diffInSeconds($ageNow));
                    $ageDays = (int) floor($ageSeconds / 86400);
                    $ageLabel = $ageDays >= 1
                        ? $ageDays.' day'.($ageDays === 1 ? '' : 's')
                        : ($ageSeconds < 3600
                            ? max(1, (int) floor($ageSeconds / 60)).' minute'.(floor($ageSeconds / 60) === 1.0 ? '' : 's')
                            : (int) floor($ageSeconds / 3600).' hour'.(floor($ageSeconds / 3600) === 1.0 ? '' : 's'));
                @endphp
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
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
                        <span class="tr-badge tr-badge--{{ $priorityTone }}" style="font-size: 11px; padding: 3px 8px; font-weight: 700; text-transform: uppercase;">
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

            <h1 class="tr-h1" style="font-size: clamp(22px, 3vw, 32px); font-weight: 700; color: var(--surface); margin-bottom: 12px; line-height: 1.25;">
                {{ $complaint->title }}
            </h1>

            {{-- Responsive Compact Metadata Grid for Mobile --}}
            <div class="banner-metadata-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px 16px; font-size: 12px; color: rgba(255,255,255,0.78); border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                <div>Filed: <strong style="color: var(--surface);">{{ $complaint->created_at->format('M d, Y') }}</strong></div>
                <div>Dept: <strong style="color: var(--surface);">{{ $complaint->department?->name ?? 'Unassigned' }}</strong></div>
                <div>Work: <strong style="color: var(--surface);">{{ $workMode->label() }}</strong></div>
                <div>Age: <strong style="color: var(--gold-300);">{{ $ageLabel }} open</strong></div>
            </div>
        </div>
    </header>

    {{-- ════════════ MAIN CONTAINER ════════════ --}}
    <div class="tr-container" style="max-width: none; padding: 0 16px; margin-top: 20px;">
        
        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="tr-card" style="background: rgba(63,203,111,0.12); border-color: rgba(63,203,111,0.3); color: var(--success); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-weight: 600; font-size: 13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="tr-card" style="background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.3); color: var(--danger); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-weight: 600; font-size: 13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($complaint->isModerationFlagged() || $complaint->hidden_from_public)
            <section class="tr-card" aria-labelledby="complaint-flag-notice-title" style="margin-bottom: 16px; padding: 16px 18px; border: 1px solid color-mix(in srgb, var(--status-progress-dot) 35%, var(--line-soft)); background: var(--status-progress-bg); color: var(--status-progress-text);">
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

        {{-- Breadcrumb Back Link --}}
        <nav class="pt-back-row" style="background: transparent; padding: 0 0 16px;">
            <a href="{{ route('staff.dashboard') }}" class="pt-back" style="color: var(--navy-800); font-weight: 600; font-size: 13px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Back to Staff Dashboard</span>
            </a>
        </nav>

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
            
            {{-- MAIN COLUMN: Description, Location, Evidence, Review Form --}}
            <div class="staff-detail-col-main" style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
                
                {{-- 1. Resident Information Card --}}
                <div class="tr-card workspace-card">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <h2>Resident information</h2>
                    </div>
                    @php $residentAlias = $complaint->user?->staff_safe_name ?? 'Resident unavailable'; @endphp
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--navy-800); color: var(--gold-300); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0;">
                            {{ strtoupper(substr($residentAlias, 0, 1)) }}
                        </div>
                        <div>
                            <strong style="display: block; font-size: 14px; color: var(--navy-900);">{{ $residentAlias }}</strong>
                            <span style="font-size: 11px; color: var(--faint);">Identity protected · Staff view</span>
                        </div>
                    </div>
                    <p style="font-size: 11px; color: var(--faint); line-height: 1.45; margin: 0; border-top: 1px solid var(--line-soft); padding-top: 8px;">
                        Strict privacy controls apply. Personal contact details remain confidential.
                    </p>
                </div>

                {{-- 1. Complaint Description Card --}}
                <div class="tr-card workspace-card">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h2>Complaint description</h2>
                    </div>
                    <p style="font-size: 14px; line-height: 1.75; color: var(--navy-900); margin: 0; white-space: pre-line;">{{ $complaint->description }}</p>
                </div>

                {{-- 2. Complaint Location & Map Card --}}
                @php
                    $hasExactPin = $complaint->latitude !== null && $complaint->longitude !== null;
                    $locationBarangay = filled($complaint->barangay) ? $complaint->barangay : $complaint->user?->barangay;
                    $directionsDestination = $hasExactPin
                        ? number_format((float) $complaint->latitude, 6, '.', '').','.number_format((float) $complaint->longitude, 6, '.', '')
                        : null;
                @endphp
                <div class="tr-card workspace-card location-card" style="overflow: hidden; padding: 0;">
                    <div class="workspace-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--line-soft);">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <h2>{{ __('staff.location.title') }}</h2>
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

                {{-- 3. Activity History Timeline Card --}}
                <div class="tr-card workspace-card">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h2>Activity history timeline</h2>
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
                    <div class="tr-card workspace-card">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <h2>Resident evidence photos ({{ count($complaint->evidence_images) }})</h2>
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

                {{-- 4. Review and Verification Form Card --}}
                <div class="tr-card workspace-card" style="border-top: 3px solid var(--gold-300);" data-review-panel data-review-ticket="{{ $complaint->ticket_id }}">
                    <div class="workspace-card__header" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"/></svg>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <h2 style="margin:0;">Review and verification form</h2>
                            @if($complaint->isVerified() && $user->canVerifyComplaints())
                                <button type="button" class="review-panel-toggle" data-review-toggle
                                        aria-expanded="false"
                                        aria-label="{{ __('staff.verification.minimize_label') }}">
                                    <svg class="review-toggle__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 12 18 9"/></svg>
                                    <span class="review-toggle__text">{{ __('staff.verification.minimize') }}</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Read-only record stays visible after verification. --}}
                    <div class="review-status-subgrid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px;">
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
                </div>

                {{-- 5. Verification follow-up — the two actions the November
                     rewrite dropped: ask the resident for better evidence, and
                     hand verification to another verifier. The server has
                     always supported both; this is the surface that lets a
                     staff member reach them. --}}
                @if($user->canVerifyComplaints() && $verificationOpen)
                    <div class="tr-card workspace-card staff-action-panel" style="border-top: 3px solid var(--status-review-dot);">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"/></svg>
                            </div>
                            <h2>{{ __('staff.verification.follow_up') }}</h2>
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

            </div>

            {{-- SIDE COLUMN: Resident Info, Official Response, Activity Timeline --}}
            <div class="staff-detail-col-side" style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">

                {{-- Reports that name somebody: the ordered gate, shown before
                     the forms that do the work. --}}
                @if($complaint->isPrivacyGated())
                    <x-publication-checklist :complaint="$complaint" />
                @endif

                {{-- 2. Public presentation — moderation, not publication. The
                     resident's visibility choice is theirs alone; the office can
                     withhold or redact the public copy. Shown only to verifiers
                     so field staff handling on-site work do not alter public
                     presentation. --}}
                @if($user->canVerifyComplaints())
                    <x-moderation-panel
                        class="moderation-panel--light"
                        :complaint="$complaint"
                        :action="route('staff.complaints.moderate', $complaint)"
                        :quick-redact-action="route('staff.complaints.redact', $complaint)"
                        id-prefix="staff"
                    />
                @endif

                {{-- 2b. Reports that name somebody: the name stays in the
                     office, and publication is the Mayor's office to decide. --}}
                @if($complaint->isPrivacyGated())
                    <x-publication-panel
                        class="moderation-panel--light"
                        :complaint="$complaint"
                        :can-request="$user->role === 'admin' || $complaint->department_id === $user->department_id"
                        :can-approve="\App\Services\ComplaintPrivacyService::canApprove($user)"
                    />
                @endif

                {{-- 2c. The work is paused — waiting on funds, materials, a
                     budget decision, another body. The reason is recorded, the
                     resident is told, and the deadline pauses with it. --}}
                @if($complaint->isVerified() && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Rejected && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Closed)
                    <x-hold-panel
                        :complaint="$complaint"
                        :can-manage="$user->role === 'admin' || $complaint->department_id === $user->department_id"
                    />
                @endif

                {{-- 3. Official Response Form Card (when verified) --}}
                @if($complaint->isVerified() && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Rejected && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Closed)
                    <div class="tr-card workspace-card" style="border-top: 3px solid var(--success);">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon" style="background: rgba(63,203,111,0.12); color: var(--success);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <h2>Add official response</h2>
                        </div>
                        <form method="POST" action="{{ route('staff.complaints.update', $complaint) }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                            @csrf
                            @method('PUT')
                            @php
                                // Only the statuses this complaint can legally
                                // move to. The server refuses every other
                                // transition, so offering them here only gives
                                // the staff member a dead end.
                                $availableStatuses = $complaint->statusEnum->allowedTransitions();
                                // The browser selects the first option that is not
                                // marked selected, and this dropdown never marks
                                // one — so this is the status the form opens on.
                                $initialStatus = collect($availableStatuses)->first()?->value;
                            @endphp
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
    .workspace-card__header h2 {
        font-size: 15px;
        font-weight: 700;
        color: var(--navy-900);
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

    .staff-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 400px;
        gap: 20px;
        align-items: start;
    }

    /* Intelligent Responsive Grid Optimizations across Breakpoints */
    @media (max-width: 1023px) {
        .staff-detail-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 599px) {
        .workspace-card {
            padding: 16px;
        }
        .banner-metadata-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .review-status-subgrid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (min-width: 600px) and (max-width: 1023px) {
        .staff-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .staff-detail-col-main {
            grid-column: span 1;
        }
        .staff-detail-col-side {
            grid-column: span 1;
        }
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
