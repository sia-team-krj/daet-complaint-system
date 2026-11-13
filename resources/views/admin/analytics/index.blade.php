@extends('admin.layouts.app')

@section('title', __('analytics.comparison_title').' - Admin')
@section('body-class', 'admin-layout page-admin-analytics')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('admin-content')
<div
    class="an-page an-page--admin"
    data-analytics
    data-endpoint="{{ $fragmentUrl }}"
    data-page-path="{{ $pagePath }}"
>
    <header class="an-header">
        <div class="an-header__copy">
            <p class="an-kicker">{{ __('analytics.kicker') }}</p>
            <h1 class="an-title">{{ __('analytics.comparison_title') }}</h1>
            <p class="an-subtitle">{{ __('analytics.comparison_subtitle') }}</p>
        </div>

        <div class="an-header__actions">
            <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="an-button an-button--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                {{ __('analytics.actions.print') }}
            </a>
        </div>
    </header>

    <div data-analytics-period>
        @include('analytics.partials.period')
    </div>

    <div data-analytics-content>
        @include('analytics.partials.comparison')
    </div>
</div>
@endsection
