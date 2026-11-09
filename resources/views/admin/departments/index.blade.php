@extends('admin.layouts.app')

@section('title', 'Departments - Admin')
@section('admin-content')
<div class="admin-page admin-page--directory">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Departments</h1>
            <p class="admin-page-description">Maintain the municipal offices that receive, route, and resolve public complaints.</p>
        </div>

        {{-- Creation is its own page. On a phone the form used to sit below the
             whole directory, so the only way to add an office was to scroll past
             every office first. --}}
        <a href="{{ route('admin.departments.create') }}" class="admin-button admin-button--primary">Add department</a>
    </header>

    @include('admin.partials.flash')

    {{-- Archive is a filter, not a second list. An admin looking for an office
         they retired needs it in the same place, and a running total tells them
         something is hidden before they go looking. --}}
    <div class="admin-department-filters" role="group" aria-label="Department status filter">
        <a href="{{ route('admin.departments.index') }}"
           class="admin-filter-chip {{ $showArchived ? '' : 'is-active' }}"
           @if(! $showArchived) aria-current="true" @endif>
            Active
            <span class="admin-filter-chip__count">{{ $activeCount }}</span>
        </a>
        <a href="{{ route('admin.departments.index', ['archived' => 1]) }}"
           class="admin-filter-chip {{ $showArchived ? 'is-active' : '' }}"
           @if($showArchived) aria-current="true" @endif>
            Archived
            <span class="admin-filter-chip__count">{{ $archivedCount }}</span>
        </a>
    </div>

    <section class="admin-section admin-section--flush" aria-labelledby="department-list-title">
            <div class="admin-section-heading">
                <div>
                    <h2 id="department-list-title" class="admin-section-title">Department directory</h2>
                    <p class="admin-section-description">
                        {{ $showArchived
                            ? 'Archived offices. They keep their complaint history and can be restored.'
                            : 'Active offices available for complaint routing and staff invitations. Tap an office for its queue, staffing, and controls.' }}
                    </p>
                </div>
            </div>

            {{--
                Every row is collapsed by default.

                This is the whole fix for the phone. Showing description, three
                stats, and four buttons on all eight rows unconditionally cost
                roughly 3,000px - about four phone screens to read a list of
                eight names. Collapsed, a row is a single line carrying the one
                thing you scan for (open work), and everything else waits for a
                tap. It reads better on a laptop too: the whole directory fits
                on one screen instead of two.

                Native <details> rather than a script, so disclosure semantics,
                keyboard operation, and the no-JavaScript path all come free.
            --}}
            <div class="admin-department-list">
                @forelse($departments as $department)
                    <details class="admin-dept {{ $department->is_active ? '' : 'is-archived' }}" data-dept="{{ $department->code }}">
                        <summary class="admin-dept__summary">
                            <span class="admin-department-code" data-dept="{{ $department->code }}">{{ $department->code }}</span>
                            <span class="admin-dept__name">{{ $department->name }}</span>

                            {{-- Only when there is something to act on. A "0 open"
                                 pill on every office is noise that trains the eye
                                 to skip the one office that matters. --}}
                            @if($department->open_complaints_count > 0)
                                <span class="admin-dept__open">{{ $department->open_complaints_count }} open</span>
                            @endif

                            <span class="admin-status {{ $department->is_active ? 'admin-status--active' : 'admin-status--revoked' }}">{{ $department->is_active ? 'Active' : 'Archived' }}</span>

                            <svg class="admin-dept__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </summary>

                        <div class="admin-dept__body">
                            <p class="admin-dept__description">{{ $department->description ?: 'No office description has been added.' }}</p>

                            <dl class="admin-inline-stats">
                                <div><dt>Open complaints</dt><dd>{{ $department->open_complaints_count }}</dd></div>
                                <div><dt>Staff accounts</dt><dd>{{ $department->staff_count }}</dd></div>
                                <div><dt>Active invitations</dt><dd>{{ $department->active_invitations_count }}</dd></div>
                            </dl>

                            {{-- Every action on this row lives in one group. Edit sits
                                 with View queue and Archive rather than floating above
                                 them: a lone control between the stats and the action
                                 row reads as an orphan, not one of this office's commands. --}}
                            <div class="admin-department-list-actions">
                                @if($department->is_active)
                                    <a href="{{ route('admin.complaints.index', ['department' => $department->id]) }}" class="admin-button admin-button--compact admin-button--secondary">View queue</a>
                                    <a href="{{ route('admin.invitations.index') }}" class="admin-button admin-button--compact admin-button--secondary">Invite staff</a>
                                @else
                                    <a href="{{ route('admin.complaints.index', ['department' => $department->id]) }}" class="admin-button admin-button--compact admin-button--secondary">View history</a>
                                @endif


                                @if($department->is_active)
                                    {{-- Two-step, inline. Archiving is reversible but it
                                         changes where work goes, so the consequence is
                                         stated before the button that causes it -
                                         without a dialog to dismiss. --}}
                                    <details class="admin-archive">
                                        <summary class="admin-button admin-button--compact admin-button--danger-ghost">Archive</summary>
                                        <div class="admin-archive__confirm">
                                            <p>
                                                Stop routing new complaints to <strong>{{ $department->name }}</strong>?
                                                @if($department->open_complaints_count > 0)
                                                    Its {{ $department->open_complaints_count }} open
                                                    {{ Str::plural('complaint', $department->open_complaints_count) }} will need
                                                    reassigning first.
                                                @else
                                                    It has no open complaints.
                                                @endif
                                                Its full history stays intact, and you can restore it later.
                                            </p>
                                            <div class="admin-form-actions">
                                                <form data-loading-submit method="POST" action="{{ route('admin.departments.archive', $department) }}">
                                                    @csrf
                                                    <button type="submit" class="admin-button admin-button--compact admin-button--danger-ghost"
                                                        @disabled($department->open_complaints_count > 0)
                                                        title="Reassign the open complaints before archiving.">
                                                        Archive office
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                @else
                                    <form data-loading-submit method="POST" action="{{ route('admin.departments.restore', $department) }}">
                                        @csrf
                                        <button type="submit" class="admin-button admin-button--compact admin-button--primary">Restore</button>
                                    </form>
                                @endif
                                <details class="admin-department-editor">
                                    <summary class="admin-button admin-button--compact admin-button--secondary">Edit</summary>

                                    <form data-loading-submit method="POST" action="{{ route('admin.departments.update', $department) }}" class="admin-form-stack">
                                        @csrf
                                        @method('PUT')

                                        <label class="admin-field">
                                            <span>Office name</span>
                                            <input type="text" name="name" class="admin-input" value="{{ old('name', $department->name) }}" required maxlength="100">
                                            @error('name')<span class="admin-field-error">{{ $message }}</span>@enderror
                                        </label>

                                        <label class="admin-field">
                                            <span>Office code</span>
                                            <input type="text" name="code" class="admin-input" value="{{ old('code', $department->code) }}" required maxlength="10" pattern="[A-Z0-9]+"
                                                   @disabled(in_array($department->code, $routableCodes, true))>
                                            @error('code')<span class="admin-field-error">{{ $message }}</span>@enderror
                                            @if(in_array($department->code, $routableCodes, true))
                                                {{-- Stated, not hidden. A disabled input with no
                                                     explanation reads as a bug; this one says
                                                     why and what to do instead. --}}
                                                <span class="admin-field-hint">
                                                    Locked. This code routes complaint categories automatically, so renaming it
                                                    would strand every category mapped to it. Archive this office and create a
                                                    replacement if the code has to change.
                                                </span>
                                            @else
                                                <span class="admin-field-hint">Short uppercase identifier, e.g. OM. Used in filters and exports.</span>
                                            @endif
                                        </label>

                                        <label class="admin-field">
                                            <span>Description</span>
                                            <textarea name="description" class="admin-input admin-textarea" rows="3" maxlength="255" placeholder="Services handled by this office.">{{ old('description', $department->description) }}</textarea>
                                            @error('description')<span class="admin-field-error">{{ $message }}</span>@enderror
                                        </label>

                                        <div class="admin-form-actions">
                                            <button type="submit" class="admin-button admin-button--primary admin-button--compact">Save changes</button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        </div>
                    </details>
                @empty
                    <div class="admin-empty-state">
                        <p>
                            @if($showArchived)
                                No departments have been archived.
                            @else
                                No active departments. Create one to start routing complaints.
                            @endif
                        </p>
                        @if($showArchived)
                            <a href="{{ route('admin.departments.index') }}" class="admin-button admin-button--compact admin-button--secondary">Back to active offices</a>
                        @else
                            <a href="{{ route('admin.departments.create') }}" class="admin-button admin-button--primary">Create the first department</a>
                        @endif
                    </div>
                @endforelse
            </div>

            @if($departments->hasPages())
                <div class="admin-pagination">{{ $departments->links() }}</div>
            @endif
        </section>

    </div>
</div>
@endsection