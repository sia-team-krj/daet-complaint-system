@php
    $items = $items ?? [];
    $fillClass = $fillClass ?? 'an-bars__fill';
    $max = $items === [] ? 1 : max(1, ...array_map(fn ($item) => $item['count'], $items));
@endphp

@if($items === [])
    <div class="an-empty"><strong>{{ $emptyMessage }}</strong></div>
@else
    <div class="an-bars-wrap" data-an-bars>
        <ul class="an-bars">
            @foreach($items as $item)
                <li class="an-bars__item">
                    <button
                        type="button"
                        class="an-bars__row an-bars__hit"
                        data-an-bar-hit
                        data-label="{{ $item['label'] }}"
                        data-count="{{ $item['count'] }}"
                        data-percent="{{ $item['percent'] ?? 0 }}"
                    >
                        <span class="an-bars__label" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                        <span class="an-bars__track" aria-hidden="true">
                            <span class="{{ $fillClass }}" style="width: {{ max(2, round(($item['count'] / $max) * 100)) }}%"></span>
                        </span>
                        <span class="an-bars__count">{{ number_format($item['count']) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <output
            class="an-inspect an-bars__detail"
            data-an-bars-detail
            data-placeholder="{{ __('analytics.chart.inspect_bars') }}"
            data-template="{{ __('analytics.chart.bar_detail') }}"
        >{{ __('analytics.chart.inspect_bars') }}</output>
    </div>
@endif
