{{--
    Resident complaint pagination — matches the `.tr-pagination` CSS in
    `resources/views/complaints/index.blade.php` so Next/Prev/pages render on
    mobile and desktop in the project's own tokens (not Laravel's default
    Tailwind-utility pagination view).
--}}
@if ($paginator->hasPages())
    <nav class="tr-pagination-nav" role="navigation" aria-label="{{ $ariaLabel ?? __('pagination.label') }}">
        <ul>
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="disabled"><span aria-disabled="true" aria-label="Previous page">{{ __('pagination.previous') }}</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">{{ __('pagination.previous') }}</a></li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="tr-pagination-gap" aria-hidden="true">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">{{ __('pagination.next') }}</a></li>
            @else
                <li class="disabled"><span aria-disabled="true" aria-label="Next page">{{ __('pagination.next') }}</span></li>
            @endif
        </ul>
    </nav>
@endif
