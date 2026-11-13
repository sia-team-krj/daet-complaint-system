{{--
    The fairness note is deliberately not a tooltip or a footnote. A comparison
    of offices that share a treasury but not a staffing complement, a vehicle
    pool or a budget is context; presented as a league table it invites a
    judgement the numbers cannot support.
--}}
<aside class="an-fairness" aria-labelledby="an-fairness-title">
    <span class="an-fairness__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><path d="M6 7h12"/><path d="m6 7-3 6a3.5 3.5 0 0 0 6 0Z"/><path d="m18 7 3 6a3.5 3.5 0 0 1-6 0Z"/></svg>
    </span>
    <div>
        <h2 class="an-fairness__title" id="an-fairness-title">{{ __('analytics.comparison.fairness_title') }}</h2>
        <p>{{ __('analytics.comparison.fairness_body') }}</p>
    </div>
</aside>
