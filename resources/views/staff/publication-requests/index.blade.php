@extends('staff.layouts.app')

@section('title', __('staff.publication.queue_title') . ' - ' . __('staff.title'))
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h1 class="tr-h1">{{ __('staff.publication.queue_title') }}</h1>
            <p class="tr-privacy">{{ __('staff.publication.queue_lede') }}</p>
        </div>
    </header>

    <section aria-labelledby="publication-requests-title" class="tr-register" style="max-width: none;">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h2 id="publication-requests-title" class="sr-only">{{ __('staff.publication.queue_title') }}</h2>

            @if ($requests->isEmpty())
                <div class="empty-state">
                    <p class="empty-state__title">{{ __('staff.publication.queue_empty') }}</p>
                    <p class="empty-state__body">{{ __('staff.publication.queue_empty_body') }}</p>
                </div>
            @else
                <ul class="publication-requests__list" role="list">
                    @foreach ($requests as $request)
                        <li role="listitem">
                            <div class="publication-requests__row">
                                <div class="publication-requests__row-main">
                                    <div class="report-row__head">
                                        <span class="report-row__ticket">{{ $request->ticket_id }}</span>
                                        @if ($request->department)
                                            <span class="dept-badge" data-dept="{{ $request->department->code }}">{{ $request->department->code }}</span>
                                        @endif
                                    </div>
                                    <h3 class="publication-requests__title">{{ $request->title }}</h3>
                                    <p class="publication-requests__meta">
                                        {{ $request->category_label ?? $request->category }}
                                        <span aria-hidden="true">&middot;</span> {{ $request->department?->name ?? __('nav.departments') }}
                                    </p>
                                </div>
                                <a class="tr-button publication-requests__open" href="{{ route('staff.publication-requests.show', $request) }}">
                                    {{ __('staff.publication.open') }}
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="tr-pagination">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
