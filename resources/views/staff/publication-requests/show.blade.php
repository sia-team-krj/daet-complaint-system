@extends('staff.layouts.app')

@section('title', __('staff.publication.review_title') . ' - ' . __('staff.title'))
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h1 class="tr-h1">{{ __('staff.publication.review_title') }}</h1>
            <p class="tr-privacy">{{ __('staff.publication.review_lede') }}</p>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <a href="{{ route('staff.publication-requests.index') }}" style="display: inline-flex; align-items: center; gap: 6px; margin: 18px 0 14px; color: var(--navy-700); font-size: 13px; font-weight: 600; text-decoration: none;">
            <span aria-hidden="true">←</span> {{ __('staff.publication.review_back') }}
        </a>

        {{-- What the decision is about: the redacted copy a resident would
             read, never the name of the party. That name stays in the office
             that handled the report. --}}
        <section class="tr-card" style="margin-bottom: 20px;">
            <div class="report-row__head">
                <span class="report-row__ticket">{{ $complaint->ticket_id }}</span>
                @if($complaint->department)
                    <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                @endif
            </div>
            <p class="report-row__meta" style="margin-top: 8px;">
                {{ $complaint->category_label ?? $complaint->category }}
                · {{ $complaint->department?->name ?? __('nav.departments') }}
            </p>

            <h2 class="tr-h2" style="margin-top: 18px;">{{ __('staff.publication.review_copy_title') }}</h2>
            <p class="tr-results" style="margin-bottom: 12px;">{{ __('staff.publication.review_copy_hint') }}</p>

            <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 700; color: var(--text);">{{ $complaint->publicTitle() }}</h3>
            <p style="margin: 0; color: var(--text); white-space: pre-line;">{{ $complaint->publicDescription() }}</p>
        </section>

        <x-publication-panel
            class="moderation-panel--light"
            :complaint="$complaint"
            :can-approve="true"
            :show-named-party="false"
            :lede="__('staff.publication.review_panel_lede')"
        />
    </div>
</div>
@endsection
