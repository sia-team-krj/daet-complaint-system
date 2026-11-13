@php
    $type = $type ?? 'count';
    $currentValue = $current === null ? null : (float) $current;
    $previousValue = $previous === null ? null : (float) $previous;
    $direction = 'flat';
    $label = null;

    if ($currentValue !== null && $previousValue !== null) {
        if ($type === 'days') {
            $change = round($currentValue - $previousValue, 1);
            if ($change > 0) $direction = 'up';
            elseif ($change < 0) $direction = 'down';
            $label = $change == 0.0
                ? __('analytics.delta.flat')
                : __('analytics.delta.days', ['value' => ($change > 0 ? '+' : '').number_format($change, 1)]);
        } elseif ($type === 'rate') {
            $change = round($currentValue - $previousValue, 1);
            if ($change > 0) $direction = 'up';
            elseif ($change < 0) $direction = 'down';
            $label = $change == 0.0
                ? __('analytics.delta.flat')
                : __('analytics.delta.points', ['value' => ($change > 0 ? '+' : '').number_format($change, 1)]);
        } elseif ($previousValue == 0.0) {
            if ($currentValue > 0) {
                $direction = 'up';
                $label = __('analytics.delta.new');
            } else {
                $label = __('analytics.delta.flat');
            }
        } else {
            $change = (($currentValue - $previousValue) / abs($previousValue)) * 100;
            if ($change > 0) $direction = 'up';
            elseif ($change < 0) $direction = 'down';
            $label = __('analytics.delta.count', ['value' => ($change > 0 ? '+' : '').number_format($change, 0).'%']);
        }
    }
@endphp

@if($label !== null)
    <span class="an-delta an-delta--{{ $direction }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            @if($direction === 'up')
                <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
            @elseif($direction === 'down')
                <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>
            @else
                <line x1="5" y1="12" x2="19" y2="12"/>
            @endif
        </svg>
        <span>{{ $label }}</span>
        <span class="an-delta__label">{{ __('analytics.delta.label') }}</span>
    </span>
@endif
