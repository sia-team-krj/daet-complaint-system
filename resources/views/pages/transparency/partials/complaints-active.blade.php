{{--
    The active filters, as removable chips.

    Swapped when a filter changes so the list of what is on stays honest.
--}}
@if($anyFilter)
    <div class="cf-active-chips">
        @if($filters['q'] !== '')
            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['q','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $filters['q']]) }}">
                {{ __('transparency.register.search_label') }}: {{ $filters['q'] }} ×
            </a>
        @endif
        @if($filters['department'] !== '')
            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['department','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $filters['department']]) }}">
                {{ $filters['department'] }} ×
            </a>
        @endif
        @if($filters['category'] !== '')
            @php $catLabel = collect($categoryOptions)->firstWhere('key', $filters['category'])['label'] ?? $filters['category']; @endphp
            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['category','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $catLabel]) }}">
                {{ $catLabel }} ×
            </a>
        @endif
        @if($filters['status'] !== '')
            @php $statusLabel = collect($statusOptions)->firstWhere('key', $filters['status'])['label'] ?? $filters['status']; @endphp
            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['status','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $statusLabel]) }}">
                {{ $statusLabel }} ×
            </a>
        @endif
        <a href="{{ route('transparency.complaints') }}" class="cf-active-chip cf-active-chip--clear">{{ __('transparency.register.clear_all') }}</a>
    </div>
@endif
