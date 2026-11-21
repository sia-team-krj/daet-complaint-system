@extends('staff.layouts.app')

@section('title', __('admin_categories.staff.title').' - Daet Listens')
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <div class="office-banner">
                <div>
                    <h1 class="tr-h1">{{ __('admin_categories.staff.title') }}</h1>
                    <p class="tr-privacy">{{ __('admin_categories.staff.subtitle', ['office' => $office->name]) }}</p>
                </div>
                <a href="{{ route('staff.office.index') }}" class="staff-button staff-button--secondary">
                    {{ __('admin_categories.staff.back') }}
                </a>
            </div>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <div class="cat-page cat-page--staff">
            <p class="cat-scope-note">
                <span class="cat-scope-note__dot" aria-hidden="true"></span>
                {{ __('admin_categories.staff.scope', ['office' => $office->name]) }}
            </p>

            @include('admin.categories.partials.manager')
        </div>
    </div>
</div>
@endsection
