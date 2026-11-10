{{--
    The staff queue's pager. Swapped with the rows, because the number of pages
    moves with them.
--}}
            <div class="staff-pagination-container">
                <div class="staff-pagination-info">
                    <strong>{{ $total }}</strong> {{ \Illuminate\Support\Str::plural('complaint', $total) }} found
                    @if($total > 0)
                        <span class="pagination-range">· Showing {{ $from }}–{{ $to }} of {{ $total }} · {{ $perPage }} per page</span>
                    @endif
                </div>

                @if($complaints->hasPages())
                    {{-- Desktop / Tablet Numbered Pagination --}}
                    <div class="staff-pagination-desktop">
                        {{ $complaints->appends(request()->query())->links('pagination::workspace', ['ariaLabel' => __('staff.pagination_label', [], null) ?: 'Complaint queue pagination']) }}
                    </div>

                    {{-- Mobile Compact Pagination --}}
                    <div class="staff-pagination-mobile">
                        @if($complaints->onFirstPage())
                            <button type="button" class="staff-page-btn staff-page-btn--disabled" disabled>Previous</button>
                        @else
                            <a href="{{ $complaints->previousPageUrl() }}" class="staff-page-btn">Previous</a>
                        @endif

                        <span class="staff-page-indicator">
                            Page {{ $currentPage }} of {{ $complaints->lastPage() }}
                        </span>

                        @if($complaints->hasMorePages())
                            <a href="{{ $complaints->nextPageUrl() }}" class="staff-page-btn">Next</a>
                        @else
                            <button type="button" class="staff-page-btn staff-page-btn--disabled" disabled>Next</button>
                        @endif
                    </div>
                @endif
            </div>
