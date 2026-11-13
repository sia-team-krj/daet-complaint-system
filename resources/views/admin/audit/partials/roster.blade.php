{{--
    The roster: people grouped by office, then administration, then residents.

    Every group renders a bounded preview so the list stays short no matter how
    many residents are in the log. A group with more people offers "See all N",
    which opens it in place — 25 people per page, never a dump.
--}}
@include('admin.audit.partials.roster-head')
@forelse($groups as $group)
    @php
        $shown = count($group['rows']);
        $isOffice = $group['type'] === 'department';
        $groupUrl = fn (array $extra = []) => route('admin.audit.index', array_merge($selectionParams, $auditQuery, $extra));
    @endphp

    <section class="admin-audit-browser__group" data-group="{{ $group['key'] }}">
        <p class="admin-audit-browser__group-head">
            <span class="admin-audit-browser__group-name">{{ $group['label'] }}</span>
            <span class="admin-audit-browser__group-meta">{{ trans_choice('audit.people_count', $group['people']) }}</span>
        </p>

        <div class="admin-audit-browser__group-list">
            @foreach($group['rows'] as $row)
                @include('admin.audit.partials.person-row', [
                    'row' => $row,
                    // Inside an office, staff and admins share a list, so the
                    // role is the one fact that tells them apart. Elsewhere the
                    // group heading already says it.
                    'secondary' => $isOffice ? ($row['role'] ?? '') : '',
                ])
            @endforeach
        </div>

        @if($group['expanded'])
            <div class="admin-audit-browser__group-foot">
                <span class="admin-audit-browser__group-shown">{{ __('audit.showing_range', [
                    'from' => $group['from'],
                    'to' => $group['to'],
                    'total' => $group['people'],
                ]) }}</span>

                @if($group['pages'] > 1)
                    @include('admin.audit.partials.group-pager', ['group' => $group])
                @endif

                <a class="admin-audit-browser__group-more" href="{{ $groupUrl() }}">{{ __('audit.collapse') }}</a>
            </div>
        @elseif($group['people'] > $shown)
            <div class="admin-audit-browser__group-foot">
                <span class="admin-audit-browser__group-shown">{{ __('audit.showing_rows', ['shown' => $shown, 'total' => $group['people']]) }}</span>
                <a class="admin-audit-browser__group-more" href="{{ $groupUrl(['people_group' => $group['key'], 'people_page' => 1]) }}">{{ __('audit.see_all', ['count' => $group['people']]) }}</a>
            </div>
        @endif
    </section>
@empty
    <p class="admin-audit-browser__empty">{{ __('audit.no_people') }}</p>
@endforelse
