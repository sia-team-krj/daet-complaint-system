{{--
    Sample tracker card (static demo, not live data).

    Reused in the desktop hero mockup and again, below the 5 steps on mobile
    (< 1024px) — one markup, two placements. The "Sample"/"Halimbawa" chip
    and the COMP-2026-00000 tracking number are what keep it honest: nothing
    here implies a real complaint exists.
--}}
@php
    $step = 4;
    $statusLabel = __('statuses.complaint_status.in_progress');
@endphp
<div class="lp-tracker" role="img" aria-label="{{ __('landing.sample_card_aria') }}">
    <div class="lp-tracker__top">
        <span class="lp-tracker__chip">{{ __('landing.sample_chip') }}</span>
        <span class="ticket-id">COMP-2026-00000</span>
    </div>
    <div class="lp-tracker__complaint">
        @include('pages.transparency.partials.category-icon', ['category' => 'road_damage'])
        <p class="lp-tracker__title">{{ __('landing.sample_title') }}</p>
    </div>
    <div class="lp-tracker__status">
        <span class="status-pill in_progress"><span class="status-pill__dot" aria-hidden="true"></span>{{ $statusLabel }}</span>
    </div>
    <div class="progress lp-tracker__bar" role="img" aria-label="{{ __('dashboard.step_of', ['current' => $step, 'total' => 5, 'label' => $statusLabel]) }}">
        @for($segment = 1; $segment <= 5; $segment++)
            <span class="progress__seg {{ $segment < $step ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '') }}"></span>
        @endfor
    </div>
</div>
