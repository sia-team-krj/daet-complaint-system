@extends('layouts.print')

@section('title', __('analytics.comparison_title').' — '.__('analytics.print.title'))
@section('body-class', 'an-print-page an-print-page--comparison')

@push('head')
<style>@page { size: A4 landscape; margin: 12mm 14mm; }</style>
@endpush

@section('content')
<div class="an-print">
    <header class="an-print__letterhead">
        <img src="{{ asset('images/lgulogo.webp') }}" alt="" class="an-print__logo" width="56" height="56">
        <div class="an-print__letterhead-copy">
            <p class="an-print__republic">{{ __('analytics.print.republic') }}</p>
            <p class="an-print__municipality">{{ __('analytics.print.municipality') }}</p>
            <p class="an-print__office">Daet Listens</p>
        </div>
    </header>

    <div class="an-print__dochead">
        <div>
            <p class="an-print__doctype">{{ __('analytics.print.title') }}</p>
            <h1 class="an-print__title">{{ __('analytics.comparison_title') }}</h1>
            <p class="an-print__subtitle">{{ __('analytics.comparison_subtitle') }}</p>
        </div>
        <div class="an-print__meta">
            <p><strong>{{ __('analytics.print.period') }}:</strong> {{ $period->rangeLabel() }}</p>
            <p><strong>{{ __('analytics.print.generated_label') }}:</strong> {{ now()->locale(app()->getLocale())->translatedFormat('F j, Y g:i A') }}</p>
        </div>
    </div>

    <p class="an-print__notice">{{ __('analytics.print.confidential') }}</p>

    @include('analytics.partials.comparison')

    <div class="an-print__signatures" aria-label="{{ __('analytics.print.signatures') }}">
        <div class="an-print__signature">
            <p class="an-print__signature-line"></p>
            <p class="an-print__signature-name">{{ __('analytics.print.prepared_by') }}</p>
            <p class="an-print__signature-role">{{ __('analytics.print.prepared_by_role') }}</p>
        </div>
        <div class="an-print__signature">
            <p class="an-print__signature-line"></p>
            <p class="an-print__signature-name">{{ __('analytics.print.noted_by') }}</p>
            <p class="an-print__signature-role">{{ __('analytics.print.noted_by_role') }}</p>
        </div>
    </div>

    <footer class="an-print__footer">
        <span>{{ __('analytics.print.municipality') }} · Daet Listens</span>
        <span>{{ __('analytics.print.generated', ['date' => now()->locale(app()->getLocale())->translatedFormat('M j, Y g:i A')]) }}</span>
    </footer>
</div>
@endsection
