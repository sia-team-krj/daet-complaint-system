@extends('layouts.app')
@section('title', __('identity.verify.title').' - Daet Listens')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@section('content')
@php
    // Resident-facing status of the verification flow, derived from the same
    // source of truth the gating uses: the resident's own verified flag, else
    // the latest verification row's lifecycle state.
    $identityStatus = 'unverified';
    $identityStatusPill = 'status-pill--unverified';
    $identityStatusLabel = __('identity.verify.unverified_title');
    // The page lede already carries the "upload to verify" nudge, so the status
    // line for the default state stays pill-only — no duplicated copy.
    $identityStatusNote = '';

    if ($user->isIdentityVerified()) {
        $identityStatus = 'verified';
        $identityStatusPill = 'verified';
        $identityStatusLabel = __('identity.verify.verified_title');
        $identityStatusNote = __('identity.verify.verified_lede');
    } elseif ($verification?->isPending()) {
        $identityStatus = 'pending';
        $identityStatusPill = 'pending';
        $identityStatusLabel = __('identity.verify.pending_title');
        $identityStatusNote = __('identity.verify.pending_lede');
    } elseif ($verification?->isRejected()) {
        $identityStatus = 'rejected';
        $identityStatusPill = 'rejected';
        $identityStatusLabel = __('identity.verify.rejected_title');
        // The specific reason lives on the card; the status line keeps the
        // status-level copy so nothing is repeated.
        $identityStatusNote = __('identity.verify.rejected_lede');
    } elseif ($verification?->isCancelled()) {
        $identityStatus = 'cancelled';
        $identityStatusPill = 'status-pill--unverified';
        $identityStatusLabel = __('identity.verify.cancelled_title');
        $identityStatusNote = __('identity.verify.cancelled_lede');
    }

    // The compact three-step progress of the verification flow: ID information,
    // upload the photos, confirm and submit. States come from the resident's
    // real position in that flow — never from a guess.
    $identitySteps = [
        ['key' => 'info', 'state' => 'current', 'label' => __('identity.verify.step_id_info')],
        ['key' => 'photos', 'state' => 'todo', 'label' => __('identity.verify.step_upload_photos')],
        ['key' => 'confirm', 'state' => 'todo', 'label' => __('identity.verify.step_confirmation')],
    ];

    if ($identityStatus === 'verified' || $identityStatus === 'pending' || $identityStatus === 'cancelled') {
        // Verified, pending and cancelled flows all sit past the upload step.
        $identitySteps = [
            ['key' => 'info', 'state' => 'done', 'label' => __('identity.verify.step_id_info')],
            ['key' => 'photos', 'state' => 'done', 'label' => __('identity.verify.step_upload_photos')],
            ['key' => 'confirm', 'state' => 'done', 'label' => __('identity.verify.step_confirmation')],
        ];
    } elseif ($identityStatus === 'rejected') {
        $identitySteps = [
            ['key' => 'info', 'state' => 'done', 'label' => __('identity.verify.step_id_info')],
            ['key' => 'photos', 'state' => 'current', 'label' => __('identity.verify.step_upload_photos')],
            ['key' => 'confirm', 'state' => 'todo', 'label' => __('identity.verify.step_confirmation')],
        ];
    }
@endphp
@section('content')
<div class="identity-verify settings-page">
    <div class="tr-container settings-container">
        <header class="settings-header">
            <h1 class="tr-h1 settings-header__title">{{ __('identity.verify.title') }}</h1>
            <p class="tr-privacy settings-header__lede">{{ __('identity.verify.lede') }}</p>

            <div class="identity-status">
                <span class="status-pill {{ $identityStatusPill }}" data-identity-status>
                    <span class="status-pill__dot" aria-hidden="true"></span>
                    <span>{{ $identityStatusLabel }}</span>
                </span>
                @if($identityStatusNote)
                    <p class="identity-status__note">{{ $identityStatusNote }}</p>
                @endif
            </div>

            <ol class="identity-steps">
                @foreach($identitySteps as $step)
                    <li class="identity-steps__item identity-steps__item--{{ $step['state'] }}{{ $step['state'] === 'current' ? ' is-current' : '' }}" @if($step['state'] === 'current') aria-current="step" @endif>
                        <span class="identity-steps__marker" aria-hidden="true">{{ $step['state'] === 'done' ? '✓' : (string) $loop->iteration }}</span>
                        <span class="identity-steps__label">{{ __($step['label']) }}</span>
                    </li>
                @endforeach
            </ol>
        </header>

        @if(session('status'))
            <x-toast :message="session('status')" />
        @endif
        @if(session('message'))
            <x-toast :message="session('message')" />
        @endif
        @if(session('error'))
            <x-toast :message="session('error')" :variant="'error'" />
        @endif

        <section class="tr-card settings-card">
            @if($user->isIdentityVerified())
                <div class="settings-field">
                    <a href="{{ route('complaints.create') }}" class="tr-button tr-button--primary">{{ __('my_complaints.file_cta') }}</a>
                </div>
            @elseif($verification?->isPending())
                <form data-loading-submit method="POST" action="{{ route('identity.verify.cancel') }}">
                    @csrf
                    <div class="settings-save-row">
                        <div class="settings-save-row__actions">
                            <button type="submit" class="tr-button tr-button--secondary">{{ __('identity.verify.cancel') }}</button>
                        </div>
                    </div>
                </form>
            @elseif($verification?->isRejected())
                <header class="settings-card__head">
                    <h2 class="settings-card__title">{{ __('identity.verify.rejected_title') }}</h2>
                    <p class="settings-card__lede">{{ $rejectionReason ?: __('identity.verify.rejected_lede') }}</p>
                </header>
                @include('identity.verify.form')
            @elseif($verification?->isCancelled())
                @include('identity.verify.form')
            @else()
                @include('identity.verify.form')
            @endif
        </section>
    </div>
</div>
@endsection
