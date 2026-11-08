@props([
    'report' => [],
    'locale' => 'en',
])

@php
    $statusKey = str_replace(' ', '_', strtolower((string)($report['status_key'] ?? $report['status'] ?? '')));
    $statusLabel = $report['status_label'] ?? __('transparency.status.unknown');
    $department = $report['department'] ?? __('transparency.register.table.department_pending');
    $location = $report['location'] ?? '';
    $reported = $report['date'] ?? '';
    $ticketId = $report['ticket_id'] ?? '';
    $title = $report['title'] ?? '';
    $url = $report['url'] ?? '#';

    $statusVariants = [
        'submitted' => 'submitted',
        'under_review' => 'verified',
        'in_progress' => 'in_progress',
        'resolved' => 'resolved',
        'rejected' => 'rejected',
        'closed' => 'closed',
    ];
    $pillVariant = $statusVariants[$statusKey] ?? 'default';
@endphp

<li style="margin-bottom: 12px;">
    <a href="{{ $url }}" wire:navigate
       style="display: block; border: 1px solid #cdd5e1; border-radius: 12px; background: #ffffff; padding: 16px; text-decoration: none; color: inherit; transition: border-color 0.18s ease, box-shadow 0.18s ease;"
       onmouseover="this.style.borderColor='#94a3b8'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
       onmouseout="this.style.borderColor='#cdd5e1'; this.style.boxShadow=''"
       aria-label="{{ __('transparency.register.open_ticket', ['ticket' => $ticketId]) }}">
        <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                <span style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; font-weight: 700; color: #0b1f3a; line-height: 1;">
                    {{ $ticketId }}
                </span>
                <span class="status-pill" style="{{ $pillVariant === 'submitted' ? 'background:#dbeafe;color:#1d4ed8;' : ($pillVariant === 'verified' ? 'background:#dcfce7;color:#15803d;' : ($pillVariant === 'assigned' ? 'background:#eff6ff;color:#1e3a8a;' : ($pillVariant === 'in_progress' ? 'background:#fef3c7;color:#92400e;' : ($pillVariant === 'resolved' ? 'background:#dcfce7;color:#15803d;' : ($pillVariant === 'rejected' ? 'background:#fee2e2;color:#991b1b;' : 'background:#f1f5f9;color:#475569;'))))) }}
                    display: inline-flex; align-items: center; gap: 1.5px; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap;">
                    {{ $statusLabel }}
                </span>
            </div>
        </div>
        <h3 style="margin: 0 0 12px; font-size: 17px; font-weight: 600; color: #172b49; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; overflow: hidden;">{{ $title }}</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <span style="font-size: 13px; color: #64748b; overflow-wrap: anywhere;">{{ $report['category_label'] ?? '' }}</span>
            <span style="font-size: 13px; color: #64748b; overflow-wrap: anywhere;">{{ $department }}</span>
            <span style="font-size: 13px; color: #64748b; overflow-wrap: anywhere;">{{ $location }}</span>
            <span style="font-size: 13px; color: #64748b; overflow-wrap: anywhere;">{{ $reported }}</span>
        </div>
    </a>
</li>
