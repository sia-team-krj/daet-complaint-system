{{--
    The on-field officer's home — the mobile task dashboard at /staff.

    A landing page, not a workflow: it lists the work already scoped to this
    account and deep-links into the same ticket every other staff screen uses.
    Nothing here writes, and nothing here grants.

    Layout follows the field reference: a centred rounded card, a header row
    (title left, live-count badge right), compact task cards, a 2x2 task-action
    grid, then the shell's fixed tab bar and footer caption.

    See docs/design-system.md §5.62.
--}}
@extends('staff.layouts.app')

@section('title', __('staff.field.page_title'))
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="field-home">
    <div class="field-home__card">

        {{-- Header row: the section title on the left, how much work is live
             on the right. The badge counts the same rows the list renders,
             so the number and the cards can never disagree. --}}
        <header class="field-home__head">
            <h1 class="field-home__title">{{ __('staff.field.my_tasks') }}</h1>
            <span class="field-home__badge">{{ __('staff.field.active_count', ['count' => $activeCount]) }}</span>
        </header>

        <ul class="field-home__tasks">
            @forelse($tasks as $task)
                @php
                    $statusKey = \Illuminate\Support\Str::slug((string) $task->status, '_');
                @endphp
                <li class="field-task">
                    <div class="field-task__head">
                        <h2 class="field-task__title">{{ \Illuminate\Support\Str::limit($task->title, 80) }}</h2>
                        <span class="status-pill {{ $statusKey }}">
                            <span class="status-pill__dot" aria-hidden="true"></span>
                            {{ $task->statusEnum->label() }}
                        </span>
                    </div>

                    <p class="field-task__id">{{ $task->ticket_id }}</p>

                    @if(filled($task->barangay ?? null))
                        <p class="field-task__loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $task->barangay }}</span>
                        </p>
                    @endif

                    <hr class="field-home__rule">

                    <p class="field-task__desc">{{ \Illuminate\Support\Str::limit((string) $task->description, 180) }}</p>

                    <a class="field-task__open" href="{{ route('staff.complaints.show', $task) }}">
                        <span>{{ __('staff.field.open_task') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </li>
            @empty
                <li class="empty-state">
                    <span class="empty-state__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                    </span>
                    <strong>{{ __('staff.empty_my_work_title') }}</strong>
                    <p>{{ __('staff.empty_my_work_desc') }}</p>
                </li>
            @endforelse
        </ul>

        @if($hasMore)
            <a class="field-home__more" href="{{ route('staff.complaints.index', ['assigned_staff_id' => $user->id, 'scope' => 'all']) }}">
                {{ __('staff.field.view_all', ['count' => $activeCount]) }}
            </a>
        @endif

    </div>
</div>
@endsection

{{-- Rides beneath the four tab items in the shell's fixed bar. --}}
@section('staff-footer', __('staff.field.footer_caption'))
