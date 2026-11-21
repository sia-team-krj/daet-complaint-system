@extends('admin.layouts.app')

@section('title', __('admin_categories.page.title').' - Admin')

@section('admin-content')
<div class="admin-page cat-page">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}">{{ __('nav.overview') }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ __('admin_categories.page.title') }}</span>
    </nav>

    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('admin_categories.page.title') }}</h1>
            <p class="admin-page-description">{{ __('admin_categories.page.subtitle') }}</p>
        </div>
        <a href="{{ route('complaints.create') }}" class="admin-button admin-button--secondary">
            {{ __('nav.file_complaint') }}
            <span aria-hidden="true">↗</span>
        </a>
    </header>

    @include('admin.categories.partials.manager')
</div>
@endsection
