@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $query = array_merge($auditQuery, $selectionParams, ['people_group' => $group['key']]);
    $path = route('admin.audit.index', [], false);
    $paginator = new LengthAwarePaginator([], $group['people'], \App\Services\AuditDirectory::PAGE_ROWS, $group['page'], [
        'path' => $path,
        'pageName' => 'people_page',
        'query' => $query,
    ]);
    $paginator->withPath($path);
    $peoplePages = max(1, (int) ceil($group['people'] / \App\Services\AuditDirectory::PAGE_ROWS));
@endphp

<div class="admin-pagination-wrap admin-pagination-wrap--compact">
    <p class="admin-pager-summary" aria-live="polite">{{ __('audit.page_of', ['current' => $group['page'], 'last' => $peoplePages]) }} · {{ __('audit.people_page_size', ['count' => \App\Services\AuditDirectory::PAGE_ROWS]) }}</p>
    <div class="admin-pagination">{{ $paginator->links('pagination::workspace', ['ariaLabel' => __('pagination.label')]) }}</div>
</div>
