{{--
    The /track lookup state: one dark stage, one white card.

    Rendered by the page AND by the fragment endpoint behind it
    (complaints.track.browser), so the two can never disagree — see
    docs/soft-navigation.md. The root element carries the page's own modifier
    class on purpose: a soft navigation swaps this whole subtree, class list
    included, so the stage background follows the state the server chose.

    Anatomy: docs/design-system.md §5.53.
--}}
<div class="pt track-page track-page--lookup">
    <div class="track-lookup-stage">
        <div class="pt-container track-container track-container--lookup">
            <div class="track-lookup-head">
                <h1 class="track-lookup-title" data-track-heading tabindex="-1">{{ __('complaint_track.lookup.heading') }}</h1>
                <p class="track-lookup-lede">{{ __('complaint_track.lookup.lede') }}</p>
            </div>

            <section class="pt-card track-lookup" aria-label="{{ __('complaint_track.lookup.form_label') }}">
                <form method="GET" action="{{ route('complaints.track') }}" class="track-form" data-track-form data-loading-managed>
                    <div class="track-field">
                        <label class="track-label" for="ticket_id">{{ __('complaint_track.lookup.label') }}</label>
                        <div class="track-input-wrap">
                            <svg class="track-input__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input
                                id="ticket_id"
                                name="ticket_id"
                                type="text"
                                class="track-input"
                                value="{{ $ticketInput }}"
                                placeholder="{{ __('complaint_track.lookup.placeholder') }}"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                maxlength="40"
                                @if($notFound) aria-invalid="true" aria-describedby="ticket-hint ticket-error" @else aria-describedby="ticket-hint" @endif
                            >
                        </div>
                    </div>

                    @if($notFound)
                        <div class="track-alert" id="ticket-error" role="alert">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                            <div>
                                <strong>{{ __('complaint_track.lookup.not_found_title') }}</strong>
                                <p>{{ __('complaint_track.lookup.not_found_body') }}</p>
                            </div>
                        </div>
                    @endif

                    <p class="track-hint" id="ticket-hint">{{ __('complaint_track.lookup.hint') }}</p>

                    <button type="submit" class="track-submit">{{ __('complaint_track.lookup.submit') }}</button>
                </form>

                <p class="track-privacy-note" role="note">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <span>{{ __('complaint_track.lookup.privacy') }}</span>
                </p>
            </section>
        </div>
    </div>
</div>
