@php
    $rows = $trend['rows'];
    $max = max(1, (int) $trend['max']);
    $count = count($rows);
    $width = 720;
    $height = 240;
    $left = 44;
    $right = 16;
    $top = 16;
    $bottom = 40;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $spacing = $count <= 1 ? $plotWidth : $plotWidth / ($count - 1);
    $x = fn (int $index): float => $count <= 1
        ? $left + ($plotWidth / 2)
        : $left + ($index * $spacing);
    $y = fn (int $value): float => $top + $plotHeight - (($value / $max) * $plotHeight);
    $filedPoints = [];
    $resolvedPoints = [];
    foreach ($rows as $index => $row) {
        $filedPoints[] = number_format($x($index), 1, '.', '').','.number_format($y($row['filed']), 1, '.', '');
        $resolvedPoints[] = number_format($x($index), 1, '.', '').','.number_format($y($row['resolved']), 1, '.', '');
    }
    $labelEvery = max(1, (int) ceil($count / 7));
    $filedTotal = array_sum(array_column($rows, 'filed'));
    $resolvedTotal = array_sum(array_column($rows, 'resolved'));
@endphp

<figure class="an-chart" data-an-chart>
    <div class="an-chart__legend" aria-hidden="true">
        <span class="an-chart__key an-chart__key--filed">{{ __('analytics.chart.filed') }}</span>
        <span class="an-chart__key an-chart__key--resolved">{{ __('analytics.chart.resolved') }}</span>
    </div>

    <output
        class="an-inspect an-chart__detail"
        data-an-chart-detail
        data-placeholder="{{ __('analytics.chart.inspect_trend') }}"
        data-template="{{ __('analytics.chart.trend_detail') }}"
    >{{ __('analytics.chart.inspect_trend') }}</output>

    <div class="an-chart__canvas">
        <svg
            viewBox="0 0 {{ $width }} {{ $height }}"
            role="img"
            aria-label="{{ __('analytics.chart.aria', ['filed' => $filedTotal, 'resolved' => $resolvedTotal]) }}"
            preserveAspectRatio="xMidYMid meet"
        >
            <g class="an-chart__grid">
                @for($step = 0; $step <= 4; $step++)
                    @php
                        $value = (int) round($max * ($step / 4));
                        $gridY = $y($value);
                    @endphp
                    <line x1="{{ $left }}" y1="{{ number_format($gridY, 1, '.', '') }}" x2="{{ $width - $right }}" y2="{{ number_format($gridY, 1, '.', '') }}"></line>
                    <text x="{{ $left - 8 }}" y="{{ number_format($gridY + 4, 1, '.', '') }}" text-anchor="end">{{ $value }}</text>
                @endfor
            </g>

            <polyline class="an-chart__line an-chart__line--filed" points="{{ implode(' ', $filedPoints) }}"></polyline>
            <polyline class="an-chart__line an-chart__line--resolved" points="{{ implode(' ', $resolvedPoints) }}"></polyline>

            @if($count <= 14)
                @foreach($rows as $index => $row)
                    <circle class="an-chart__dot an-chart__dot--filed" cx="{{ number_format($x($index), 1, '.', '') }}" cy="{{ number_format($y($row['filed']), 1, '.', '') }}" r="3"></circle>
                    <circle class="an-chart__dot an-chart__dot--resolved" cx="{{ number_format($x($index), 1, '.', '') }}" cy="{{ number_format($y($row['resolved']), 1, '.', '') }}" r="3"></circle>
                @endforeach
            @endif

            @foreach($rows as $index => $row)
                @php
                    $bandStart = min(max($left, $x($index) - $spacing / 2), $width - $right);
                    $bandEnd = min(max($left, $x($index) + $spacing / 2), $width - $right);
                    $bandWidth = max(1, $bandEnd - $bandStart);
                @endphp
                <rect
                    class="an-chart__hit"
                    data-an-chart-band
                    x="{{ number_format($bandStart, 1, '.', '') }}"
                    y="{{ $top }}"
                    width="{{ number_format($bandWidth, 1, '.', '') }}"
                    height="{{ $plotHeight }}"
                    tabindex="0"
                    role="button"
                    aria-label="{{ __('analytics.chart.trend_point_aria', ['label' => $row['label'], 'filed' => $row['filed'], 'resolved' => $row['resolved']]) }}"
                    data-label="{{ $row['label'] }}"
                    data-filed="{{ $row['filed'] }}"
                    data-resolved="{{ $row['resolved'] }}"
                ></rect>
            @endforeach

            @foreach($rows as $index => $row)
                @if($index % $labelEvery === 0 || $index === $count - 1)
                    <text class="an-chart__x-label" x="{{ number_format($x($index), 1, '.', '') }}" y="{{ $height - 14 }}" text-anchor="middle">{{ $row['label'] }}</text>
                @endif
            @endforeach
        </svg>
    </div>

    <details class="an-chart__data">
        <summary>{{ __('analytics.chart.view_data') }}</summary>
        <div class="an-table-wrap">
            <table class="an-table an-table--compact">
                <thead>
                    <tr>
                        <th scope="col">{{ __('analytics.chart.date') }}</th>
                        <th scope="col">{{ __('analytics.chart.filed') }}</th>
                        <th scope="col">{{ __('analytics.chart.resolved') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <th scope="row">{{ $row['label'] }}</th>
                            <td data-label="{{ __('analytics.chart.filed') }}">{{ number_format($row['filed']) }}</td>
                            <td data-label="{{ __('analytics.chart.resolved') }}">{{ number_format($row['resolved']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
