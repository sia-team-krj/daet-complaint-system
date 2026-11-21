@extends('admin.layouts.app')

@section('title', 'Review identity submission - Admin')
@section('admin-content')
<div class="admin-page admin-identity-review-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('identity.admin.review') }}</h1>
            <p class="admin-page-description">{{ __('identity.admin.show_title') }}</p>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('admin.identity.index') }}" class="admin-button admin-button--secondary">{{ __('identity.admin.back_to_queue') }}</a>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-panel" aria-label="Identity submission">
        <div class="admin-panel-heading">
            <div>
                <h2 class="admin-panel-title">{{ $verification->user->full_name }}</h2>
                <span class="admin-muted-cell">{{ $verification->user->email }}</span>
            </div>
            <span @class(['admin-status', 'admin-status--pending' => $verification->status === 'pending', 'admin-status--verified' => $verification->status === 'approved', 'admin-status--rejected' => $verification->status === 'rejected', 'admin-status--revoked' => $verification->status === 'revoked', 'admin-status--expired' => $verification->status === 'expired'])>{{ __('identity.admin.'.$verification->status) }}</span>
        </div>

        <div class="admin-panel-body">
            <dl class="admin-detail-list admin-user-detail-list">
                <div><dt>ID type</dt><dd>{{ $verification->id_type }}</dd></div>
                <div><dt>ID number hash</dt><dd><code>{{ $verification->id_number_hash }}</code></dd></div>
                <div><dt>Submitted</dt><dd>{{ $verification->submitted_at?->format('M j, Y g:i A') }}</dd></div>
                <div><dt>Status</dt><dd>{{ __('identity.admin.'.$verification->status) }}</dd></div>
            </dl>

            @if($verification->possibleDuplicate)
                <div class="admin-status admin-status--pending">
                    {{ __('identity.admin.possible_duplicate', ['name' => $verification->possibleDuplicate->full_name, 'email' => $verification->possibleDuplicate->email]) }}
                </div>
            @endif

            @if($verification->isRejected() && $verification->rejection_reason)
                <p class="admin-muted-cell">{{ $verification->rejection_reason }}</p>
            @endif

            <div class="admin-identity-photos">
                <figure>
                    <img src="{{ route('admin.identity.document', ['verification' => $verification, 'side' => 'id_front']) }}" alt="{{ $verification->user->full_name }} — {{ __('identity.admin.id_front') }}" width="320" loading="lazy">
                    <figcaption class="admin-table-subtext">{{ __('identity.admin.id_front') }}</figcaption>
                </figure>
                <figure>
                    <img src="{{ route('admin.identity.document', ['verification' => $verification, 'side' => 'selfie']) }}" alt="{{ $verification->user->full_name }} — {{ __('identity.admin.selfie') }}" width="320" loading="lazy">
                    <figcaption class="admin-table-subtext">{{ __('identity.admin.selfie') }}</figcaption>
                </figure>
            </div>

            <form method="POST" action="{{ route('admin.identity.approve', $verification) }}" class="admin-identity-actions" data-confirm="{{ __('identity.admin.review') }} — confirm you compared the face and the ID?">
                @csrf
                <button type="submit" class="admin-button admin-button--primary">{{ __('identity.admin.approvable') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.identity.reject', $verification) }}" class="admin-identity-actions" onsubmit="return confirm('{{ __('identity.admin.rejectable') }} — {{ $verification->user->full_name }}?')">
                @csrf
                <input type="text" name="reason" placeholder="{{ __('identity.admin.rejection_reason') }}" class="admin-input" required aria-label="{{ __('identity.admin.rejection_reason') }}">
                <button type="submit" class="admin-button admin-button--danger">{{ __('identity.admin.rejectable') }}</button>
            </form>
        </div>
    </section>
</div>
@endsection
