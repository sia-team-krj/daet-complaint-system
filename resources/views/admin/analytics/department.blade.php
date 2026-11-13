@extends('admin.layouts.app')

@section('title', $department->name.' '.__('analytics.title').' - Admin')
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
    <p class="an-back">
        <a href="{{ route($routeNames['comparison'], $period->query()) }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            {{ __('analytics.back_to_comparison') }}
        </a>
    </p>

    <header class="an-header">
        <div class="an-header__copy">
            <p class="an-kicker">{{ __('analytics.kicker') }}</p>
            <h1 class="an-title">{{ $department->name }}</h1>
            <p class="an-header__department">
                <span class="dept-badge" data-dept="{{ $department->code }}">{{ $department->code }}</span>
                @if(! $department->is_active)
                    <span class="an-archived">{{ __('analytics.comparison.archived') }}</span>
                @endif
                <span>{{ __('analytics.admin_department_subtitle') }}</span>
            </p>
            <p class="an-subtitle">{{ __('analytics.subtitle') }}</p>
        </div>

        <div class="an-header__actions">
            @if(($data['metrics']['filed'] ?? 0) > 0)
                <a href="{{ $exportUrl }}" class="an-button an-button--secondary" download>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('analytics.actions.export') }}
                </a>
            @endif
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
        @include('analytics.partials.content')
    </div>
</div>
@endsection
