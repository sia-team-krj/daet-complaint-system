@php
    $metrics = $data['metrics'];
    $previous = $metrics['previous'];
    $showStaffBreakdown = (bool) ($staffBreakdown ?? false);
    $formatPercent = fn ($value): string => $value === null
        ? '—'
        : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%';
    $formatDays = fn ($value): string => $value === null
        ? '—'
        : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').' '.__('analytics.days_short');

    $statCards = [
        [
            'key' => 'filed',
            'label' => __('analytics.stats.filed'),
            'value' => number_format($metrics['filed']),
            'meta' => __('analytics.stats.filed_meta'),
            'delta' => ['current' => $metrics['filed'], 'previous' => $previous['filed'], 'type' => 'count'],
        ],
        [
            'key' => 'resolved',
            'label' => __('analytics.stats.resolved'),
            'value' => number_format($metrics['resolved']),
            'meta' => __('analytics.stats.resolved_meta'),
            'delta' => ['current' => $metrics['resolved'], 'previous' => $previous['resolved'], 'type' => 'count'],
        ],
        [
            'key' => 'open',
            'label' => __('analytics.stats.open'),
            'value' => number_format($metrics['open']),
            'meta' => __('analytics.stats.open_meta', [
                'assigned' => number_format($metrics['assigned_open']),
                'unassigned' => number_format($metrics['unassigned_open']),
            ]),
            'delta' => null,
        ],
        [
            'key' => 'overdue',
            'label' => __('analytics.stats.overdue'),
            'value' => number_format($metrics['overdue']),
            'meta' => __('analytics.stats.overdue_meta'),
            'delta' => null,
            'tone' => $metrics['overdue'] > 0 ? 'attention' : 'calm',
        ],
        [
            'key' => 'avg',
            'label' => __('analytics.stats.avg_days'),
            'value' => $formatDays($metrics['avg_days']),
            'meta' => __('analytics.stats.avg_meta'),
            'delta' => ['current' => $metrics['avg_days'], 'previous' => $previous['avg_days'], 'type' => 'days'],
        ],
        [
            'key' => 'on_time',
            'label' => __('analytics.stats.on_time'),
            'value' => $formatPercent($metrics['on_time_rate']),
            'meta' => $metrics['on_time_base'] > 0
                ? __('analytics.stats.on_time_meta', [
                    'on_time' => number_format($metrics['on_time']),
                    'base' => number_format($metrics['on_time_base']),
                ])
                : __('analytics.stats.on_time_meta_empty'),
            'delta' => ['current' => $metrics['on_time_rate'], 'previous' => $previous['on_time_rate'], 'type' => 'rate'],
        ],
    ];
@endphp

<div class="an-content">
    <p class="an-note">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        {{ __('analytics.stats.resolved_note') }}
    </p>

    <section class="an-stats" aria-label="{{ __('analytics.sections.summary') }}">
        @foreach($statCards as $card)
            <article class="an-stat {{ isset($card['tone']) ? 'an-stat--'.$card['tone'] : '' }}">
                <span class="an-stat__label">{{ $card['label'] }}</span>
                <strong class="an-stat__value">{{ $card['value'] }}</strong>
                <span class="an-stat__meta">{{ $card['meta'] }}</span>
                @if($card['delta'])
                    @include('analytics.partials.delta', [
                        'current' => $card['delta']['current'],
                        'previous' => $card['delta']['previous'],
                        'type' => $card['delta']['type'],
                    ])
                @endif
            </article>
        @endforeach
    </section>

    <div class="an-grid an-grid--trend">
        <section class="an-card an-card--wide" aria-labelledby="an-trend-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-trend-title">{{ __('analytics.sections.trend') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.trend_sub', ['granularity' => __('analytics.granularity.'.$data['trend']['granularity'])]) }}</p>
                </div>
            </div>
            @include('analytics.partials.trend-chart', ['trend' => $data['trend']])
        </section>

        <section class="an-card" aria-labelledby="an-status-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-status-title">{{ __('analytics.sections.status_mix') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.status_mix_sub') }}</p>
                </div>
            </div>
            @include('analytics.partials.status-mix', ['mix' => $data['status_mix']])
        </section>
    </div>

    <div class="an-grid an-grid--two">
        <section class="an-card" aria-labelledby="an-categories-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-categories-title">{{ __('analytics.sections.categories') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.categories_sub') }}</p>
                </div>
            </div>
            @include('analytics.partials.bars', [
                'items' => $data['categories'],
                'emptyMessage' => __('analytics.sections.no_reports'),
            ])
        </section>

        <section class="an-card" aria-labelledby="an-barangays-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-barangays-title">{{ __('analytics.sections.barangays') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.barangays_sub') }}</p>
                </div>
            </div>
            @include('analytics.partials.bars', [
                'items' => $data['barangays'],
                'emptyMessage' => __('analytics.sections.no_barangays'),
            ])
        </section>
    </div>

    <div class="an-grid an-grid--two">
        <section class="an-card" aria-labelledby="an-aging-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-aging-title">{{ __('analytics.sections.aging') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.aging_sub') }}</p>
                </div>
            </div>
            @php
                $agingTotal = array_sum(array_map(fn ($bucket) => $bucket['count'], $data['aging']));
                $agingItems = array_map(fn ($bucket) => [
                    'key' => $bucket['key'],
                    'label' => __('analytics.aging.'.$bucket['key']),
                    'count' => $bucket['count'],
                    'percent' => $agingTotal > 0 ? round(($bucket['count'] / $agingTotal) * 100, 1) : 0,
                ], $data['aging']);
            @endphp
            @if($agingTotal === 0)
                <div class="an-empty">
                    <strong>{{ __('analytics.sections.all_clear') }}</strong>
                </div>
            @else
                @include('analytics.partials.bars', [
                    'items' => $agingItems,
                    'fillClass' => 'an-bars__fill an-bars__fill--aging',
                    'emptyMessage' => __('analytics.sections.all_clear'),
                ])
            @endif
        </section>

        <section class="an-card" aria-labelledby="an-quality-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-quality-title">{{ __('analytics.sections.quality') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.quality_sub') }}</p>
                </div>
            </div>

            <div class="an-quality">
                <div class="an-quality__item">
                    <span>{{ __('analytics.quality.verified') }}</span>
                    <strong>{{ number_format($data['quality']['verified']) }}</strong>
                    <small>{{ $data['quality']['verified_rate'] !== null
                        ? __('analytics.quality.verified_share', ['value' => $formatPercent($data['quality']['verified_rate'])])
                        : __('analytics.quality.no_filed') }}</small>
                </div>
                <div class="an-quality__item">
                    <span>{{ __('analytics.quality.needs_information') }}</span>
                    <strong>{{ number_format($data['quality']['needs_information']) }}</strong>
                </div>
                <div class="an-quality__item">
                    <span>{{ __('analytics.quality.duplicate') }}</span>
                    <strong>{{ number_format($data['quality']['duplicate']) }}</strong>
                </div>
                <div class="an-quality__item">
                    <span>{{ __('analytics.quality.rejected') }}</span>
                    <strong>{{ number_format($data['quality']['rejected']) }}</strong>
                </div>
            </div>
        </section>
    </div>

    <section class="an-card" aria-labelledby="an-workload-title">
        <div class="an-card__head">
            <div>
                <h2 class="an-card__title" id="an-workload-title">{{ __('analytics.sections.workload') }}</h2>
                <p class="an-card__sub">{{ __('analytics.sections.workload_sub') }}</p>
            </div>
        </div>

        <div class="an-mini-grid">
            <div class="an-mini">
                <span>{{ __('analytics.workload.team') }}</span>
                <strong>{{ number_format($data['workload']['team']) }}</strong>
            </div>
            <div class="an-mini">
                <span>{{ __('analytics.workload.assigned') }}</span>
                <strong>{{ number_format($data['workload']['assigned']) }}</strong>
            </div>
            <div class="an-mini">
                <span>{{ __('analytics.workload.unassigned') }}</span>
                <strong>{{ number_format($data['workload']['unassigned']) }}</strong>
            </div>
            <div class="an-mini">
                <span>{{ __('analytics.workload.avg') }}</span>
                <strong>{{ $data['workload']['per_staff'] !== null ? number_format($data['workload']['per_staff'], 1) : '—' }}</strong>
            </div>
        </div>

        @if($showStaffBreakdown)
            @if($data['workload']['staff'] === [])
                <div class="an-empty"><strong>{{ __('analytics.sections.no_staff') }}</strong></div>
            @else
                <div class="an-table-wrap">
                    <table class="an-table an-table--workload">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('analytics.workload.table.staff') }}</th>
                                <th scope="col">{{ __('analytics.workload.table.open') }}</th>
                                <th scope="col">{{ __('analytics.workload.table.in_progress') }}</th>
                                <th scope="col">{{ __('analytics.workload.table.resolved') }}</th>
                                <th scope="col">{{ __('analytics.workload.table.overdue') }}</th>
                                <th scope="col">{{ __('analytics.workload.table.avg_days') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['workload']['staff'] as $member)
                                <tr>
                                    <th scope="row">{{ $member['name'] }}</th>
                                    <td data-label="{{ __('analytics.workload.table.open') }}">{{ number_format($member['open']) }}</td>
                                    <td data-label="{{ __('analytics.workload.table.in_progress') }}">{{ number_format($member['in_progress']) }}</td>
                                    <td data-label="{{ __('analytics.workload.table.resolved') }}">{{ number_format($member['resolved']) }}</td>
                                    <td data-label="{{ __('analytics.workload.table.overdue') }}">{{ number_format($member['overdue']) }}</td>
                                    <td data-label="{{ __('analytics.workload.table.avg_days') }}">{{ $member['avg_days'] !== null ? $formatDays($member['avg_days']) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <p class="an-table-note">{{ __('analytics.sections.staff_breakdown_sub') }}</p>
        @else
            <p class="an-table-note">{{ __('analytics.sections.workload_staff_note') }}</p>
        @endif
    </section>

    <section class="an-card" aria-labelledby="an-activity-title">
        <div class="an-card__head">
            <div>
                <h2 class="an-card__title" id="an-activity-title">{{ __('analytics.sections.activity') }}</h2>
                <p class="an-card__sub">{{ __('analytics.sections.activity_sub') }}</p>
            </div>
        </div>

        @if($data['activity']->isEmpty())
            <div class="an-empty"><strong>{{ __('analytics.sections.no_activity') }}</strong></div>
        @else
            @php
                $activityPrimary = $data['activity']->take(8);
                $activityRemainder = $data['activity']->slice(8);
            @endphp
            <ol class="an-feed">
                @foreach($activityPrimary as $entry)
                    @include('analytics.partials.feed-item', ['entry' => $entry, 'routeNames' => $routeNames])
                @endforeach
            </ol>
            @if($activityRemainder->isNotEmpty())
                <details class="an-feed__more">
                    <summary>{{ trans_choice('analytics.activity.show_more', $activityRemainder->count(), ['count' => $activityRemainder->count()]) }}</summary>
                    <ol class="an-feed an-feed--more">
                        @foreach($activityRemainder as $entry)
                            @include('analytics.partials.feed-item', ['entry' => $entry, 'routeNames' => $routeNames])
                        @endforeach
                    </ol>
                </details>
            @endif
        @endif
    </section>

    @if(($surface ?? 'staff') === 'admin')
        @include('analytics.partials.fairness')
    @endif
</div>
