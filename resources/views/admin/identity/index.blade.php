@extends('admin.layouts.app')

@section('title', 'Identity verification - Admin')
@section('admin-content')
<div class="admin-page admin-identity-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('identity.admin.queue_title') }}</h1>
            <p class="admin-page-description">{{ __('identity.admin.queue_lede') }}</p>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Back to users</a>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-panel" aria-label="Identity queue">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">{{ __('identity.admin.queue_title') }}</h2>
            <span class="admin-status admin-status--pending">{{ $pendingCount }} pending</span>
        </div>
        <div class="admin-panel-body">
            <form method="GET" action="{{ route('admin.identity.index') }}" class="admin-form-row admin-identity-filters" id="admin-identity-filters">
                <label class="sr-only" for="search">{{ __('identity.admin.search_placeholder') }}</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="{{ __('identity.admin.search_placeholder') }}" class="admin-input" aria-label="{{ __('identity.admin.search_placeholder') }}" aria-describedby="search-live">
                <select name="status" class="admin-input" aria-label="{{ __('identity.admin.status') }}">
                    <option value="">{{ __('identity.admin.status') }}: {{ __('identity.admin.pending') }}</option>
                    <option value="pending">{{ __('identity.admin.pending') }}</option>
                    <option value="approved">{{ __('identity.admin.approved') }}</option>
                    <option value="rejected">{{ __('identity.admin.rejected') }}</option>
                    <option value="revoked">{{ __('identity.admin.revoked') }}</option>
                    <option value="expired">{{ __('identity.admin.expired') }}</option>
                    <option value="cancelled">{{ __('identity.admin.cancelled') }}</option>
                </select>
                <p id="search-live" class="sr-only" aria-live="polite">Filtering the identity queue.</p>
            </form>

            @if($verifications->isEmpty())
                <div class="admin-empty-state">
                    <div class="admin-empty-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0c0 7-3 9-3 9h18s-3-2-3-9h18s-3-2-3-9h18s-3-2-3-9h18s-3-2-3-9"></path></svg></div>
                    <h3>No submissions match this filter</h3>
                    <p>Clear filters to see the full queue.</p>
                </div>
            @else
                <div class="admin-table-wrap" aria-label="Identity queue">
                    <table class="admin-table admin-user-table">
                        <caption class="sr-only">Identity verification queue</caption>
                        <thead>
                            <tr>
                                <th scope="col">Resident</th>
                                <th scope="col">ID type</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col">Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($verifications as $v)
                                <tr>
                                    <td data-label="Resident">
                                        <a href="{{ route('admin.identity.show', $v) }}" class="admin-user-name">{{ $v->user->full_name }}</a>
                                        <small class="admin-table-subtext">{{ $v->user->email }}</small>
                                    </td>
                                    <td data-label="ID type">{{ $v->id_type }}</td>
                                    <td data-label="Status">
                                        <span @class(['admin-status', 'admin-status--pending' => $v->status === 'pending', 'admin-status--verified' => $v->status === 'approved', 'admin-status--rejected' => $v->status === 'rejected', 'admin-status--revoked' => $v->status === 'revoked', 'admin-status--expired' => $v->status === 'expired'])>{{ __('identity.admin.'.$v->status) }}</span>
                                    </td>
                                    <td data-label="Submitted">{{ $v->submitted_at?->format('M j, Y') }}</td>
                                    <td data-label="Review">
                                        <a href="{{ route('admin.identity.show', $v) }}" class="admin-button admin-button--compact admin-button--secondary">{{ __('identity.admin.review') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="admin-pagination-wrap">
                    <p class="admin-pager-summary" aria-live="polite">{{ __('pagination.page_of', ['page' => $verifications->currentPage(), 'last' => $verifications->lastPage(), 'count' => $verifications->perPage()]) }}</p>
                    <div class="admin-pagination">{{ $verifications->links('pagination::workspace', ['ariaLabel' => __('pagination.label')]) }}</div>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
