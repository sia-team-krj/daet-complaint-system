{{--
    The roster, or the honest prompt that points to it.

    “Everyone” shows counts and the search first. The directory opens its
    rows only when the admin chooses a scope, expands a group, or searches
    for a person by name.
--}}
@php
    $auditQuery = $auditQuery ?? [];
    $selectionParams = array_filter($selectionParams ?? [], fn ($value) => filled($value));
    $actorId = $actorId ?? null;
@endphp
@if($searchResults !== null)
    @include('admin.audit.partials.people-results', [
        'rows' => $searchResults['rows'],
        'total' => $searchResults['total'],
        'query' => $peopleQuery,
        'auditQuery' => $auditQuery,
        'actorId' => $actorId,
    ])
@elseif(($selection['active'] ?? false) && ($selection['type'] ?? '') === 'actor')
    @include('admin.audit.partials.selected-person', ['selection' => $selection])
@elseif(! ($selection['active'] ?? false) && $expandedGroup === null)
    @include('admin.audit.partials.directory-prompt')
@else
    @include('admin.audit.partials.roster', [
        'groups' => $groups,
        'auditQuery' => $auditQuery,
        'selectionParams' => $selectionParams,
        'actorId' => $actorId,
    ])
@endif
