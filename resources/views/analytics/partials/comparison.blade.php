@php
    $totals = $data['totals'];
    $rows = $data['rows'];
    $formatPercent = fn ($value): string => $value === null
        ? '—'
        : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%';
    $formatDays = fn ($value): string => $value === null
        ? '—'
        : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').' '.__('analytics.days_short');

    $statCards = [
        [
            'label' => __('analytics.comparison.active_offices_label'),
            'value' => number_format($totals['offices']),
            'meta' => __('analytics.comparison.active_offices', ['count' => number_format($totals['offices'])]),
        ],
        [
            'label' => __('analytics.stats.filed'),
            'value' => number_format($totals['filed']),
            'meta' => __('analytics.stats.filed_meta'),
            'delta' => ['current' => $totals['filed'], 'previous' => $totals['previous']['filed'], 'type' => 'count'],
        ],
        [
            'label' => __('analytics.stats.resolved'),
            'value' => number_format($totals['resolved']),
            'meta' => __('analytics.stats.resolved_meta'),
            'delta' => ['current' => $totals['resolved'], 'previous' => $totals['previous']['resolved'], 'type' => 'count'],
        ],
        [
            'label' => __('analytics.stats.open'),
            'value' => number_format($totals['open']),
            'meta' => __('analytics.comparison.per_staff', ['value' => $totals['per_staff'] !== null ? number_format($totals['per_staff'], 1) : '—']),
        ],
        [
            'label' => __('analytics.stats.overdue'),
            'value' => number_format($totals['overdue']),
            'meta' => __('analytics.stats.overdue_meta'),
            'tone' => $totals['overdue'] > 0 ? 'attention' : 'calm',
        ],
        [
            'label' => __('analytics.stats.on_time'),
            'value' => $formatPercent($totals['on_time_rate']),
            'meta' => $totals['on_time_base'] > 0
                ? __('analytics.stats.on_time_meta', [
                    'on_time' => number_format($totals['on_time']),
                    'base' => number_format($totals['on_time_base']),
                ])
                : __('analytics.stats.on_time_meta_empty'),
        ],
    ];
@endphp

<div class="an-content">
    <section class="an-stats" aria-label="{{ __('analytics.comparison.totals') }}">
        @foreach($statCards as $card)
            <article class="an-stat {{ isset($card['tone']) ? 'an-stat--'.$card['tone'] : '' }}">
                <span class="an-stat__label">{{ $card['label'] }}</span>
                <strong class="an-stat__value">{{ $card['value'] }}</strong>
                <span class="an-stat__meta">{{ $card['meta'] }}</span>
                @if(! empty($card['delta']))
                    @include('analytics.partials.delta', [
                        'current' => $card['delta']['current'],
                        'previous' => $card['delta']['previous'],
                        'type' => $card['delta']['type'],
                    ])
                @endif
            </article>
        @endforeach
    </section>

    @include('analytics.partials.fairness')

    <section class="an-card an-card--table" aria-labelledby="an-comparison-title">
        <div class="an-card__head">
            <div>
                <h2 class="an-card__title" id="an-comparison-title">{{ __('analytics.comparison.table_title') }}</h2>
                <p class="an-card__sub">{{ __('analytics.comparison.table_sub') }}</p>
            </div>
        </div>

        @if($rows->isEmpty())
            <div class="an-empty"><strong>{{ __('analytics.comparison.no_offices') }}</strong></div>
        @else
            <div class="an-table-wrap">
                <table class="an-table an-table--comparison">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('analytics.comparison.office') }}</th>
                            <th scope="col">{{ __('analytics.comparison.team') }}</th>
                            <th scope="col">{{ __('analytics.stats.open') }}</th>
                            <th scope="col">{{ __('analytics.stats.filed') }}</th>
                            <th scope="col">{{ __('analytics.stats.resolved') }}</th>
                            <th scope="col">{{ __('analytics.stats.avg_days') }}</th>
                            <th scope="col">{{ __('analytics.stats.on_time') }}</th>
                            <th scope="col">{{ __('analytics.stats.overdue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $department = $row['department'];
                                $metrics = $row['metrics'];
                                $previous = $metrics['previous'];
                                $departmentUrl = route($routeNames['department'], array_merge(
                                    ['department' => $department->id],
                                    $period->query(),
                                ));
                            @endphp
                            <tr>
                                <th scope="row" class="an-table__office" data-label="{{ __('analytics.comparison.office') }}">
                                    <a href="{{ $departmentUrl }}" class="an-table__office-link">
                                        <span class="dept-badge" data-dept="{{ $department->code }}">{{ $department->code }}</span>
                                        <span>{{ $department->name }}</span>
                                    </a>
                                </th>
                                <td data-label="{{ __('analytics.comparison.team') }}">
                                    <span class="an-table__strong">{{ number_format($metrics['staff_count']) }}</span>
                                    <span class="an-table__small">{{ __('analytics.comparison.staff_short', ['count' => number_format($metrics['staff_count'])]) }}</span>
                                </td>
                                <td data-label="{{ __('analytics.stats.open') }}">
                                    <span class="an-table__strong">{{ number_format($metrics['open']) }}</span>
                                    <span class="an-table__small">{{ __('analytics.comparison.per_staff', ['value' => $metrics['per_staff'] !== null ? number_format($metrics['per_staff'], 1) : '—']) }}</span>
                                </td>
                                <td data-label="{{ __('analytics.stats.filed') }}">
                                    <span class="an-table__strong">{{ number_format($metrics['filed']) }}</span>
                                    @include('analytics.partials.delta', ['current' => $metrics['filed'], 'previous' => $previous['filed'], 'type' => 'count'])
                                </td>
                                <td data-label="{{ __('analytics.stats.resolved') }}">
                                    <span class="an-table__strong">{{ number_format($metrics['resolved']) }}</span>
                                    @include('analytics.partials.delta', ['current' => $metrics['resolved'], 'previous' => $previous['resolved'], 'type' => 'count'])
                                </td>
                                <td data-label="{{ __('analytics.stats.avg_days') }}">
                                    <span class="an-table__strong">{{ $formatDays($metrics['avg_days']) }}</span>
                                    @include('analytics.partials.delta', ['current' => $metrics['avg_days'], 'previous' => $previous['avg_days'], 'type' => 'days'])
                                </td>
                                <td data-label="{{ __('analytics.stats.on_time') }}">
                                    <span class="an-table__strong">{{ $formatPercent($metrics['on_time_rate']) }}</span>
                                    @include('analytics.partials.delta', ['current' => $metrics['on_time_rate'], 'previous' => $previous['on_time_rate'], 'type' => 'rate'])
                                </td>
                                <td data-label="{{ __('analytics.stats.overdue') }}">
                                    <span class="an-table__strong {{ $metrics['overdue'] > 0 ? 'is-attention' : '' }}">{{ number_format($metrics['overdue']) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
