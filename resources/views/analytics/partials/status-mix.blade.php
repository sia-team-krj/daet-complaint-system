@php
    $total = (int) $mix['total'];
    $segments = collect($mix['segments'])->filter(fn ($segment) => $segment['count'] > 0)->values();
    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $offset = 0.0;
@endphp

@if($total === 0)
    <div class="an-empty"><strong>{{ __('analytics.sections.no_reports') }}</strong></div>
@else
    <div class="an-donut" data-an-donut>
        <svg viewBox="0 0 140 140" role="img" aria-label="{{ __('analytics.chart.total_filed', ['count' => number_format($total)]) }}">
            <circle class="an-donut__track" cx="70" cy="70" r="{{ $radius }}" fill="none" stroke-width="16"></circle>

            @foreach($segments as $segment)
                @php
                    $length = ($segment['count'] / $total) * $circumference;
                    $gap = $circumference - $length;
                @endphp
                <circle
                    class="an-donut__segment an-donut__segment--{{ $segment['key'] }}"
                    data-an-donut-segment="{{ $segment['key'] }}"
                    cx="70"
                    cy="70"
                    r="{{ $radius }}"
                    fill="none"
                    stroke-width="16"
                    stroke-dasharray="{{ number_format($length, 2, '.', '') }} {{ number_format($gap, 2, '.', '') }}"
                    stroke-dashoffset="{{ number_format(-$offset, 2, '.', '') }}"
                    transform="rotate(-90 70 70)"
                ></circle>
                @php $offset += $length; @endphp
            @endforeach

            <text class="an-donut__total" x="70" y="67" text-anchor="middle">{{ number_format($total) }}</text>
            <text class="an-donut__caption" x="70" y="84" text-anchor="middle">{{ __('analytics.chart.filed') }}</text>
        </svg>

        <ul class="an-donut__legend">
            @foreach($mix['segments'] as $segment)
                <li>
                    <button
                        type="button"
                        class="an-donut__legend-item {{ $segment['count'] === 0 ? 'is-empty' : '' }}"
                        data-an-donut-hit
                        data-key="{{ $segment['key'] }}"
                        data-label="{{ $segment['label'] }}"
                        data-count="{{ $segment['count'] }}"
                        data-percent="{{ $segment['percent'] }}"
                    >
                        <span class="an-donut__dot an-donut__dot--{{ $segment['key'] }}" aria-hidden="true"></span>
                        <span class="an-donut__label">{{ $segment['label'] }}</span>
                        <span class="an-donut__value">{{ number_format($segment['count']) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <output
            class="an-inspect an-donut__detail"
            data-an-donut-detail
            data-placeholder="{{ __('analytics.chart.inspect_donut') }}"
            data-template="{{ __('analytics.chart.donut_detail') }}"
        >{{ __('analytics.chart.inspect_donut') }}</output>
    </div>
@endif
