{{--
    The active filters, as removable chips.

    Swapped on its own when a filter changes, so the summary can appear and
    disappear without redrawing the page.
--}}
@if($activeFilters->isNotEmpty())
    <div class="admin-filter-summary" role="status">
        <span class="admin-filter-summary__label">Filtering by</span>
        @foreach($activeFilters as $key => $value)
            @php $remaining = request()->except($key); @endphp
            <a href="{{ route('admin.complaints.index', $remaining) }}" class="admin-filter-summary__chip">{{ $value }}<span aria-hidden="true"> ×</span><span class="sr-only">Remove this filter</span></a>
        @endforeach
        <a href="{{ route('admin.complaints.index') }}" class="admin-filter-summary__clear">Clear all</a>
    </div>
@endif
