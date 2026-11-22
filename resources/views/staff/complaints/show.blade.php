@extends('staff.layouts.app')
@section('title', $complaint->ticket_id . ' - Complaint Investigation Workspace')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
@include('staff.complaints.partials.show.hero')
@include('staff.complaints.partials.show.main-column')
@include('staff.complaints.partials.show.side-column')
@include('staff.complaints.partials.show.tail')
@endsection

@include('staff.complaints.partials.show.scripts')
