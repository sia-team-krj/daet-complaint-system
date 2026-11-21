@extends('layouts.print')

@section('title', __('staff.performance.staff_report').' — '.($staff->full_name ?? ''))
@section('body-class', 'an-print-page staff-performance-print')

@section('content')
<div class="an-print">
    <header class="an-print__header">
        <div>
            <p class="an-print__municipality">{{ config('app.name', 'Daet Listens') }}</p>
            <h1 class="an-print__title">{{ __('staff.performance.staff_report') }}</h1>
            <p class="an-print__subtitle">{{ $staff->full_name }}</p>
        </div>
        <div class="an-print__meta">
            @if($staff->department?->code)
                <span class="dept-badge" data-dept="{{ $staff->department->code }}">{{ $staff->department->code }}</span>
            @endif
            <p><strong>{{ __('staff.performance.export.department') }}:</strong> {{ $staff->department?->name ?? '—' }}</p>
            <p><strong>{{ __('staff.performance.period') }}:</strong> {{ $period->rangeLabel() }}</p>
            <p>{{ __('staff.performance.generated_on', ['date' => ($generatedAt ?? now())->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}</p>
        </div>
    </header>

    <p class="an-print__notice">{{ __('staff.performance.description') }}</p>

    <section class="an-card" aria-label="{{ __('staff.performance.summary') }}" style="margin-bottom:16px;">
        <div class="an-card__head">
            <h2 class="an-card__title">{{ __('staff.performance.summary') }}</h2>
        </div>
        <div class="an-quality" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
            <div class="an-quality__item"><span>{{ __('staff.performance.metrics.actions') }}</span><strong>{{ $metrics['actions'] ?? 0 }}</strong></div>
            <div class="an-quality__item"><span>{{ __('staff.performance.metrics.complaints_touched') }}</span><strong>{{ $metrics['complaints_touched'] ?? 0 }}</strong></div>
            <div class="an-quality__item"><span>{{ __('staff.performance.metrics.resolved') }}</span><strong>{{ $metrics['resolved'] ?? 0 }}</strong></div>
            <div class="an-quality__item"><span>{{ __('staff.performance.metrics.moved_to_in_progress') }}</span><strong>{{ $metrics['moved_to_in_progress'] ?? 0 }}</strong></div>
            <div class="an-quality__item"><span>{{ __('staff.performance.metrics.status_changes') }}</span><strong>{{ $metrics['status_changes'] ?? 0 }}</strong></div>
            <div class="an-quality__item"><span>{{ __('staff.performance.metrics.responses') }}</span><strong>{{ $metrics['responses'] ?? 0 }}</strong></div>
        </div>
    </section>

    <section class="an-card" aria-label="{{ __('staff.performance.activity_log') }}">
        <div class="an-card__head">
            <h2 class="an-card__title">{{ __('staff.performance.activity_log') }}</h2>
            <p class="an-card__sub">{{ $period->rangeLabel() }}</p>
        </div>

        @if($activities->isEmpty())
            <p class="an-empty">{{ __('staff.performance.no_activity') }}</p>
        @else
            <div class="an-table-wrap">
                <table class="an-table">
                    <thead>
                        <tr>
                            <th>{{ __('staff.performance.export.date_time') }}</th>
                            <th>{{ __('staff.performance.export.ticket') }}</th>
                            <th>{{ __('staff.performance.export.complaint_title') }}</th>
                            <th>{{ __('staff.performance.export.category') }}</th>
                            <th>{{ __('staff.performance.export.from_status') }}</th>
                            <th>{{ __('staff.performance.export.to_status') }}</th>
                            <th>{{ __('staff.performance.export.action_note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>{{ $activity->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $activity->ticket_id }}</td>
                                <td>{{ $activity->complaint_title ?: '—' }}</td>
                                <td>{{ \App\Services\DepartmentAnalyticsService::categoryLabel($activity->category ?: null) }}</td>
                                <td>{{ $activity->previous_status ?? '—' }}</td>
                                <td>{{ $activity->new_status ?? '—' }}</td>
                                <td style="white-space: normal; min-width: 220px;">{{ $activity->comment !== '' ? $activity->comment : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <footer class="an-print__footer">
        <span>{{ config('app.name', 'Daet Listens') }} · {{ $staff->full_name }}</span>
        <span>{{ $period->rangeLabel() }}</span>
    </footer>
</div>

<style>
    @media screen {
        .staff-performance-print .an-print { max-width: 1100px; margin: 0 auto; padding: 24px; }
    }
    .staff-performance-print,
    .staff-performance-print .print-document,
    .staff-performance-print .an-print,
    .staff-performance-print .an-card,
    .staff-performance-print .an-print__header,
    .staff-performance-print .an-print__footer,
    .staff-performance-print .an-print__notice {
        color: var(--text);
    }
    .staff-performance-print .an-card,
    .staff-performance-print .an-table-wrap,
    .staff-performance-print .an-table,
    .staff-performance-print .an-table thead th,
    .staff-performance-print .an-table tbody th,
    .staff-performance-print .an-table tbody td {
        background: var(--surface);
        color: var(--text);
    }
    .staff-performance-print .an-table thead th {
        color: var(--muted);
        background: var(--navy-50);
    }
    .staff-performance-print .an-table tbody td,
    .staff-performance-print .an-table tbody th {
        color: var(--text);
    }
    .staff-performance-print .an-card__title,
    .staff-performance-print .an-card__sub,
    .staff-performance-print .an-quality__item span,
    .staff-performance-print .an-quality__item small,
    .staff-performance-print .an-empty {
        color: inherit;
    }
    .staff-performance-print .an-quality__item strong { font-size: 20px; color: var(--navy-900); }
    .staff-performance-print .an-table td, .staff-performance-print .an-table th { vertical-align: top; }
    @media print {
        .staff-performance-print,
        .staff-performance-print .an-print,
        .staff-performance-print .an-card,
        .staff-performance-print .an-table,
        .staff-performance-print .an-table th,
        .staff-performance-print .an-table td {
            color: var(--text) !important;
            background: var(--surface) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .staff-performance-print .an-table thead th {
            color: var(--muted) !important;
            background: var(--navy-50) !important;
        }
    }
</style>
@endsection
