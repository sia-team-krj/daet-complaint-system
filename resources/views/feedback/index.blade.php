@extends('layouts.app')
@section('title', __('feedback.title') . ' - Daet Listens')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@section('content')
{{--
    Resident feedback.

    Two things, because they are two different things: a rating, which counts
    from the moment it is given, and a suggestion, which the administration
    reads before anybody else sees it.

    A resident rates the portal and the offices they have actually dealt with.
    A rating of an office you never wrote to says nothing about the office.
--}}
<div class="feedback-page">
    <div class="tr-container">

        <header class="tr-banner feedback-banner">
            <div class="tr-container">
                <h1 class="tr-h1">{{ __('feedback.title') }}</h1>
                <p class="tr-privacy">{{ __('feedback.subtitle') }}</p>
            </div>
        </header>

        @if(session('status'))
            <div class="feedback-flash feedback-flash--success" role="status">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="feedback-flash feedback-flash--error" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="feedback-flash feedback-flash--error" role="alert">
                <ul>
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('feedback.store') }}" class="feedback-form">
            @csrf

            <section class="tr-card feedback-card">
                <header class="feedback-card__head">
                    <h2 class="feedback-card__title">{{ __('feedback.portal_title') }}</h2>
                    <p class="feedback-card__lede">{{ __('feedback.portal_lede') }}</p>
                </header>

                <x-star-rating
                    name="portal_rating"
                    :value="$myRatings->get(null)?->rating"
                    :label="__('feedback.portal_label')"
                    :hint="__('feedback.portal_hint')"
                />

                @if($portalAverage['count'] > 0)
                    <p class="feedback-card__average">
                        {{ __('feedback.average_now', ['average' => $portalAverage['average'], 'count' => $portalAverage['count']]) }}
                    </p>
                @else
                    <p class="feedback-card__average">{{ __('feedback.average_none') }}</p>
                @endif
            </section>

            @if($offices->isNotEmpty())
                <section class="tr-card feedback-card">
                    <header class="feedback-card__head">
                        <h2 class="feedback-card__title">{{ __('feedback.offices_title') }}</h2>
                        <p class="feedback-card__lede">{{ __('feedback.offices_lede') }}</p>
                    </header>

                    <ul class="feedback-offices">
                        @foreach($offices as $office)
                            @php $average = $officeAverages[$office->id] ?? ['average' => null, 'count' => 0]; @endphp
                            <li class="feedback-office">
                                <div class="feedback-office__body">
                                    <span class="feedback-office__name">{{ $office->name }}</span>
                                    @if($average['count'] > 0)
                                        <span class="feedback-office__average">{{ __('feedback.average_now', ['average' => $average['average'], 'count' => $average['count']]) }}</span>
                                    @else
                                        <span class="feedback-office__average">{{ __('feedback.average_none') }}</span>
                                    @endif
                                </div>
                                <x-star-rating
                                    name="office_ratings[{{ $office->id }}]"
                                    :value="$myRatings->get($office->id)?->rating"
                                />
                            </li>
                        @endforeach
                    </ul>
                </section>
            @else
                <section class="tr-card feedback-card feedback-card--quiet">
                    <p class="feedback-card__lede">{{ __('feedback.no_offices') }}</p>
                </section>
            @endif

            <section class="tr-card feedback-card">
                <header class="feedback-card__head">
                    <h2 class="feedback-card__title">{{ __('feedback.suggestion_title') }}</h2>
                    <p class="feedback-card__lede">{{ __('feedback.suggestion_lede') }}</p>
                </header>

                <label class="sr-only" for="feedback-suggestion">{{ __('feedback.suggestion_title') }}</label>
                <textarea id="feedback-suggestion" name="suggestion" class="feedback-textarea" rows="6" maxlength="5000"
                          placeholder="{{ __('feedback.suggestion_placeholder') }}">{{ old('suggestion') }}</textarea>
                <p class="feedback-card__note">{{ __('feedback.suggestion_note') }}</p>
            </section>

            <div class="feedback-actions">
                <button type="submit" class="tr-button feedback-submit">{{ __('feedback.send') }}</button>
            </div>
        </form>

        @if($suggestions->isNotEmpty())
            <section class="tr-card feedback-card">
                <header class="feedback-card__head">
                    <h2 class="feedback-card__title">{{ __('feedback.published_title') }}</h2>
                    <p class="feedback-card__lede">{{ __('feedback.published_lede') }}</p>
                </header>

                <ul class="feedback-suggestions">
                    @foreach($suggestions as $suggestion)
                        <li class="feedback-suggestion">
                            <p class="feedback-suggestion__body">{{ $suggestion->body }}</p>
                            <p class="feedback-suggestion__meta">
                                {{ $suggestion->user?->display_alias ?: __('feedback.community_member') }}
                                · {{ $suggestion->published_at?->diffForHumans() }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
@endsection
