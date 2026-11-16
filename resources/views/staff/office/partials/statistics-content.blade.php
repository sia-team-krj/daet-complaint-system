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
    $fmtCount = fn ($value): string => number_format((int) $value);

    $delta = function ($current, $prev, $type = 'count') {
        if ($prev === null || $current === null) return null;
        if ($type === 'count') {
            $diff = (int) $current - (int) $prev;
            $label = ($diff > 0 ? '+' : '').$diff.' '.__('office.statistics.vs_previous');
            $cls = $diff > 0 ? 'is-up' : ($diff < 0 ? 'is-down' : 'is-flat');
            return ['label' => $label, 'cls' => $cls];
        }
        $diff = round((float) $current - (float) $prev, 1);
        $unit = $type === 'days' ? ' '.__('analytics.days_short') : '%';
        $label = ($diff > 0 ? '+' : '').rtrim(rtrim(number_format($diff, 1), '0'), '.').$unit.' '.__('office.statistics.vs_previous');
        $cls = $diff > 0 ? 'is-up' : ($diff < 0 ? 'is-down' : 'is-flat');
        return ['label' => $label, 'cls' => $cls];
    };

    $onHold = 0;
    foreach (($data['status_mix'] ?? []) as $row) {
        if (($row['key'] ?? null) === 'on_hold') { $onHold = (int) ($row['count'] ?? 0); break; }
    }

    $avgFirstAction = $metrics['avg_first_action_days'] ?? null;
    $prevAvgFirstAction = $previous['avg_first_action_days'] ?? null;

    $kpis = [
        ['label' => __('analytics.stats.filed'), 'value' => $fmtCount($metrics['filed']), 'icon' => 'file', 'tone' => 'navy', 'meta' => __('analytics.stats.filed_meta'), 'delta' => $delta($metrics['filed'], $previous['filed']), 'href' => $drilldown['filed'] ?? null],
        ['label' => __('analytics.stats.open'), 'value' => $fmtCount($metrics['open']), 'icon' => 'clock', 'tone' => 'amber', 'meta' => __('analytics.stats.open_meta', ['assigned' => $fmtCount($metrics['assigned_open']), 'unassigned' => $fmtCount($metrics['unassigned_open'])]), 'delta' => null, 'href' => $drilldown['open'] ?? null],
        ['label' => __('analytics.stats.resolved'), 'value' => $fmtCount($metrics['resolved']), 'icon' => 'check', 'tone' => 'green', 'meta' => __('analytics.stats.resolved_meta'), 'delta' => $delta($metrics['resolved'], $previous['resolved']), 'href' => $drilldown['resolved'] ?? null],
        ['label' => __('analytics.stats.overdue'), 'value' => $fmtCount($metrics['overdue']), 'icon' => 'alert', 'tone' => 'amber', 'meta' => __('analytics.stats.overdue_meta'), 'delta' => null, 'href' => $drilldown['overdue'] ?? null],
        ['label' => __('office.statistics.on_hold'), 'value' => $fmtCount($onHold), 'icon' => 'pause', 'tone' => 'sky', 'meta' => __('office.statistics.on_hold_meta'), 'delta' => null, 'href' => $drilldown['on_hold'] ?? null],
        ['label' => __('analytics.stats.avg_days'), 'value' => $formatDays($metrics['avg_days']), 'icon' => 'calendar', 'tone' => 'navy', 'meta' => __('analytics.stats.avg_meta'), 'delta' => $delta($metrics['avg_days'], $previous['avg_days'], 'days'), 'href' => null],
        ['label' => __('office.statistics.avg_first_action'), 'value' => $formatDays($avgFirstAction), 'icon' => 'bolt', 'tone' => 'green', 'meta' => __('office.statistics.avg_first_action_meta'), 'delta' => $delta($avgFirstAction, $prevAvgFirstAction, 'days'), 'href' => null],
        ['label' => __('analytics.stats.on_time'), 'value' => $formatPercent($metrics['on_time_rate']), 'icon' => 'target', 'tone' => 'green', 'meta' => $metrics['on_time_base'] > 0 ? __('analytics.stats.on_time_meta', ['on_time' => $fmtCount($metrics['on_time']), 'base' => $fmtCount($metrics['on_time_base'])]) : __('analytics.stats.on_time_meta_empty'), 'delta' => $delta($metrics['on_time_rate'], $previous['on_time_rate'], 'rate'), 'href' => null],
    ];

    $icon = function ($name) {
        return match ($name) {
            'file' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/></svg>',
            'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>',
            'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
            'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 4.3 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>',
            'pause' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="10" y1="9" x2="10" y2="15"/><line x1="14" y1="9" x2="14" y2="15"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'bolt' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg>',
            'target' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/></svg>',
            default => '',
        };
    };
@endphp

<section class="dept-stats office-stats-kpis" aria-label="{{ __('office.statistics.kpi_aria') }}">
    @foreach($kpis as $card)
        @if(!empty($card['href']))
            <a class="dept-stat" href="{{ $card['href'] }}" wire:navigate>
                <span class="tr-stat__icon tr-stat__icon--{{ $card['tone'] }} dept-si dept-si--{{ $card['tone'] }}" aria-hidden="true">{!! $icon($card['icon']) !!}</span>
                <span>
                    <span class="dept-stat__value">{{ $card['value'] }}</span>
                    <span class="dept-stat__label">{{ $card['label'] }}</span>
                    <span class="dept-stat__meta">{{ $card['meta'] }}</span>
                    @if($card['delta'])<span class="dept-stat__delta {{ $card['delta']['cls'] }}">{{ $card['delta']['label'] }}</span>@endif
                </span>
            </a>
        @else
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--{{ $card['tone'] }} dept-si dept-si--{{ $card['tone'] }}" aria-hidden="true">{!! $icon($card['icon']) !!}</span>
                <span>
                    <span class="dept-stat__value">{{ $card['value'] }}</span>
                    <span class="dept-stat__label">{{ $card['label'] }}</span>
                    <span class="dept-stat__meta">{{ $card['meta'] }}</span>
                    @if($card['delta'])<span class="dept-stat__delta {{ $card['delta']['cls'] }}">{{ $card['delta']['label'] }}</span>@endif
                </span>
            </div>
        @endif
    @endforeach
</section>

<div class="office-stats-links" aria-label="{{ __('office.statistics.quick_filters') }}">
    <a class="status-chip" href="{{ $drilldown['pending'] ?? '#' }}" wire:navigate>{{ __('nav.needs_verification') }}</a>
    <a class="status-chip" href="{{ $drilldown['needs_information'] ?? '#' }}" wire:navigate>{{ __('nav.waiting_on_resident') }}</a>
    <a class="status-chip" href="{{ $drilldown['in_progress'] ?? '#' }}" wire:navigate>{{ __('analytics.status_mix.in_progress') }}</a>
    <a class="status-chip" href="{{ $drilldown['closed'] ?? '#' }}" wire:navigate>{{ __('analytics.status_mix.closed') }}</a>
</div>

<div class="an-content">
    <p class="an-note">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        {{ __('analytics.stats.resolved_note') }}
    </p>

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
            <p class="an-table-note" style="margin-top:10px;">{{ __('office.statistics.drilldown_hint') }}</p>
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
            @if(empty($data['categories']))
                <div class="an-empty"><strong>{{ __('analytics.sections.no_reports') }}</strong></div>
            @else
                <ul class="an-bars">
                    @foreach($data['categories'] as $item)
                        <li class="an-bars__item">
                            <div class="an-bars__label">
                                <a href="{{ route('staff.complaints.index', ['category' => $item['key']]) }}" wire:navigate>{{ $item['label'] }}</a>
                                <span>{{ number_format($item['count']) }} · {{ rtrim(rtrim(number_format($item['percent'], 1), '0'), '.') }}%</span>
                            </div>
                            <div class="an-bars__track"><div class="an-bars__fill" style="width: {{ max(2, (float)$item['percent']) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="an-card" aria-labelledby="an-barangays-title">
            <div class="an-card__head">
                <div>
                    <h2 class="an-card__title" id="an-barangays-title">{{ __('analytics.sections.barangays') }}</h2>
                    <p class="an-card__sub">{{ __('analytics.sections.barangays_sub') }}</p>
                </div>
            </div>
            @if(empty($data['barangays']))
                <div class="an-empty"><strong>{{ __('analytics.sections.no_barangays') }}</strong></div>
            @else
                <ul class="an-bars">
                    @foreach($data['barangays'] as $item)
                        <li class="an-bars__item">
                            <div class="an-bars__label">
                                <a href="{{ route('staff.complaints.index', ['barangay' => $item['label']]) }}" wire:navigate>{{ $item['label'] }}</a>
                                <span>{{ number_format($item['count']) }} · {{ rtrim(rtrim(number_format($item['percent'], 1), '0'), '.') }}%</span>
                            </div>
                            <div class="an-bars__track"><div class="an-bars__fill an-bars__fill--warm" style="width: {{ max(2, (float)$item['percent']) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @endif
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
                <div class="an-empty"><strong>{{ __('analytics.sections.all_clear') }}</strong></div>
            @else
                @include('analytics.partials.bars', ['items' => $agingItems, 'fillClass' => 'an-bars__fill an-bars__fill--aging', 'emptyMessage' => __('analytics.sections.all_clear')])
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
                    <small>{{ $data['quality']['verified_rate'] !== null ? __('analytics.quality.verified_share', ['value' => $formatPercent($data['quality']['verified_rate'])]) : __('analytics.quality.no_filed') }}</small>
                </div>
                <div class="an-quality__item"><span>{{ __('analytics.quality.needs_information') }}</span><strong>{{ number_format($data['quality']['needs_information']) }}</strong></div>
                <div class="an-quality__item"><span>{{ __('analytics.quality.duplicate') }}</span><strong>{{ number_format($data['quality']['duplicate']) }}</strong></div>
                <div class="an-quality__item"><span>{{ __('analytics.quality.rejected') }}</span><strong>{{ number_format($data['quality']['rejected']) }}</strong></div>
            </div>
        </section>
    </div>

    <section class="an-card" aria-labelledby="an-workload-title">
        <div class="an-card__head">
            <div>
                <h2 class="an-card__title" id="an-workload-title">{{ __('analytics.sections.workload') }}</h2>
                <p class="an-card__sub">{{ __('office.statistics.workload_sub') }}</p>
            </div>
        </div>
        <div class="an-mini-grid">
            <div class="an-mini"><span>{{ __('analytics.workload.team') }}</span><strong>{{ number_format($data['workload']['team']) }}</strong></div>
            <div class="an-mini"><span>{{ __('analytics.workload.assigned') }}</span><strong>{{ number_format($data['workload']['assigned']) }}</strong></div>
            <div class="an-mini"><span>{{ __('analytics.workload.unassigned') }}</span><strong>{{ number_format($data['workload']['unassigned']) }}</strong></div>
            <div class="an-mini"><span>{{ __('analytics.workload.avg') }}</span><strong>{{ $data['workload']['per_staff'] !== null ? number_format($data['workload']['per_staff'], 1) : '—' }}</strong></div>
        </div>
        @if($showStaffBreakdown)
            @if(($data['workload']['staff'] ?? []) === [])
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
                                @php
                                    $memberId = $member['id'] ?? null;
                                    $memberLink = $memberId
                                        ? route('staff.complaints.index', ['assigned_staff_id' => $memberId])
                                        : route('staff.complaints.index', ['assigned' => 'unassigned']);
                                @endphp
                                <tr>
                                    <th scope="row">
                                        <a href="{{ $memberLink }}" class="an-table__staff-link" wire:navigate>{{ $member['name'] }}</a>
                                    </th>
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
        @endif
    </section>

    @php
        $activityFeed = collect($data['activity'] ?? [])->map(function ($entry) {
            $obj = new \stdClass();
            $ticket = $entry['ticket_id'] ?? __('analytics.activity.ticket_fallback');
            $actor = $entry['actor'] ?? __('analytics.activity.system');
            $action = $entry['action'] ?? 'updated';
            $title = match ($action) {
                'filed' => __('analytics.activity.filed', ['actor' => $actor, 'ticket' => $ticket]),
                'status_changed' => __('analytics.activity.status_changed', [
                    'actor' => $actor,
                    'ticket' => $ticket,
                    'from' => $entry['from'] ?? '—',
                    'to' => $entry['to'] ?? '—',
                ]),
                'note' => __('analytics.activity.note', ['actor' => $actor, 'ticket' => $ticket]),
                default => __('analytics.activity.updated', ['actor' => $actor, 'ticket' => $ticket]),
            };
            $detail = $entry['title'] ?: $ticket;
            $note = $entry['comment'] ?? null;
            $complaint = $entry['complaint_id'] ?? null;
            $obj->display_title = $title;
            $obj->display_detail = $detail;
            $obj->display_note = $note;
            $obj->display_count = null;
            $obj->created_at = $entry['created_at'] ?? null;
            $obj->complaint = $complaint;
            $obj->complaint_ticket_id = $ticket;
            return $obj;
        });
    @endphp

    <section class="tr-card dept-activity" aria-labelledby="office-activity-title">
        <div class="dept-activity__head">
            <h2 id="office-activity-title" class="dept-section-title">{{ __('analytics.sections.activity') }}</h2>
            <p class="an-card__sub" style="margin:4px 0 0;">{{ __('analytics.sections.activity_sub') }}</p>
        </div>
        <div class="dept-activity__feed" id="office-activity-feed">
            @include('staff.partials.activity-feed', ['entries' => $activityFeed->take(12), 'extra' => true])
        </div>
        @if($activityFeed->count() > 4)
            <button type="button" class="dept-activity__more" aria-expanded="false" aria-controls="office-activity-feed">{{ __('staff.dashboard.show_more') }}</button>
        @endif
        <a href="{{ route('staff.department-activity.index') }}" class="dept-activity__view-all">{{ __('staff.dashboard.view_all_activity') }}</a>
    </section>

    <script>
    (function () {
        var feed = document.getElementById('office-activity-feed');
        var btn = feed ? feed.parentElement.querySelector('.dept-activity__more') : null;
        if (!feed || !btn) return;
        btn.addEventListener('click', function () {
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            expanded = !expanded;
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            feed.toggleAttribute('data-expanded', expanded);
            btn.textContent = expanded ? @json(__('staff.dashboard.show_less')) : @json(__('staff.dashboard.show_more'));
        });
    })();
    </script>
</div>
