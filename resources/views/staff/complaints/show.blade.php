@extends('staff.layouts.app')
@section('title', $complaint->ticket_id . ' - Complaint Investigation Workspace')
@section('body-class', 'staff-field-ticket')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

{{-- Field workspace: while the ticket is open, the staff bottom nav is
     replaced by the four quick actions — the officer's ways in are the nav.
     Same controls, same anchors as the ticket's update form; the location
     shortcut drops because the ticket's own map carries it. --}}
@if(($workspaceMode ?? 'verifier') === 'field')
    @php
        $isTicketActionable = $complaint->isVerified()
            && $complaint->statusEnum->allowedManualTransitions() !== [];
    @endphp
    @section('staff-bottom-nav')
        @if($isTicketActionable)
            <a class="ticket-action" href="#status">
                <span class="ticket-action__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg></span>
                <span>{{ __('staff.ticket.actions.update_status') }}</span>
            </a>
            <a class="ticket-action" href="#proof-capture">
                <span class="ticket-action__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                <span>{{ __('staff.ticket.actions.take_photo') }}</span>
            </a>
            <a class="ticket-action" href="#complaint-update-form" data-ticket-focus="note">
                <span class="ticket-action__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                <span>{{ __('staff.ticket.actions.add_findings') }}</span>
            </a>
            <a class="ticket-action" href="#complaint-update-form" data-ticket-focus="submit">
                <span class="ticket-action__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg></span>
                <span>{{ __('staff.ticket.actions.submit_report') }}</span>
            </a>
        @else
            <p class="ticket-action-note">
                {{ $complaint->isVerified()
                    ? __('staff.ticket.actions.no_transitions')
                    : __('staff.ticket.actions.awaiting_verification') }}
            </p>
        @endif
        {{-- Location shortcut as a tab — handy while a ticket is open. --}}
        <a class="staff-bottom-nav__item" href="#complaint-location" aria-label="{{ __('staff.location.title') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>{{ __('staff.location.title') }}</span>
        </a>
    @endsection
@endif

@section('staff-content')
@include('staff.complaints.partials.show.hero')
@include('staff.complaints.partials.show.main-column')
@include('staff.complaints.partials.show.side-column')
@include('staff.complaints.partials.show.tail')
@endsection

@include('staff.complaints.partials.show.scripts')
