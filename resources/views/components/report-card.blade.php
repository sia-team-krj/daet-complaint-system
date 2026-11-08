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

<li {{ $attributes->merge(['class' => 'group']) }}>
    <a href="{{ $url }}" wire:navigate class="block rounded-12 border border-navy-200 bg-white p-6 hover:border-navy-300 hover:shadow-sm transition-all" aria-label="{{ __('transparency.register.open_ticket', ['ticket' => $ticketId]) }}">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-2 min-w-0">
                <span class="block truncate text-[11px] font-mono text-navy-700 leading-none">{{ $ticketId }}</span>
                <x-pill variant="{{ $pillVariant }}">{{ $statusLabel }}</x-pill>
            </div>
        </div>

        <h3 class="mt-4 text-sm font-medium text-navy-900 leading-tight line-clamp-2">{{ $title }}</h3>

        <div class="mt-4 flex flex-wrap gap-3 text-[11px] text-navy-500">
            <span class="truncate">{{ $report['category_label'] ?? '' }}</span>
            <span class="truncate">{{ $department }}</span>
            <span class="truncate">{{ $location }}</span>
            <span class="truncate">{{ $reported }}</span>
        </div>
    </a>
</li>
