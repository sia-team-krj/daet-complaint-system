{{--
    One person in the audit directory.

    Used by the roster, by an expanded group and by the search results, so the
    row exists in exactly one file. $row carries id, name, initials, role,
    office, count and deleted; $auditQuery carries the trail filters that must
    survive the selection; $secondary is an optional label shown after the
    name (the office in search results, the role inside an office group).
--}}
@php
    $isSelected = $actorId !== null && (int) $actorId === (int) $row['id'];
    $secondary = $secondary ?? ($row['role'] ?? '');
@endphp
<a href="{{ route('admin.audit.index', array_merge($auditQuery, ['actor_id' => $row['id']])) }}"
   class="admin-audit-browser__person{{ $isSelected ? ' is-selected' : '' }}"
   @if($isSelected) aria-current="true" @endif>
    <span class="admin-audit-browser__avatar" aria-hidden="true">{{ $row['initials'] }}</span>
    <span class="admin-audit-browser__person-name">
        {{ $row['name'] }}
        @if(! empty($row['deleted']))
            <span class="admin-audit-browser__person-flag">{{ __('audit.former_account') }}</span>
        @endif
    </span>
    @if($secondary)
        <span class="admin-audit-browser__person-role">{{ $secondary }}</span>
    @endif
    <span class="admin-audit-browser__person-count">{{ $row['count'] }}<span class="sr-only"> {{ trans_choice('audit.entry_unit', $row['count']) }}</span></span>
</a>
