@php
    $presets = ['month', '7d', '30d', '90d', 'ytd'];
    $baseParams = $routeParams ?? [];
    $isCustom = $period->preset === 'custom';
    $previous = $data['previous_period'] ?? $period->previous();
@endphp

<section class="an-period" aria-labelledby="an-period-label">
    <div class="an-period__top">
        <div>
            <h2 class="an-period__title" id="an-period-label">{{ __('analytics.period.label') }}</h2>
            <p class="an-period__range">
                {{ $period->rangeLabel() }}
                <span class="an-period__previous">
                    {{ __('analytics.period.previous_range', [
                        'from' => $previous->start->locale(app()->getLocale())->translatedFormat('M j, Y'),
                        'to' => $previous->end->locale(app()->getLocale())->translatedFormat('M j, Y'),
                    ]) }}
                </span>
            </p>
        </div>

        <nav class="an-period__nav" aria-label="{{ __('analytics.period.label') }}">
            @foreach($presets as $preset)
                <a
                    href="{{ route($routeNames['page'], array_merge($baseParams, ['period' => $preset])) }}"
                    class="an-period__chip {{ $period->preset === $preset ? 'is-active' : '' }}"
                    @if($period->preset === $preset) aria-current="true" @endif
                >{{ __('analytics.period.'.$preset) }}</a>
            @endforeach

            @if($isCustom)
                <span class="an-period__chip is-active" aria-current="true">{{ __('analytics.period.custom') }}</span>
            @endif
        </nav>
    </div>

    <form
        class="an-period__custom"
        method="GET"
        action="{{ route($routeNames['page'], $baseParams) }}"
        data-analytics-range
        aria-label="{{ __('analytics.period.custom_range') }}"
    >
        <input type="hidden" name="period" value="custom">

        <label class="an-period__field">
            <span>{{ __('analytics.period.from') }}</span>
            <input type="date" name="from" value="{{ $period->start->toDateString() }}" max="{{ now()->toDateString() }}">
        </label>

        <label class="an-period__field">
            <span>{{ __('analytics.period.to') }}</span>
            <input type="date" name="to" value="{{ $period->end->toDateString() }}" max="{{ now()->toDateString() }}">
        </label>

        <button type="submit" class="an-button an-button--secondary an-button--compact">{{ __('analytics.period.apply') }}</button>
    </form>
</section>
