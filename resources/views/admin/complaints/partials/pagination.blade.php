{{--
    The queue's pager. Swapped with the list when the page changes.
--}}
@if($complaints->hasPages())
    <div class="tr-container tr-pagination">
        <p class="tr-showing">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }} · 5 per page</p>
        <div class="admin-pagination">{{ $complaints->links('pagination::workspace') }}</div>
    </div>
@endif
