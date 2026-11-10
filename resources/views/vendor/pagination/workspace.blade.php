{{--
    Operator workspace pagination - shared by the admin and staff surfaces.

    Laravel's default `pagination::tailwind` view ships utility-class markup for
    two separate control sets and light-mode `bg-white border-gray-300` styling,
    neither of which belongs on the dark operator surfaces. This view renders a
    single control set in the project's own classes so every operator queue
    paginates identically at five rows per page.
--}}
@if ($paginator->hasPages())
    <nav class="admin-pager" role="navigation" aria-label="{{ $ariaLabel ?? (__('pagination.label', [], null) ?: 'Pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="admin-pager-step is-disabled" aria-disabled="true" aria-label="Previous page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <span>{{ __('pagination.previous', [], null) ?: 'Previous' }}</span>
            </span>
        @else
            <a class="admin-pager-step" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <span>{{ __('pagination.previous', [], null) ?: 'Previous' }}</span>
            </a>
        @endif

        <ol class="admin-pager-pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="admin-pager-gap" aria-hidden="true">{{ $element }}</li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="admin-pager-page is-current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="admin-pager-page" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ol>

        @if ($paginator->hasMorePages())
            <a class="admin-pager-step" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                <span>{{ __('pagination.next', [], null) ?: 'Next' }}</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        @else
            <span class="admin-pager-step is-disabled" aria-disabled="true" aria-label="Next page">
                <span>{{ __('pagination.next', [], null) ?: 'Next' }}</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </span>
        @endif
    </nav>
@endif
