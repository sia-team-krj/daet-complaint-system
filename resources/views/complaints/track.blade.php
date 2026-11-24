@extends($mainLayout)

@section('title', $complaint
    ? __('complaint_track.result.page_title', ['ticket' => $complaint->ticket_id])
    : __('complaint_track.lookup.page_title'))
@section('body-class', 'page-complaint-track')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    {{--
        One region, two states. The page and `complaints.track.browser` both
        render the partial this includes, so a lookup swaps exactly what a full
        page load would have redrawn — navbar, footer and scripts untouched.
    --}}
    <div data-track-region>
        @include($complaint ? 'complaints.partials.track-result' : 'complaints.partials.track-lookup')
    </div>
@endsection

@push('scripts')
<script>
(() => {
    // Soft navigation for the tracker (docs/soft-navigation.md): the form
    // swaps the one region that changes instead of redrawing the browser, the
    // URL stays the truth (Back, Forward, refresh and a shared link all
    // behave), and with JavaScript off the form is still an ordinary GET.
    if (window.__daetTrackSoftNav === true) return;
    window.__daetTrackSoftNav = true;

    const endpoint = '{{ route('complaints.track.browser') }}';
    const pagePath = new URL('{{ route('complaints.track') }}', window.location.origin).pathname;
    const jsonHeaders = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

    const region = () => document.querySelector('[data-track-region]');

    function settle(payload) {
        const target = region();
        if (!target || typeof payload.html !== 'string') throw new Error('bad payload');
        if (payload.mode !== 'result' && payload.mode !== 'lookup') throw new Error('bad payload');

        target.innerHTML = payload.html;
        if (payload.title) document.title = payload.title;
        window.scrollTo(0, 0);

        // Announce where the visitor landed: the record itself, the field to
        // correct when the ticket was wrong, or the heading of a fresh form.
        const heading = target.querySelector('[data-track-heading]');
        if (payload.mode === 'result' || !payload.notFound) {
            if (heading) heading.focus({ preventScroll: true });
            return;
        }

        const input = target.querySelector('#ticket_id');
        if (input) {
            input.focus({ preventScroll: true });
            input.select();
        } else if (heading) {
            heading.focus({ preventScroll: true });
        }
    }

    async function navigate(url, push, busy) {
        try {
            const target = new URL(endpoint, window.location.origin);
            target.search = url.search;

            const response = await fetch(target, { headers: jsonHeaders, credentials: 'same-origin' });
            if (!response.ok) throw new Error('request failed');

            const payload = await response.json();

            // This viewer can already open the full record: follow it with a
            // real navigation, never a fragment of it.
            if (payload.redirect) {
                window.location.href = payload.redirect;
                return;
            }

            settle(payload);

            if (push !== false) history.pushState({ soft: true }, '', url.toString());
        } catch (error) {
            // Never leave the visitor on a half-drawn page.
            window.location.href = url.toString();
        } finally {
            if (busy && busy.isConnected) window.daetActionLoading?.setBusy(busy, false);
        }
    }

    // The lookup form: the same URL a full submit would have produced.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-track-form')) return;
        if (event.defaultPrevented) return;

        event.preventDefault();

        const url = new URL(form.getAttribute('action'), window.location.origin);
        const ticket = String(new FormData(form).get('ticket_id') || '').trim();

        if (ticket) url.searchParams.set('ticket_id', ticket);
        else url.searchParams.delete('ticket_id');

        const busy = event.submitter || form.querySelector('button[type="submit"]');
        if (busy) window.daetActionLoading?.setBusy(busy, true);

        navigate(url, true, busy);
    });

    // Any link to this path is a change of state, not a change of page:
    // "Track another complaint" lives inside the region being swapped, so the
    // handler is bound to the document and survives every swap.
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('wire:navigate')) return;

        const url = new URL(link.getAttribute('href'), window.location.origin);
        if (url.origin !== window.location.origin || url.pathname !== pagePath) return;

        event.preventDefault();
        navigate(url, true);
    });

    // Back and forward move through the same states.
    window.addEventListener('popstate', () => {
        navigate(new URL(window.location.href), false);
    });
})();
</script>
@endpush
