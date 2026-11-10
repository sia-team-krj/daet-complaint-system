{{--
    The status chips, with their per-status totals.

    Swapped when a filter changes: the totals reflect the other filters, so
    they have to move with the results.
--}}
<div class="cf-chips-row">
    @php
        $chipBase = array_filter($filters, fn ($v, $k) => ! in_array($k, ['status', 'page'], true) && $v !== '', ARRAY_FILTER_USE_BOTH);
        $chipUrl = fn ($s) => route('transparency.complaints', array_merge($chipBase, $s === '' ? [] : ['status' => $s]));
    @endphp
    <a href="{{ $chipUrl('') }}" class="cf-chip @if($filters['status'] === '') cf-chip--active @endif" @if($filters['status'] === '') aria-current="true" @endif>
        {{ __('transparency.register.all_statuses_with_count', ['n' => array_sum($statusCounts ?? [])]) }}
    </a>
    @foreach($statusOptions as $st)
        @php $cnt = (int) (($statusCounts ?? [])[$st['key']] ?? 0); @endphp
        @if($cnt > 0)
            <a href="{{ $chipUrl($st['key']) }}" class="cf-chip @if($filters['status'] === $st['key']) cf-chip--active @endif" @if($filters['status'] === $st['key']) aria-current="true" @endif>
                {{ $st['label'] }} {{ $cnt }}
            </a>
        @endif
    @endforeach
</div>
