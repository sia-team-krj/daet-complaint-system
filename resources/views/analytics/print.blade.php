@extends('layouts.print')

@section('title', $department->name.' — '.__('analytics.print.title'))
@section('body-class', 'an-print-page')

@section('content')
<div class="an-print">
    <header class="an-print__header">
        <div>
            <p class="an-print__municipality">{{ __('analytics.print.municipality') }}</p>
            <h1 class="an-print__title">{{ $department->name }}</h1>
            <p class="an-print__subtitle">{{ __('analytics.print.title') }}</p>
        </div>
        <div class="an-print__meta">
            <span class="dept-badge" data-dept="{{ $department->code }}">{{ $department->code }}</span>
            <p>{{ $period->rangeLabel() }}</p>
            <p>{{ __('analytics.print.generated', ['date' => now()->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}</p>
        </div>
    </header>

    <p class="an-print__notice">{{ __('analytics.print.confidential') }}</p>

    @include('analytics.partials.content')

    <footer class="an-print__footer">
        <span>{{ __('analytics.print.municipality') }} · Daet Listens</span>
        <span>{{ $period->rangeLabel() }}</span>
    </footer>
</div>
@endsection
