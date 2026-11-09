@extends('staff.layouts.app')
@section('title', $complaint->ticket_id . ' - Complaint Investigation Workspace')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page staff-complaint-detail" style="background: var(--bg, #F3F6FB); min-height: calc(100svh - var(--navbar-height)); padding-bottom: 60px;">
    
    {{-- ════════════ BANNER (Staff Dashboard Header Style) ════════════ --}}
    <header class="tr-banner" style="background: linear-gradient(135deg, #10294B 0%, #1B3A61 100%); color: #ffffff; padding: 28px 0 24px; border-bottom: 1px solid rgba(201,168,76,0.2);">
        <div class="tr-container" style="max-width: none; padding: 0 16px;">
            <div class="banner-top-row" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="pt-ticket-pill" style="background: rgba(201,168,76,0.18); color: #E9B943; border: 1px solid rgba(201,168,76,0.4); padding: 3px 8px; border-radius: 4px; font-family: var(--font-mono, monospace); font-weight: 700; font-size: 12px;">
                        {{ $complaint->ticket_id }}
                    </span>
                    <span class="pt-chip-neutral" style="background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                        {{ $complaint->category }}
                    </span>
                </div>
                @php
                    $statusEnum = $complaint->statusEnum;
                    $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '-');
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
                    <span class="pt-pill pt-pill--{{ $statusKey }}" style="font-size: 11px; padding: 3px 10px;">
                        <span class="pt-pill__dot" aria-hidden="true"></span>
                        {{ $statusEnum->label() }}
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
                </div>
            </div>

            <h1 class="tr-h1" style="font-size: clamp(22px, 3vw, 32px); font-weight: 700; color: #ffffff; margin-bottom: 12px; line-height: 1.25;">
                {{ $complaint->title }}
            </h1>

            {{-- Responsive Compact Metadata Grid for Mobile --}}
            <div class="banner-metadata-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px 16px; font-size: 12px; color: rgba(255,255,255,0.78); border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                <div>Filed: <strong style="color: #fff;">{{ $complaint->created_at->format('M d, Y') }}</strong></div>
                <div>Dept: <strong style="color: #fff;">{{ $complaint->department?->name ?? 'Unassigned' }}</strong></div>
                <div>Age: <strong style="color: #E9B943;">{{ $ageLabel }} open</strong></div>
            </div>
        </div>
    </header>

    {{-- ════════════ MAIN CONTAINER ════════════ --}}
    <div class="tr-container" style="max-width: none; padding: 0 16px; margin-top: 20px;">
        
        {{-- Flash Messages --}}
        @if(session('status'))
            <div class="tr-card" style="background: rgba(63,203,111,0.12); border-color: rgba(63,203,111,0.3); color: #15803D; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-weight: 600; font-size: 13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="tr-card" style="background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.3); color: #B91C1C; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; padding: 12px 16px; font-weight: 600; font-size: 13px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Breadcrumb Back Link --}}
        <nav class="pt-back-row" style="background: transparent; padding: 0 0 16px;">
            <a href="{{ route('staff.dashboard') }}" class="pt-back" style="color: #10294B; font-weight: 600; font-size: 13px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Back to Staff Dashboard</span>
            </a>
        </nav>

        {{-- Optimized Responsive Grid (Adapts intelligently across breakpoints) --}}
        <div class="staff-detail-grid">
            
            {{-- MAIN COLUMN: Description, Location, Evidence, Review Form --}}
            <div class="staff-detail-col-main" style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
                
                {{-- 1. Complaint Description Card --}}
                <div class="tr-card workspace-card">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h2>Complaint description</h2>
                    </div>
                    <p style="font-size: 14px; line-height: 1.75; color: #142D50; margin: 0; white-space: pre-line;">{{ $complaint->description }}</p>
                </div>

                {{-- 2. Complaint Location & Map Card --}}
                <div class="tr-card workspace-card" style="overflow: hidden; padding: 0;">
                    <div class="workspace-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--line-soft, #DCE5F1);">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <h2>Complaint location</h2>
                    </div>
                    @if($complaint->latitude !== null && $complaint->longitude !== null)
                        <div id="complaint-map" style="height: 260px; width: 100%; isolation: isolate; z-index: 0;"
                             data-latitude="{{ (float) $complaint->latitude }}"
                             data-longitude="{{ (float) $complaint->longitude }}"></div>
                        <div style="padding: 12px 20px; background: #F8FAFC; border-top: 1px solid var(--line-soft, #DCE5F1); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; font-size: 12px; color: #64748B; gap: 8px;">
                            <span>GPS: <strong style="font-family: var(--font-mono, monospace); color: #142D50;">{{ number_format((float) $complaint->latitude, 6) }}, {{ number_format((float) $complaint->longitude, 6) }}</strong></span>
                            @if($complaint->user?->barangay)
                                <span>Barangay: <strong style="color: #142D50;">{{ $complaint->user->barangay }}</strong></span>
                            @endif
                        </div>
                    @else
                        <div style="height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748B; gap: 6px; background: #F8FAFC; font-size: 13px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>No exact location coordinates provided</span>
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
                                <a href="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" target="_blank" rel="noopener" style="display: block; border-radius: 6px; overflow: hidden; border: 1px solid var(--line-soft, #DCE5F1); background: #F8FAFC; aspect-ratio: 1;">
                                    <img src="{{ route('complaints.evidence', [$complaint, $imageIndex]) }}" alt="Evidence {{ $imageIndex + 1 }}" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 4. Review and Verification Form Card --}}
                <div class="tr-card workspace-card" style="border-top: 3px solid #E9B943;">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3Z"/></svg>
                        </div>
                        <h2>Review and verification form</h2>
                    </div>

                    <div class="review-status-subgrid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px;">
                        <div style="padding: 10px; background: #F8FAFC; border-radius: 6px; border: 1px solid var(--line-soft, #DCE5F1);">
                            <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 2px;">Review status</span>
                            <strong style="font-size: 12px; color: #142D50;">{{ $complaint->reviewStatusEnum->label() }}</strong>
                        </div>
                        <div style="padding: 10px; background: #F8FAFC; border-radius: 6px; border: 1px solid var(--line-soft, #DCE5F1);">
                            <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 2px;">Suggested priority</span>
                            <strong style="font-size: 12px; color: #142D50;">{{ $complaint->suggestedPriorityEnum->label() }}</strong>
                        </div>
                        <div style="padding: 10px; background: #F8FAFC; border-radius: 6px; border: 1px solid var(--line-soft, #DCE5F1);">
                            <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 2px;">Confirmed priority</span>
                            <strong style="font-size: 12px; color: #10294B;">{{ $complaint->confirmedPriorityEnum?->label() ?? 'Not confirmed' }}</strong>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('staff.complaints.review', $complaint) }}" style="display: flex; flex-direction: column; gap: 16px;">
                        @csrf
                        @method('PUT')
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 6px;" for="review_status">Review decision</label>
                            <select name="review_status" id="review_status" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line, #DCE5F1); border-radius: 6px; background: var(--surface, #FFFFFF); color: #142D50; font-size: 13px;" required>
                                @foreach($reviewStatusOptions as $reviewOption)
                                    <option value="{{ $reviewOption->value }}" @selected($complaint->review_status === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 6px;" for="confirmed_priority">Confirmed priority</label>
                            <select name="confirmed_priority" id="confirmed_priority" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line, #DCE5F1); border-radius: 6px; background: var(--surface, #FFFFFF); color: #142D50; font-size: 13px;">
                                <option value="">Not confirmed</option>
                                @foreach($priorityOptions as $priorityOption)
                                    <option value="{{ $priorityOption->value }}" @selected($complaint->confirmed_priority === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 6px;" for="verification_method">Verification method</label>
                            <select name="verification_method" id="verification_method" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line, #DCE5F1); border-radius: 6px; background: var(--surface, #FFFFFF); color: #142D50; font-size: 13px;">
                                <option value="">Not recorded</option>
                                @foreach(\App\Enums\VerificationMethod::cases() as $methodOption)
                                    <option value="{{ $methodOption->value }}" @selected($complaint->verification_method === $methodOption->value)>{{ $methodOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 6px;" for="review_notes">Review notes</label>
                            <textarea name="review_notes" id="review_notes" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid var(--line, #DCE5F1); border-radius: 6px; background: var(--surface, #FFFFFF); color: #142D50; font-size: 13px;" placeholder="Record investigation findings...">{{ old('review_notes', $complaint->review_notes) }}</textarea>
                        </div>
                        <button type="submit" class="tr-button" style="width: 100%; justify-content: center; min-height: 44px; background: #10294B; color: #FFFFFF; font-weight: 700; border-radius: 6px; font-size: 13px;">
                            Save review decision
                        </button>
                    </form>
                </div>

            </div>

            {{-- SIDE COLUMN: Resident Info, Official Response, Activity Timeline --}}
            <div class="staff-detail-col-side" style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
                
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
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #10294B; color: #E9B943; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0;">
                            {{ strtoupper(substr($residentAlias, 0, 1)) }}
                        </div>
                        <div>
                            <strong style="display: block; font-size: 14px; color: #142D50;">{{ $residentAlias }}</strong>
                            <span style="font-size: 11px; color: #64748B;">Identity protected · Staff view</span>
                        </div>
                    </div>
                    <p style="font-size: 11px; color: #64748B; line-height: 1.45; margin: 0; border-top: 1px solid var(--line-soft, #DCE5F1); padding-top: 8px;">
                        Strict privacy controls apply. Personal contact details remain confidential.
                    </p>
                </div>

                {{-- 2. Official Response Form Card (when verified) --}}
                @if($complaint->isVerified() && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Rejected && $complaint->statusEnum !== \App\Enums\ComplaintStatus::Closed)
                    <div class="tr-card workspace-card" style="border-top: 3px solid #3FCB6F;">
                        <div class="workspace-card__header">
                            <div class="workspace-card__icon" style="background: rgba(63,203,111,0.12); color: #15803D;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <h2>Add official response</h2>
                        </div>
                        <form method="POST" action="{{ route('staff.complaints.update', $complaint) }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                            @csrf
                            @method('PUT')
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 6px;">Status update</label>
                                <select name="status" class="tr-select" style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line, #DCE5F1); border-radius: 6px; background: var(--surface, #FFFFFF); color: #142D50; font-size: 13px;" required>
                                    @foreach($statuses as $statusOption)
                                        <option value="{{ $statusOption->value }}" {{ $complaint->statusEnum->value === $statusOption->value ? 'selected' : '' }}>{{ $statusOption->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; margin-bottom: 6px;">Public response note</label>
                                <textarea name="note" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid var(--line, #DCE5F1); border-radius: 6px; background: var(--surface, #FFFFFF); color: #142D50; font-size: 13px;" required minlength="10" placeholder="Write update visible to resident...">{{ old('note') }}</textarea>
                            </div>
                            <button type="submit" class="tr-button" style="width: 100%; justify-content: center; min-height: 44px; background: #15803D; color: #FFFFFF; font-weight: 700; border-radius: 6px; font-size: 13px;">
                                Send response & update status
                            </button>
                        </form>
                    </div>
                @endif

                {{-- 3. Activity History Timeline Card --}}
                <div class="tr-card workspace-card">
                    <div class="workspace-card__header">
                        <div class="workspace-card__icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h2>Activity history timeline</h2>
                    </div>

                    @if($complaint->logs->isEmpty())
                        <p style="color: #64748B; font-size: 13px; margin: 0; text-align: center; padding: 12px 0;">No activity logs recorded yet.</p>
                    @else
                        <div style="position: relative; padding-left: 18px; display: flex; flex-direction: column; gap: 16px;">
                            <div style="position: absolute; left: 4px; top: 6px; bottom: 6px; width: 2px; background: #E2E8F0;"></div>
                            @foreach($complaint->logs->sortByDesc('created_at') as $log)
                                <div style="position: relative; font-size: 12px;">
                                    <div style="position: absolute; left: -18px; top: 3px; width: 8px; height: 8px; border-radius: 50%; background: {{ $log->is_internal ? '#D97706' : '#10294B' }}; border: 2px solid #FFFFFF;"></div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 3px;">
                                        <strong style="color: #142D50;">{{ $log->actor->full_name ?? 'System' }}</strong>
                                        <span style="font-size: 10px; color: #64748B;">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($log->previous_status && $log->new_status)
                                        <div style="display: inline-block; padding: 2px 6px; background: #F1F5F9; border-radius: 10px; font-size: 10px; font-weight: 600; color: #10294B; margin-bottom: 4px;">
                                            {{ $log->previous_status }} &rarr; {{ $log->new_status }}
                                        </div>
                                    @endif
                                    @if($log->comment)
                                        <p style="margin: 3px 0; color: #334155; line-height: 1.45;">{{ $log->comment }}</p>
                                    @endif
                                    <span style="font-size: 10px; color: {{ $log->is_internal ? '#D97706' : '#15803D' }}; font-weight: 600;">
                                        {{ $log->is_internal ? 'Internal Staff Note' : 'Public Update' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
</div>

<style>
    .workspace-card {
        background: var(--surface, #FFFFFF);
        border: 1px solid var(--line-soft, #DCE5F1);
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
        border-bottom: 1px solid var(--line-soft, #DCE5F1);
    }
    .workspace-card__header h2 {
        font-size: 15px;
        font-weight: 700;
        color: #142D50;
        margin: 0;
    }
    .workspace-card__icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: rgba(16,41,75,0.08);
        color: #10294B;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
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
</style>
@endsection

@push('scripts')
<script>
    (() => {
        const buildComplaintMap = () => {
            const mapElement = document.getElementById('complaint-map');
            if (!mapElement) return;
            if (mapElement.dataset.mapInitialized === 'true') return;
            mapElement.dataset.mapInitialized = 'true';

            const latitude = Number.parseFloat(mapElement.dataset.latitude);
            const longitude = Number.parseFloat(mapElement.dataset.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

            const map = window.L.map(mapElement, { scrollWheelZoom: false }).setView([latitude, longitude], 16);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            window.L.marker([latitude, longitude]).addTo(map);

            setTimeout(() => map.invalidateSize(), 250);
        };
        window.whenLeafletReady ? window.whenLeafletReady(buildComplaintMap) : document.addEventListener('DOMContentLoaded', buildComplaintMap);
    })();
</script>
@endpush
