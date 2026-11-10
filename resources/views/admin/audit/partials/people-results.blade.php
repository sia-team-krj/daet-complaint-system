{{--
    People search results in the audit directory.

    Rendered by the page (server-side, works without JavaScript) and by the
    JSON endpoint the type-ahead calls, so the markup lives in one place.
--}}
<div class="admin-audit-browser__results">
    <p class="admin-audit-browser__results-head">
        @if($total > 0)
            {{ trans_choice('audit.people_matches', $total, ['query' => $query]) }}
        @else
            {{ __('audit.no_person_matches', ['query' => $query]) }}
        @endif
    </p>

    @if($total > 0)
        @include('admin.audit.partials.roster-head')
        <div class="admin-audit-browser__results-list">
            @foreach($rows as $row)
                @include('admin.audit.partials.person-row', [
                    'row' => $row,
                    'secondary' => trim(($row['role'] ?? '').(! empty($row['office']) ? ' · '.$row['office'] : '')),
                ])
            @endforeach
        </div>
    @else
        <p class="admin-audit-browser__empty">{{ __('audit.no_people_hint') }}</p>
    @endif
</div>
