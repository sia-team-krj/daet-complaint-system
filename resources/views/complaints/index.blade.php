@extends($mainLayout)

@section('title', __('my_complaints.title'))

{{--
    The resident's register, redesigned to the same family as the public
    transparency register. The list reuses the shared report-row component
    (partials/report-row.blade.php), the filters round-trip through the
    query string so they survive refresh and can be shared, and the stat
    strip is computed server-side from the same query as the list.
--}}
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@push('styles')
    <style>
        /* ── Resident: visibility + needs-response pills ─────────── */
        /* Resident pills share line 1 — after the ticket, before the status */
        .report-row__meta .visibility-pill,
        .report-row__meta .needs-response { order: 2; }

        .visibility-pill {
            display: inline-flex;
            align-items: center;
            height: 22px;
            padding: 0 8px;
            border-radius: var(--radius-pill);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .visibility-pill--public { background: var(--navy-50); color: var(--navy-700); }
        .visibility-pill--private { background: var(--cat-other-bg); color: var(--cat-other-fg); }

        .needs-response {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 22px;
            padding: 0 8px;
            border-radius: var(--radius-pill);
            background: var(--status-progress-bg);
            color: var(--status-progress-text);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .needs-response__dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--status-progress-dot);
        }

        .report-row__updated {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: var(--faint);
            white-space: normal;
        }

        .report-row__reported { white-space: normal; }

        .report-row__reported { white-space: normal; }

        /* Filter chips are <a> here — keep them underline-free */
        .tr-chips a.status-chip { text-decoration: none; }

        .tr-banner__cta { margin-top: 16px; }

        /* ── Attention strip (real "needs information" state only) ── */
        .mc-attention {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            margin-top: 16px;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            background: var(--status-progress-bg);
            color: var(--status-progress-text);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.35;
            text-decoration: none;
        }

        .mc-attention:hover { text-decoration: none; }

        .mc-attention:focus-visible {
            outline: 2px solid var(--status-progress-text);
            outline-offset: 2px;
        }

        .mc-attention__icon { flex-shrink: 0; }

        .mc-attention__text { min-width: 0; }

        .mc-attention__cta {
            margin-left: auto;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ── Pagination ───────────────────────────────────────────── */
        .tr-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .tr-showing { margin: 0; color: var(--muted); font-size: 13px; font-variant-numeric: tabular-nums; }

        .tr-pagination nav { display: flex; align-items: center; gap: 4px; }
        .tr-pagination ul { display: flex; align-items: center; gap: 4px; list-style: none; margin: 0; padding: 0; }
        .tr-pagination li a,
        .tr-pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .tr-pagination li a:hover { background: var(--navy-50); color: var(--navy-700); }
        .tr-pagination li[aria-current="page"] span {
            background: var(--navy-700);
            border-color: var(--navy-700);
            color: var(--surface);
        }
        .tr-pagination li.disabled span { opacity: 0.45; }

        /* Shared chrome the transparency page gets from its component. The
           banner layout here matches it 1:1 so the two pages agree. */
        .mc-content { padding-bottom: 48px; }

        /* ── Desktop defaults: keep ≥641px pixel-identical where not
              intentionally changed here ────────────────────────────── */
        .mc-filters-toggle { display:none; }
        .mc-hero-mobile, .mc-hero-greeting, .mc-heading-mobile, .mc-count-mobile,
        .mc-attention-wrap--mobile { display:none; }
        /* Draft-then-Apply row: same component, desktop "fits in the bar" sizing */
        .mc-filters-actions { display:flex; align-items:center; gap:12px; flex:0 0 auto; }
        .mc-filters-apply {
            display:inline-flex; align-items:center; justify-content:center;
            height:44px; padding:0 18px;
            border:0; border-radius:var(--radius-sm);
            background:var(--gold-500); color:var(--navy-900);
            font-family:var(--font-sans); font-size:14px; font-weight:700;
            letter-spacing:0; cursor:pointer;
        }
        .mc-filters-apply:hover { background:var(--gold-300); }
        .mc-filters-apply:focus-visible { outline:none; box-shadow:var(--ring); }
        .mc-filters-clear {
            display:inline-flex; align-items:center; justify-content:center;
            height:44px; padding:0 16px;
            border:1px solid var(--line); border-radius:var(--radius-sm);
            background:var(--surface); color:var(--text);
            font-family:var(--font-sans); font-size:14px; font-weight:600;
            text-decoration:none; white-space:nowrap;
        }
        .mc-filters-clear:focus-visible { outline:none; box-shadow:var(--ring); }
        /* Narrow desktop: Apply/Clear wrap under the selects, right-aligned */
        @media (min-width: 641px) and (max-width: 1024px) {
            #filter-bar .mc-filters-panel { flex-wrap: wrap; }
            #filter-bar .mc-filters-actions { width:100%; justify-content:flex-end; }
        }
        .mc-file-cta { text-transform:none; letter-spacing:0; font-size:14px; border-radius:var(--radius-sm); padding:11px 18px; }
        .mc-file-cta__icon { display:none; width:18px; height:18px; flex-shrink:0; }

        /* ── Banner: CTA horizontal with H1 + P on desktop ── */
        body.page-complaints-index .tr-banner__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        body.page-complaints-index .tr-banner__text {
            flex: 1 1 auto;
            min-width: 0;
        }
        body.page-complaints-index .tr-banner__cta {
            flex: 0 0 auto;
        }

        .report-row__category-mobile,
        .report-row__sep-mobile,
        .report-row__date-mobile { display:none; }
        @media (min-width: 641px) {
            .mc-filters-panel[hidden] { display:flex !important; }
            #filter-bar .mc-filters-panel { display:flex !important; }
        }
    </style>
    <style>
        /* ≤640px mobile redesign — My Complaints (/complaints), pass 3.
           Scoped to body.page-complaints-index so shared report rows/footer
           keep their look on other pages. Desktop (≥641px) unchanged. */
        @media (max-width:640px) {
            /* ── Foundations ─────────────────────────────────────── */
            body.page-complaints-index .tr-container { padding-left:16px; padding-right:16px; }
            body.page-complaints-index .transparency-page { font-size:16px; line-height:1.5; }

            /* ── Hero (compact, ~150px) ──────────────────────────── */
            body.page-complaints-index .mc-hero-desktop,
            body.page-complaints-index .mc-hero-sub-desktop,
            body.page-complaints-index .mc-updated,
            body.page-complaints-index .mc-attention-wrap--desktop { display:none !important; }
            body.page-complaints-index .mc-hero-mobile { display:inline; }
            body.page-complaints-index .mc-hero-greeting { display:block; }
            body.page-complaints-index .transparency-page .tr-banner { padding-top:calc(var(--navbar-height) + 16px); padding-bottom:32px; }
            body.page-complaints-index .transparency-page .tr-h1 { font-size:24px; font-weight:700; line-height:1.2; letter-spacing:var(--tracking-display); margin:0; }
            body.page-complaints-index .mc-hero-greeting { margin:8px 0 0; font-size:14px; line-height:1.4; color:rgba(255,255,255,0.82); }
            body.page-complaints-index .tr-banner__cta { margin-top:16px; }
            body.page-complaints-index .mc-file-cta {
                display:flex; align-items:center; justify-content:center; gap:8px;
                width:100%; min-height:48px; height:48px; padding:0 16px;
                background:var(--gold-500); color:var(--navy-900);
                border:0; border-radius:var(--radius-sm); box-shadow:none;
                font-family:var(--font-sans); font-size:16px; font-weight:700;
                letter-spacing:0; text-transform:none; text-decoration:none;
            }
            body.page-complaints-index .mc-file-cta:hover { background:var(--gold-300); transform:none; box-shadow:none; }
            body.page-complaints-index .mc-file-cta:focus-visible { outline:none; box-shadow:var(--ring); }
            body.page-complaints-index .mc-file-cta__icon { display:block; width:18px; height:18px; }

            /* ── Attention strip (directly under hero) ───────────── */
            body.page-complaints-index .mc-attention-wrap--mobile { display:block; }
            body.page-complaints-index .mc-attention-wrap--mobile .mc-attention {
                margin-top:16px; min-height:48px; align-items:flex-start; padding:12px 16px;
            }
            body.page-complaints-index .mc-attention__text { white-space:normal; overflow-wrap:anywhere; }
            body.page-complaints-index .mc-attention__cta { flex-shrink:0; }
            body.page-complaints-index .mc-attention__icon { margin-top:2px; }

            /* ── List header ─────────────────────────────────────── */
            body.page-complaints-index .mc-content { padding-bottom:32px; }
            body.page-complaints-index .tr-register-head { align-items:baseline; margin-top:24px; gap:8px; }
            body.page-complaints-index .tr-h2 { font-size:20px; font-weight:600; }
            body.page-complaints-index .mc-heading-desktop { display:none; }
            body.page-complaints-index .mc-heading-mobile { display:inline; }
            body.page-complaints-index .mc-count-mobile { display:block; margin:0; margin-left:auto; font-size:13px; line-height:1.4; color:var(--muted); white-space:nowrap; }

            /* ── Toolbar: flat on page, no nested card ───────────── */
            body.page-complaints-index #filter-bar.tr-card {
                background:transparent; border:0; box-shadow:none; border-radius:0;
                padding:0; margin-top:12px; gap:12px;
            }
            body.page-complaints-index #filter-bar .tr-filters__row1 {
                flex-direction:row; align-items:stretch; gap:8px; flex-wrap:wrap;
                position:relative;
            }
            body.page-complaints-index #filter-bar .tr-search { flex:1 1 0; min-width:0; }
            body.page-complaints-index #filter-bar .tr-search input {
                height:48px; font-size:16px; border:1px solid var(--line); border-radius:var(--radius-sm);
                background:var(--surface); color:var(--text);
            }
            body.page-complaints-index .mc-filters-toggle {
                display:inline-flex; align-items:center; justify-content:center; gap:8px;
                flex:0 0 auto; width:auto; min-height:48px; height:48px; padding:0 12px;
                border:1px solid var(--line); border-radius:var(--radius-sm);
                background:var(--surface); color:var(--text);
                font-family:var(--font-sans); font-size:14px; font-weight:600; cursor:pointer;
            }
            body.page-complaints-index .mc-filters-toggle:focus-visible { outline:none; box-shadow:var(--ring); }
            body.page-complaints-index .mc-filters-icon { width:18px; height:18px; color:var(--muted); }
            body.page-complaints-index .mc-filters-badge { display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 6px; border-radius:var(--radius-pill); background:var(--navy-700); color:var(--surface); font-size:12px; font-weight:700; }
            body.page-complaints-index .mc-filters-chevron { width:16px; height:16px; color:var(--muted); transition:transform 160ms ease; }
            body.page-complaints-index .mc-filters-toggle[aria-expanded="true"] .mc-filters-chevron { transform:rotate(180deg); }

            /* Filter panel: anchored popover under the Filters button — an overlay,
               never in the content flow (the list does not shift). */
            body.page-complaints-index .mc-filters-panel {
                position:absolute; top:calc(100% + 8px); right:0; left:auto;
                width:min(100%, 340px); z-index:var(--z-popover);
                display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;
                margin:0; padding:16px; background:var(--surface);
                border:1px solid var(--line-soft); border-radius:var(--radius-sm);
                box-shadow:var(--shadow-lift);
            }
            body.page-complaints-index #filter-bar .mc-filters-panel[hidden] { display:none !important; }
            body.page-complaints-index .mc-filters-panel .tr-select { flex:none; width:100%; }
            body.page-complaints-index .mc-filters-panel select { height:48px; font-size:16px; border-radius:var(--radius-sm); }
            body.page-complaints-index .mc-filters-actions { display:flex; gap:12px; grid-column:1 / -1; }
            body.page-complaints-index .mc-filters-apply {
                display:inline-flex; align-items:center; justify-content:center;
                flex:1 1 0; min-height:48px; padding:0 16px;
                border:0; border-radius:var(--radius-sm);
                background:var(--gold-500); color:var(--navy-900);
                font-family:var(--font-sans); font-size:16px; font-weight:700; cursor:pointer;
            }
            body.page-complaints-index .mc-filters-apply:hover { background:var(--gold-300); }
            body.page-complaints-index .mc-filters-apply:focus-visible { outline:none; box-shadow:var(--ring); }
            body.page-complaints-index .mc-filters-clear {
                display:inline-flex; align-items:center; justify-content:center;
                flex:1 1 0; min-height:48px; padding:0 16px;
                border:1px solid var(--line); border-radius:var(--radius-sm);
                background:var(--surface); color:var(--text);
                font-family:var(--font-sans); font-size:14px; font-weight:600; text-decoration:none;
            }
            body.page-complaints-index .mc-filters-clear:focus-visible { outline:none; box-shadow:var(--ring); }
            body.page-complaints-index #filter-bar .tr-filters__row2 .tr-clear { display:none; }
            @media (max-width:359px) {
                body.page-complaints-index .mc-filters-panel { grid-template-columns:1fr; }
            }

            /* ── Status chips: horizontal scroll, aligned, hint fade ─ */
            body.page-complaints-index #filter-bar .tr-filters__row2 { flex-wrap:nowrap; gap:0; margin:0 -16px; padding:0 16px; position:relative; }
            body.page-complaints-index #filter-bar .tr-chips {
                flex-wrap:nowrap; overflow-x:auto; scroll-snap-type:x proximity;
                scrollbar-width:none; -ms-overflow-style:none;
                gap:8px; padding:4px 24px 8px 0; margin:0;
                -webkit-overflow-scrolling:touch;
                mask-image:linear-gradient(to right, black 0, black calc(100% - 28px), transparent 100%);
            }
            body.page-complaints-index #filter-bar .tr-chips::-webkit-scrollbar { display:none; }
            body.page-complaints-index #filter-bar .status-chip {
                scroll-snap-align:start; display:inline-flex; align-items:center; justify-content:center;
                min-height:36px; height:36px; padding:0 14px; white-space:nowrap; flex-shrink:0;
                position:relative; font-size:13px; text-decoration:none;
            }
            body.page-complaints-index #filter-bar .status-chip::before { content:""; position:absolute; inset:-4px; }
            body.page-complaints-index #filter-bar .status-chip.selected { box-shadow:inset 0 -2px 0 var(--gold-500); }
            body.page-complaints-index #filter-bar .status-chip:focus-visible { outline:none; box-shadow:var(--ring); }

            /* ── List: cards on page bg (no wrapper box) ─────────── */
            body.page-complaints-index .tr-list-card {
                background:transparent; border:0; box-shadow:none; border-radius:0;
                margin-top:12px; overflow:visible;
            }
            body.page-complaints-index .report-list { display:grid; gap:12px; padding:0; }
            body.page-complaints-index .report-list > li[data-report-row] {
                border:1px solid var(--line-soft); border-radius:var(--radius-sm);
                box-shadow:none; background:var(--surface); overflow:hidden;
            }
            body.page-complaints-index .report-list .report-row {
                display:flex; flex-wrap:wrap; align-items:center;
                gap:4px 8px; padding:16px; min-height:0; text-decoration:none; color:inherit;
            }
            body.page-complaints-index .report-list .report-row:active { background:var(--navy-50); }
            body.page-complaints-index .report-list .report-row:focus-visible { outline:none; box-shadow:var(--ring-inset); }
            body.page-complaints-index .report-row__complaint,
            body.page-complaints-index .report-row__text,
            body.page-complaints-index .report-row__meta { display:contents; }
            body.page-complaints-index .report-row__dept { display:flex !important; order:4; flex:1 1 100%; align-items:center; gap:0; min-width:0; }
            body.page-complaints-index .cat-icon,
            body.page-complaints-index .visibility-pill,
            body.page-complaints-index .report-row__meta-dot,
            body.page-complaints-index .report-row__meta-pin,
            body.page-complaints-index .report-row__brgy,
            body.page-complaints-index .report-row__dept .dept-badge,
            body.page-complaints-index .report-row__reported,
            body.page-complaints-index .report-row__verified,
            body.page-complaints-index .progress--mini,
            body.page-complaints-index .report-row__chevron { display:none !important; }

            /* Row 1: ticket (left) + status (right) */
            body.page-complaints-index .transparency-page .ticket-id { order:1; font-family:var(--font-mono); font-size:13px; font-weight:600; color:var(--muted); font-variant-numeric:tabular-nums; }
            body.page-complaints-index .transparency-page .report-row__status { order:2; display:flex; flex-direction:row; align-items:center; margin-left:auto; min-width:0; }
            body.page-complaints-index .transparency-page .status-pill { font-size:12px; white-space:nowrap; flex-shrink:0; }
            /* Needs response: its own full line, urgent */
            body.page-complaints-index .transparency-page .needs-response { order:3; flex:1 1 100%; width:fit-content; max-width:100%; margin-top:4px; height:auto; min-height:24px; padding:4px 8px; white-space:normal; }

            /* Row 2: title */
            body.page-complaints-index .transparency-page .report-row__title {
                order:4; flex:1 1 100%; margin-top:4px;
                font-size:16px; font-weight:600; line-height:1.35; color:var(--text);
                display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
            }

            /* Row 3: category · department (order set on .report-row__dept) */
            body.page-complaints-index .transparency-page .report-row__category-mobile { display:inline; font-size:13px; color:var(--muted); white-space:nowrap; flex-shrink:0; }
            body.page-complaints-index .transparency-page .report-row__sep-mobile { display:inline; font-size:13px; color:var(--muted); padding:0 4px; flex-shrink:0; }
            body.page-complaints-index .transparency-page .report-row__dept-name { font-size:13px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }

            /* Row 4: step text + date + bar */
            body.page-complaints-index .transparency-page .report-row__progressline { order:5; flex:1 1 100%; display:flex; flex-wrap:wrap; align-items:center; gap:4px 8px; margin-top:4px; }
            body.page-complaints-index .transparency-page .report-row__progressline .progress__text { font-size:12px; line-height:1.4; color:var(--muted); }
            body.page-complaints-index .transparency-page .report-row__date-mobile { display:inline; margin-left:auto; font-size:12px; color:var(--muted); white-space:nowrap; }
            body.page-complaints-index .transparency-page .report-row__progressline .progress { display:flex; flex:1 1 100%; width:100%; }

            /* ── Pagination / empty / footer ─────────────────────── */
            body.page-complaints-index .mc-showing {
                position:absolute; width:1px; height:1px; padding:0; margin:-1px;
                overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;
            }
            body.page-complaints-index .tr-pagination { margin-top:16px; justify-content:flex-start; }
            body.page-complaints-index .tr-pagination li a,
            body.page-complaints-index .tr-pagination li span { min-width:44px; height:44px; }
            body.page-complaints-index .empty-state { padding:32px 16px; text-align:left; align-items:flex-start; }
            body.page-complaints-index .empty-state .tr-button { width:100%; }
        }

        @media (max-width:640px) and (prefers-reduced-motion: reduce) {
            body.page-complaints-index .mc-filters-chevron { transition:none; }
        }
    </style>
@endpush

@push('scripts')
<script>
(function(){
    var btn = document.getElementById('mc-filters-toggle');
    var panel = document.getElementById('mc-filters-panel');
    var form = document.getElementById('filter-bar');
    var draftFields = form
        ? [form.querySelector('input[name="q"]'), document.getElementById('complaint-department'), document.getElementById('complaint-category'), document.getElementById('complaint-sort')].filter(Boolean)
        : [];
    var baseValues = draftFields.map(function (f) { return f.value; });
    function revertDrafts() {
        draftFields.forEach(function (f, i) { f.value = baseValues[i]; });
    }
    if (btn && panel && btn.dataset.filtersBound !== '1') {
        btn.dataset.filtersBound = '1';

        function setOpen(open) {
            if (open) {
                panel.removeAttribute('hidden');
                panel.setAttribute('aria-hidden', 'false');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                panel.setAttribute('hidden', '');
                panel.setAttribute('aria-hidden', 'true');
                btn.setAttribute('aria-expanded', 'false');
                // A closed filter popover discards unsaved drafts; the URL
                // (and this popover's badge) reflects the applied filters.
                revertDrafts();
            }
        }

        function isOpen() {
            return !panel.hasAttribute('hidden');
        }

        btn.addEventListener('click', function () {
            setOpen(!isOpen());
        });

        // The popover only exists ≤640px; on desktop the panel is the
        // inline always-open filter row and must never collapse.
        var mqMobile = window.matchMedia('(max-width: 640px)');

        // Popover contract: tap/click outside the popover (or its trigger) closes it.
        document.addEventListener('click', function (event) {
            if (!mqMobile.matches || !isOpen()) return;
            var row1 = btn.closest('.tr-filters__row1');
            if (!row1 || !row1.contains(event.target)) setOpen(false);
        });

        // Escape closes and returns focus to the trigger.
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && mqMobile.matches && isOpen()) {
                setOpen(false);
                btn.focus();
            }
        });

        var mq = window.matchMedia('(min-width: 641px)');
        function sync() { if (mq.matches) setOpen(true); }
        if (mq.addEventListener) mq.addEventListener('change', sync); else if (mq.addListener) mq.addListener(sync);
        sync();
    }
    var chips = document.querySelector('.tr-chips');
    if (chips && window.matchMedia('(max-width: 640px)').matches) {
        var active = chips.querySelector('.status-chip.selected');
        if (active && active.scrollIntoView) { try { active.scrollIntoView({ inline: 'center', block: 'nearest' }); } catch(e){} }
    }
})();
</script>
@endpush

@section('body-class', 'page-complaints-index')

@section('content')
<div class="transparency-page">
    {{-- ════════════ BANNER ════════════ --}}
    <header class="tr-banner mc-hero">
        <div class="tr-container">
            @php
                $hour = now('Asia/Manila')->hour;
                $greetingKey = $hour < 12 ? 'dashboard.greeting_morning' : ($hour < 17 ? 'dashboard.greeting_afternoon' : 'dashboard.greeting_evening');
            @endphp
            <div class="tr-banner__row">
                <div class="tr-banner__text">
                    <h1 class="tr-h1 mc-hero-h1"><span class="mc-hero-desktop">{{ __($greetingKey) }}, <span>{{ auth()->user()->first_name }}.</span></span><span class="mc-hero-mobile">{{ __('my_complaints.banner_title') }}</span></h1>
                    <p class="tr-privacy mc-hero-sub-desktop">{{ __('my_complaints.banner_sub') }}</p>
                    <p class="mc-hero-greeting">{{ __($greetingKey) }}, {{ auth()->user()->first_name }}</p>
                </div>
                <div class="tr-banner__cta">
                    <a href="{{ route('complaints.create') }}" wire:navigate class="btn-nav-cta mc-file-cta">
                        <svg class="mc-file-cta__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('my_complaints.file_cta') }}
                    </a>
                </div>
            </div>

            <p class="tr-updated mc-updated">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ __('my_complaints.updated') }} {{ now()->locale(app()->getLocale())->translatedFormat('M j, Y') }}
            </p>
        </div>
    </header>

    {{-- Attention strip (mobile): directly under the hero. Desktop keeps its
         position after the stat strip for a pixel-identical 1310px layout. --}}
    @if($attentionCount > 0 && $attentionComplaint)
        <div class="tr-container mc-attention-wrap mc-attention-wrap--mobile">
            <a href="{{ route('complaints.show', $attentionComplaint) }}" wire:navigate class="mc-attention" role="status" aria-live="polite">
                <svg class="mc-attention__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span class="mc-attention__text">{{ trans_choice('my_complaints.attention', $attentionCount) }}</span>
                <span class="mc-attention__cta">{{ __('dashboard.read_update') }} <span aria-hidden="true">→</span></span>
            </a>
        </div>
    @endif



    {{-- Attention strip (desktop position, preserved for 1310px parity). --}}
    @if($attentionCount > 0 && $attentionComplaint)
        <div class="tr-container mc-attention-wrap mc-attention-wrap--desktop">
            <a href="{{ route('complaints.show', $attentionComplaint) }}" wire:navigate class="mc-attention" role="status" aria-live="polite">
                <svg class="mc-attention__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span class="mc-attention__text">{{ trans_choice('my_complaints.attention', $attentionCount) }}</span>
                <span class="mc-attention__cta">{{ __('dashboard.read_update') }} <span aria-hidden="true">→</span></span>
            </a>
        </div>
    @endif

    <section class="tr-register mc-content" aria-labelledby="register-heading">
        <div class="tr-container">
            <div class="tr-register-head">
                <h2 class="tr-h2" id="register-heading"><span class="mc-heading-desktop">{{ __('my_complaints.register_heading') }}</span><span class="mc-heading-mobile">{{ __('my_complaints.list_heading_mobile') }}</span></h2>
                <p class="mc-count-mobile" aria-hidden="true">{{ trans_choice('my_complaints.count', $complaints->total()) }}</p>
            </div>

            {{-- Filter bar: GET round-trip so filters live in the URL --}}
            <form data-loading-submit method="GET" action="{{ route('complaints.index') }}" class="tr-card tr-filters mc-filters" id="filter-bar" role="search">
                <div class="tr-filters__row1 mc-filters__row1">
                    <label class="tr-search" for="complaint-search">
                        <span class="sr-only">{{ __('my_complaints.search_label') }}</span>
                        <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input
                            id="complaint-search"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ __('my_complaints.search_placeholder') }}"
                            autocomplete="off"
                        >
                    </label>
                    @php
                        $mcActiveFilters = collect([
                            'department' => request('department'),
                            'category' => request('category'),
                            'sort' => request('sort'),
                        ])->filter(fn (?string $v) => filled($v) && ($v !== null) && ($v !== 'reported_desc'))->count();
                    @endphp
                    <button type="button" class="mc-filters-toggle" id="mc-filters-toggle" aria-expanded="{{ $mcActiveFilters > 0 ? 'true' : 'false' }}" aria-controls="mc-filters-panel">
                        <svg class="mc-filters-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>
                        <span class="mc-filters-toggle__label">{{ __('transparency.register.filters') ?? 'Filters' }}</span>
                        @if($mcActiveFilters > 0)<span class="mc-filters-badge" aria-label="{{ $mcActiveFilters }}">{{ $mcActiveFilters }}</span>@endif
                        <svg class="mc-filters-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="tr-selects mc-filters-panel" id="mc-filters-panel" @if($mcActiveFilters === 0) hidden @endif aria-hidden="{{ $mcActiveFilters === 0 ? 'true' : 'false' }}">
                        <div class="tr-select tr-select--department">
                            <span class="sr-only" id="ds-department-label">{{ __('transparency.register.department_label') }}</span>
                            <select id="complaint-department" name="department" aria-labelledby="ds-department-label">
                                <option value="">{{ __('my_complaints.all_departments') }}</option>
                                @foreach($departmentOptions as $department)
                                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--category">
                            <span class="sr-only" id="ds-category-label">{{ __('transparency.register.category_label') }}</span>
                            <select id="complaint-category" name="category" aria-labelledby="ds-category-label">
                                <option value="">{{ __('my_complaints.all_categories') }}</option>
                                @foreach($categoryOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--sort">
                            <span class="sr-only" id="ds-sort-label">{{ __('transparency.register.sort_label') }}</span>
                            <select id="complaint-sort" name="sort" aria-labelledby="ds-sort-label">
                                <option value="reported_desc" @selected(request('sort', 'reported_desc') === 'reported_desc')>{{ __('my_complaints.sort_reported_desc') }}</option>
                                <option value="reported_asc" @selected(request('sort') === 'reported_asc')>{{ __('my_complaints.sort_reported_asc') }}</option>
                                <option value="status_asc" @selected(request('sort') === 'status_asc')>{{ __('my_complaints.sort_status_asc') }}</option>
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="mc-filters-actions">
                            <button type="submit" class="mc-filters-apply">{{ __('transparency.register.apply_filters') ?? 'Apply' }}</button>
                            <a href="{{ route('complaints.index') }}" class="mc-filters-clear">{{ __('my_complaints.clear_filters') }}</a>
                        </div>
                    </div>
                </div>

                <div class="tr-filters__row2">
                    <div class="tr-chips" role="group" aria-label="{{ __('transparency.register.status_label') }}">
                        @php
                            $baseQuery = array_filter(['q' => request('q'), 'sort' => request('sort'), 'department' => request('department'), 'category' => request('category')]);
                            $chips = [
                                'all' => __('my_complaints.all_statuses'),
                                'open' => __('my_complaints.stat_open'),
                                'submitted' => __('statuses.complaint_status.submitted'),
                                'under_review' => __('statuses.complaint_status.under_review'),
                                'in_progress' => __('statuses.complaint_status.in_progress'),
                                'resolved' => __('statuses.complaint_status.resolved'),
                            ];
                        @endphp
                        @foreach($chips as $key => $label)
                            <a href="{{ route('complaints.index', array_filter(array_merge($baseQuery, ['status' => $key === 'all' ? null : $key]))) }}" class="status-chip {{ (request('status') ?: 'all') === $key || (!request('status') && $key === 'all') ? 'selected' : '' }}">{{ $label }}@if(isset($counts[$key]))&nbsp;{{ $counts[$key] }}@endif</a>
                        @endforeach
                    </div>
                    @if(request()->anyFilled(['q', 'sort', 'department', 'category']) || filled(request('status')))
                        <a href="{{ route('complaints.index') }}" class="tr-clear" id="clear-filters">{{ __('my_complaints.clear_filters') }}</a>
                    @endif
                </div>
            </form>

            {{-- List card --}}
            <div class="tr-card tr-list-card">
                <div class="report-list__head" aria-hidden="true">
                    <span>{{ __('transparency.register.table.complaint') }}</span>
                    <span>{{ __('transparency.register.table.department') }}</span>
                    <span>{{ __('transparency.register.table.status') }}</span>
                    <span>{{ __('transparency.register.table.reported') }}</span>
                    <span></span>
                </div>

                @php
                    $hasFilters = request()->anyFilled(['q', 'department', 'category'])
                        || (filled(request('status')) && request('status') !== 'all');
                @endphp
                @if($complaints->total() === 0 && !$hasFilters)
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        <strong>{{ __('my_complaints.empty.title') }}</strong>
                        <p>{{ __('my_complaints.empty.body') }}</p>
                        <a href="{{ route('complaints.create') }}" wire:navigate class="tr-button">{{ __('my_complaints.empty.cta') }}</a>
                    </div>
                @elseif($complaints->isEmpty())
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                        </span>
                        <strong>{{ __('my_complaints.no_results.title') }}</strong>
                        <p>{{ __('my_complaints.no_results.body') }}</p>
                        <a href="{{ route('complaints.index') }}" class="tr-button">{{ __('my_complaints.clear_filters') }}</a>
                    </div>
                @else
                    <ul class="report-list" id="complaint-table-body">
                        @foreach($complaints as $complaint)
                            @include('partials.report-row', ['complaint' => $complaint])
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($complaints->total() > 0)
                <div class="tr-pagination">
                    <p class="tr-showing mc-showing" aria-live="polite">{{ __('my_complaints.showing', ['from' => $complaints->firstItem(), 'to' => $complaints->lastItem(), 'total' => $complaints->total()]) }}</p>
                    @if($complaints->hasPages())
                        <div class="tr-pagination-nav">
                            {{ $complaints->onEachSide(1)->links('pagination::resident') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
