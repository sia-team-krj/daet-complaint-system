@extends($mainLayout)

@section('title', __('complaint_success.page.title', ['ticket' => $complaint->ticket_id]))

{{--
    Submission success — the receipt that closes the citizen filing flow.
    Light page in the wizard / My-Complaints family: green check hero, mono
    ticket strip, fact rows, the shared 5-segment progress bar (§5.15 base
    from partials.public-ui, widened by the page CSS), the same "what
    happens next" copy the wizard teaches, and ONE gold primary CTA.
    Page CSS: resources/css/pages/complaint-success.css (token-only).
--}}
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('content')
@php
    $status = $complaint->statusEnum;
    $statusKey = \Illuminate\Support\Str::slug($status->value, '_');
    $closed = $statusKey === 'closed';
    $step = match ($statusKey) {
        'submitted' => 1,
        'under_review', 'verified', 'assigned' => 2,
        'in_progress' => 3,
        'resolved' => 4,
        'closed' => 5,
        default => null, // rejected — neutral bar, label only
    };
    $isRejected = $step === null;
    $dept = $complaint->department;
    $photoCount = (int) $complaint->evidence_image_count;
    $locationSource = session('complaint_location_source');
@endphp
<section class="cs-page">
    <div class="tr-container">
        <div class="cs-stack">

            <article class="cs-card cs-hero" aria-labelledby="cs-hero-title">
                <span class="cs-hero__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 10"/></svg>
                </span>

                <h1 id="cs-hero-title">{{ __('complaint_success.hero.title') }}</h1>

                @if($dept)
                    <p class="cs-hero__lede">{{ __('complaint_success.hero.lede_office', ['office' => $dept->name, 'menu' => __('nav.my_complaints')]) }}</p>
                @else
                    <p class="cs-hero__lede">{{ __('complaint_success.hero.lede_pending') }}</p>
                @endif

                <div class="cs-ticket">
                    <span class="cs-ticket__label">{{ __('complaint_success.ticket.label') }}</span>
                    <span class="cs-ticket__value">{{ $complaint->ticket_id }}</span>
                    <span class="cs-ticket__hint">{{ __('complaint_success.ticket.hint') }}</span>
                </div>

                <ul class="cs-facts">
                    <li>
                        <span>{{ __('complaint_success.facts.status') }}</span>
                        <strong>
                            <span class="status-pill {{ $statusKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $status->label() }}</span>
                        </strong>
                    </li>
                    <li>
                        <span>{{ __('complaint_success.facts.filed') }}</span>
                        <strong>{{ $complaint->created_at?->locale(app()->getLocale())->translatedFormat('M j, Y') }}</strong>
                    </li>
                    <li>
                        <span>{{ __('complaint_success.facts.routed_to') }}</span>
                        <strong>
                            @if($dept?->code)
                                <span class="dept-badge" data-dept="{{ $dept->code }}">{{ $dept->code }}</span>
                            @endif
                            {{ $dept?->name ?? __('transparency.v2.department_pending') }}
                        </strong>
                    </li>
                    @if($photoCount > 0)
                        <li>
                            <span>{{ __('complaint_success.facts.photos') }}</span>
                            <strong>{{ trans_choice('complaint_success.facts.photos_count', $photoCount, ['count' => $photoCount]) }}</strong>
                        </li>
                    @endif
                </ul>

                <div class="cs-progress">
                    <div role="img" aria-label="{{ $isRejected ? $status->label() : __('my_complaints.progress_aria', ['step' => $step, 'total' => 5, 'status' => $status->label()]) }}">
                        <span class="cs-progress__text">
                            @if($isRejected)
                                {{ $status->label() }}
                            @else
                                {{ __('my_complaints.step_status', ['step' => $step, 'total' => 5, 'status' => $status->label()]) }}
                            @endif
                        </span>
                        <span class="progress progress--full" aria-hidden="true">
                            @for($segment = 1; $segment <= 5; $segment++)
                                <span class="progress__seg {{ $isRejected ? '' : ($segment < $step || $closed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '')) }}"></span>
                            @endfor
                        </span>
                    </div>
                </div>
            </article>

            <section class="cs-card cs-next" aria-labelledby="cs-next-title">
                <h2 id="cs-next-title">{{ __('complaint_success.next.title') }}</h2>
                <ol class="cs-next__list">
                    <li><span class="cs-step-num" aria-hidden="true">1</span><span>{{ __('complaint_create.wizard.next_1') }}</span></li>
                    <li><span class="cs-step-num" aria-hidden="true">2</span><span>{{ __('complaint_create.wizard.next_2') }}</span></li>
                    <li><span class="cs-step-num" aria-hidden="true">3</span><span>{{ __('complaint_create.wizard.next_3') }}</span></li>
                </ol>
                <p class="cs-next__note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>
                        @if($locationSource === 'photo_gps')
                            {{ __('complaint_success.next.location_gps') }}
                        @elseif(in_array($locationSource, ['address', 'map'], true))
                            {{ __('complaint_success.next.location_plain') }}
                        @elseif($locationSource === 'not_applicable')
                            {{ __('complaint_success.next.location_not_applicable') }}
                        @else
                            {{ __('complaint_success.next.location_none') }}
                        @endif
                    </span>
                </p>
            </section>

            {{--
                Auto-redirect strip: after a short countdown the receipt hands
                over to My Complaints. "Stay on this page" cancels it so nobody
                is swept away mid-read; the countdown pauses while the tab is
                hidden. JS contract: data attributes are the contract (§7).
            --}}
            @php
                $redirectSeconds = 6;
                $redirectTemplate = __('complaint_success.redirect.countdown', ['menu' => __('nav.my_complaints')]);
            @endphp
            <div
                class="cs-redirect"
                id="cs-redirect"
                data-redirect-url="{{ route('complaints.index') }}"
                data-redirect-seconds="{{ $redirectSeconds }}"
                data-redirect-template="{{ $redirectTemplate }}"
            >
                <span class="cs-redirect__label" role="timer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span data-redirect-count>{{ str_replace('{s}', (string) $redirectSeconds, $redirectTemplate) }}</span>
                </span>
                <button class="cs-redirect__cancel" type="button" data-redirect-cancel>
                    {{ __('complaint_success.redirect.cancel') }}
                </button>
            </div>

            <div class="cs-actions">
                <a href="{{ route('complaints.show', $complaint) }}" class="cs-btn cs-btn--primary">{{ __('complaint_success.actions.view') }}</a>
                <a href="{{ route('complaints.index') }}" class="tr-button">{{ __('complaint_success.actions.my_complaints') }}</a>
            </div>

        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    /*
     * Success receipt auto-redirect: counts down data-redirect-seconds, then
     * sends to data-redirect-url. "Stay on this page" cancels; the tick does
     * not decrement while the tab is hidden so the redirect never fires on a
     * tab the person isn't looking at. No libraries; data attributes are the
     * contract (design system §7).
     */
    (function () {
        var box = document.getElementById('cs-redirect');
        if (!box || !box.getAttribute('data-redirect-url')) { return; }

        var template = box.getAttribute('data-redirect-template') || '';
        var url = box.getAttribute('data-redirect-url');
        var remaining = parseInt(box.getAttribute('data-redirect-seconds'), 10);
        if (!Number.isFinite(remaining) || remaining <= 0) { return; }

        var labelEl = box.querySelector('[data-redirect-count]');
        var cancelled = false;
        var timer = null;

        function render(value) {
            if (labelEl) { labelEl.textContent = template.replace('{s}', String(value)); }
        }

        function redirect() {
            if (cancelled || document.visibilityState === 'hidden') { return; }
            window.location.assign(url);
        }

        function schedule(ms) {
            timer = window.setTimeout(nextTick, ms);
        }

        function nextTick() {
            if (cancelled) { return; }
            if (remaining <= 1) {
                redirect();
                return;
            }
            // While the tab is hidden, hold the countdown instead of landing
            // the person on My Complaints before they've seen the receipt.
            if (document.visibilityState === 'hidden') {
                schedule(1000);
                return;
            }
            remaining -= 1;
            render(remaining);
            schedule(1000);
        }

        var cancel = box.querySelector('[data-redirect-cancel]');
        if (cancel) {
            cancel.addEventListener('click', function () {
                cancelled = true;
                if (timer) { window.clearTimeout(timer); }
                box.hidden = true; // [hidden] is the visibility mechanism
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (!cancelled && document.visibilityState === 'visible' && timer) {
                window.clearTimeout(timer);
                schedule(1000);
            }
        });

        schedule(1000);
    })();
</script>
@endpush
