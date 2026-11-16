@extends('staff.layouts.app')

@section('title', __('office.statistics.title'))
@section('body-class', 'page-staff-dashboard page-staff-office-statistics')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@push('styles')
<style>
    .office-stats-hero { margin-bottom: 18px; }
    .office-stats-hero .tr-privacy { max-width: 70ch; }
    body.page-staff-office-statistics .office-stats-kpis.dept-stats {
        margin-top: -20px;
        margin-bottom: 20px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px;
        background: var(--surface);
        border: 1px solid var(--border, var(--dept-card-border));
        border-radius: var(--radius-lg, 14px);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        text-decoration: none;
        color: inherit;
        min-width: 0;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    body.page-staff-office-statistics .office-stats-kpis a.dept-stat:hover {
        border-color: var(--navy-300, var(--dept-active-border));
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
    }
    body.page-staff-office-statistics .office-stats-kpis .tr-stat__icon,
    body.page-staff-office-statistics .office-stats-kpis .dept-si {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
    }
    body.page-staff-office-statistics .office-stats-kpis .tr-stat__icon svg,
    body.page-staff-office-statistics .office-stats-kpis .dept-si svg { width: 18px; height: 18px; }
    body.page-staff-office-statistics .office-stats-kpis .tr-stat__icon--navy { background: var(--dept-icon-navy-bg, rgba(30, 58, 95, 0.1)); color: var(--dept-icon-navy-fg, var(--navy-700)); }
    body.page-staff-office-statistics .office-stats-kpis .tr-stat__icon--amber { background: var(--dept-icon-amber-bg, rgba(217, 119, 6, 0.12)); color: var(--dept-icon-amber-fg, var(--gold-700)); }
    body.page-staff-office-statistics .office-stats-kpis .tr-stat__icon--green { background: var(--dept-icon-green-bg, rgba(22, 163, 74, 0.12)); color: var(--dept-icon-green-fg, var(--success)); }
    body.page-staff-office-statistics .office-stats-kpis .tr-stat__icon--sky { background: var(--dept-icon-sky-bg, rgba(14, 165, 233, 0.12)); color: var(--dept-icon-sky-fg, var(--blue-600)); }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__value {
        display: block;
        font-size: 28px;
        line-height: 1.1;
        font-weight: 700;
        color: var(--text-primary, var(--text));
        font-variant-numeric: tabular-nums;
    }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__label {
        display: block;
        margin-top: 2px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--text-secondary, var(--muted));
        line-height: 1.25;
    }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__meta {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: var(--text-secondary, var(--muted));
        line-height: 1.35;
    }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__delta {
        display: inline-flex;
        align-items: center;
        margin-top: 8px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.4;
    }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__delta.is-up { color: var(--success, var(--status-resolved-text)); background: var(--dept-icon-green-bg, rgba(22, 163, 74, 0.12)); }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__delta.is-down { color: var(--danger); background: var(--danger-soft, rgba(220, 38, 38, 0.1)); }
    body.page-staff-office-statistics .office-stats-kpis .dept-stat__delta.is-flat { color: var(--text-secondary, var(--muted)); background: var(--surface-soft, rgba(148, 163, 184, 0.12)); }
    .an-period, .an-content { margin-top: 0; }
    .office-stats-links { display: flex; flex-wrap: wrap; gap: 8px; margin: -4px 0 20px; }
    .office-stats-links .status-chip { text-decoration: none; }
    body.page-staff-office-statistics .an-note {
        display: flex; align-items: flex-start; gap: 8px;
        margin: 0 0 16px; padding: 10px 12px;
        border: 1px solid var(--border, var(--dept-card-border));
        border-radius: 10px; background: var(--surface-soft, var(--surface));
        color: var(--text-secondary, var(--muted)); font-size: 13px; line-height: 1.45;
    }
    body.page-staff-office-statistics .an-grid { display: grid; gap: 16px; margin-bottom: 16px; }
    body.page-staff-office-statistics .an-grid--trend { grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); }
    body.page-staff-office-statistics .an-grid--two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    body.page-staff-office-statistics .an-card {
        background: var(--surface); border: 1px solid var(--border, var(--dept-card-border));
        border-radius: var(--radius-lg, 14px); padding: 18px 20px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04); min-width: 0;
    }
    body.page-staff-office-statistics .an-card__head { margin-bottom: 14px; }
    body.page-staff-office-statistics .an-card__title { margin: 0; font-size: 18px; line-height: 1.25; color: var(--text-primary, var(--text)); font-weight: 700; }
    body.page-staff-office-statistics .an-card__sub { margin: 4px 0 0; font-size: 13px; color: var(--text-secondary, var(--muted)); line-height: 1.4; }
    body.page-staff-office-statistics .an-empty {
        padding: 18px; border: 1px dashed var(--border, var(--dept-card-border)); border-radius: 10px;
        color: var(--text-secondary, var(--muted)); text-align: center; font-size: 14px;
    }
    body.page-staff-office-statistics .an-bars { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
    body.page-staff-office-statistics .an-bars__label { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; color: var(--text-secondary, var(--muted)); margin-bottom: 6px; }
    body.page-staff-office-statistics .an-bars__label a { color: var(--text-primary, var(--text)); font-weight: 600; text-decoration: none; }
    body.page-staff-office-statistics .an-bars__label a:hover { text-decoration: underline; }
    body.page-staff-office-statistics .an-bars__track { height: 8px; border-radius: 999px; background: var(--surface-soft, rgba(148,163,184,.18)); overflow: hidden; }
    body.page-staff-office-statistics .an-bars__fill { display: block; height: 100%; border-radius: inherit; background: var(--navy-600, var(--primary)); }
    body.page-staff-office-statistics .an-bars__fill--warm { background: var(--gold-600, var(--amber-500)); }
    body.page-staff-office-statistics .an-bars__fill--aging { background: var(--gold-500, var(--amber-500)); }
    body.page-staff-office-statistics .an-quality { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; }
    body.page-staff-office-statistics .an-quality__item { padding: 12px; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px; background: var(--surface-soft, var(--surface)); }
    body.page-staff-office-statistics .an-quality__item span { display:block; font-size:12px; color: var(--text-secondary, var(--muted)); text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
    body.page-staff-office-statistics .an-quality__item strong { display:block; margin-top: 4px; font-size: 22px; color: var(--text-primary, var(--text)); line-height: 1.1; }
    body.page-staff-office-statistics .an-quality__item small { display:block; margin-top: 4px; font-size: 12px; color: var(--text-secondary, var(--muted)); }
    body.page-staff-office-statistics .an-mini-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; margin-bottom: 14px; }
    body.page-staff-office-statistics .an-mini { padding: 12px; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px; background: var(--surface-soft, var(--surface)); }
    body.page-staff-office-statistics .an-mini span { display:block; font-size:12px; color: var(--text-secondary, var(--muted)); font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    body.page-staff-office-statistics .an-mini strong { display:block; margin-top: 4px; font-size: 20px; color: var(--text-primary, var(--text)); }
    body.page-staff-office-statistics .an-table-wrap { overflow-x: auto; border: 1px solid var(--border, var(--dept-card-border)); border-radius: 10px; background: var(--surface); }
    body.page-staff-office-statistics .an-table { width: 100%; border-collapse: collapse; font-size: 14px; color: var(--text-primary, var(--text)); }
    body.page-staff-office-statistics .an-table th, body.page-staff-office-statistics .an-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border, var(--dept-card-border)); white-space: nowrap; color: var(--text-primary, var(--text)); }
    body.page-staff-office-statistics .an-table td { font-weight: 600; font-variant-numeric: tabular-nums; }
    body.page-staff-office-statistics .an-table thead th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--text-secondary, var(--muted)); background: var(--surface-soft, var(--surface)); font-weight: 700; }
    body.page-staff-office-statistics .an-table tbody th { font-weight: 600; color: var(--text-primary, var(--text)); }
    body.page-staff-office-statistics .an-table tbody tr:hover td, body.page-staff-office-statistics .an-table tbody tr:hover th { background: var(--surface-soft, rgba(148,163,184,.08)); }
    body.page-staff-office-statistics .an-table tbody tr:last-child th, body.page-staff-office-statistics .an-table tbody tr:last-child td { border-bottom: 0; }
    body.page-staff-office-statistics .an-table__staff-link { color: var(--primary, var(--navy-700)); font-weight: 600; text-decoration: none; }
    body.page-staff-office-statistics .an-table__staff-link:hover { text-decoration: underline; }
    body.page-staff-office-statistics .an-table-note { margin: 10px 0 0; font-size: 12px; color: var(--text-secondary, var(--muted)); }
    body.page-staff-office-statistics .an-feed { list-style: none; margin: 0; padding: 0; display: grid; gap: 0; }
    body.page-staff-office-statistics .an-feed__item { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--border, var(--dept-card-border)); }
    body.page-staff-office-statistics .an-feed__item:last-child { border-bottom: 0; padding-bottom: 0; }
    body.page-staff-office-statistics .an-feed__item:first-child { padding-top: 0; }
    body.page-staff-office-statistics .an-feed__dot { width: 10px; height: 10px; border-radius: 999px; margin-top: 6px; background: var(--navy-400, var(--primary)); flex: 0 0 10px; box-shadow: 0 0 0 4px var(--surface-soft, rgba(148,163,184,.15)); }
    body.page-staff-office-statistics .an-feed__dot.is-internal { background: var(--gold-500, var(--amber-500)); }
    body.page-staff-office-statistics .an-feed__body { min-width: 0; }
    body.page-staff-office-statistics .an-feed__text { margin: 0; font-size: 14px; color: var(--text-primary, var(--text)); line-height: 1.45; }
    body.page-staff-office-statistics .an-feed__comment { margin: 6px 0 0; padding: 8px 10px; border-left: 3px solid var(--border-strong, var(--dept-card-border)); background: var(--surface-soft, var(--surface)); border-radius: 6px; font-size: 13px; color: var(--text-secondary, var(--muted)); }
    body.page-staff-office-statistics .an-feed__meta { margin: 6px 0 0; font-size: 12px; color: var(--text-secondary, var(--muted)); display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    body.page-staff-office-statistics .an-feed__meta a { color: var(--primary, var(--navy-700)); font-weight: 600; text-decoration: none; }
    body.page-staff-office-statistics .an-feed__meta a:hover { text-decoration: underline; }
    body.page-staff-office-statistics .dept-activity { margin-top: 16px; }
    body.page-staff-office-statistics .dept-activity__head { margin-bottom: 12px; }
    body.page-staff-office-statistics .dept-activity__feed { display: grid; gap: 10px; }
    body.page-staff-office-statistics .dept-activity-item {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 12px 14px; border: 1px solid var(--border, var(--dept-card-border));
        border-radius: 12px; background: var(--surface); text-decoration: none; color: inherit;
    }
    body.page-staff-office-statistics .dept-activity-item--link:hover { border-color: var(--navy-300, var(--dept-active-border)); box-shadow: 0 4px 12px rgba(15,23,42,.06); }
    body.page-staff-office-statistics .dept-activity-item__dot {
        width: 10px; height: 10px; border-radius: 999px; margin-top: 6px; flex: 0 0 10px;
        background: var(--navy-500, var(--primary)); box-shadow: 0 0 0 4px var(--surface-soft, rgba(148,163,184,.15));
    }
    body.page-staff-office-statistics .dept-activity-item-main { display: flex; gap: 12px; justify-content: space-between; width: 100%; min-width: 0; }
    body.page-staff-office-statistics .dept-activity-item__copy { min-width: 0; }
    body.page-staff-office-statistics .dept-activity-item__title { margin: 0; font-size: 14px; font-weight: 600; color: var(--text-primary, var(--text)); line-height: 1.4; }
    body.page-staff-office-statistics .dept-activity-item__note { margin: 4px 0 0; font-size: 13px; color: var(--text-secondary, var(--muted)); line-height: 1.4; }
    body.page-staff-office-statistics .dept-activity-item__time { font-size: 12px; color: var(--text-secondary, var(--muted)); white-space: nowrap; }
    body.page-staff-office-statistics .dept-activity-item--extra { display: none; }
    body.page-staff-office-statistics .dept-activity__feed[data-expanded] .dept-activity-item--extra { display: flex; }
    body.page-staff-office-statistics .dept-activity__more,
    body.page-staff-office-statistics .dept-activity__view-all {
        display: inline-flex; margin-top: 12px; margin-right: 12px; font-size: 13px; font-weight: 600;
        color: var(--primary, var(--navy-700)); background: transparent; border: 0; padding: 0; cursor: pointer; text-decoration: none;
    }
    body.page-staff-office-statistics .dept-activity__more:hover,
    body.page-staff-office-statistics .dept-activity__view-all:hover { text-decoration: underline; }
    body.page-staff-office-statistics .dept-activity__empty { margin: 0; color: var(--text-secondary, var(--muted)); font-size: 14px; }
    @media (max-width: 1100px) {
        body.page-staff-office-statistics .office-stats-kpis.dept-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        body.page-staff-office-statistics .an-grid--trend, body.page-staff-office-statistics .an-grid--two { grid-template-columns: 1fr; }
        body.page-staff-office-statistics .an-mini-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 640px) {
        body.page-staff-office-statistics .office-stats-kpis.dept-stats { grid-template-columns: 1fr; margin-top: 10px; }
        body.page-staff-office-statistics .office-stats-kpis .dept-stat__value { font-size: 24px; }
        body.page-staff-office-statistics .an-quality { grid-template-columns: 1fr; }
        body.page-staff-office-statistics .an-mini-grid { grid-template-columns: 1fr 1fr; }
        body.page-staff-office-statistics .an-card { padding: 16px; }
    }
</style>
@endpush

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner office-stats-hero">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <p class="tr-kicker">{{ __('office.statistics.kicker') }}</p>
            <h1 class="tr-h1">{{ __('office.statistics.title') }}</h1>
            <p class="tr-privacy">{{ __('office.statistics.lede', ['office' => $office->name]) }}</p>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <div data-office-stats data-browser-url="{{ route($routeNames['browser'], array_merge($routeParams, $period->query())) }}">
            <div data-office-stats-period>
                @include('analytics.partials.period', ['period' => $period, 'data' => $data, 'routeNames' => $routeNames, 'routeParams' => $routeParams])
            </div>
            <div data-office-stats-content>
                @include('staff.office.partials.statistics-content', ['data' => $data, 'period' => $period, 'drilldown' => $drilldown, 'staffBreakdown' => $staffBreakdown, 'routeNames' => $routeNames])
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('[data-office-stats]');
    if (!root) return;
    const periodEl = root.querySelector('[data-office-stats-period]');
    const contentEl = root.querySelector('[data-office-stats-content]');
    let timer;

    async function load(url, push = true) {
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (!res.ok) { window.location.href = url; return; }
            const json = await res.json();
            if (json.period && periodEl) periodEl.innerHTML = json.period;
            if (json.content && contentEl) contentEl.innerHTML = json.content;
            root.dataset.browserUrl = url.replace('/statistics?', '/statistics/browser?').replace('/statistics&', '/statistics/browser&');
            if (push) history.pushState({}, '', url);
        } catch (_) { window.location.href = url; }
    }

    root.addEventListener('click', (e) => {
        const a = e.target.closest('.an-period__chip');
        if (!a) return;
        e.preventDefault();
        load(a.href);
    });

    root.addEventListener('change', (e) => {
        if (!e.target.closest('[data-analytics-range]')) return;
        const form = e.target.closest('form');
        if (!form) return;
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(form.action, window.location.origin);
            new FormData(form).forEach((v,k) => { if (v) url.searchParams.set(k, v); });
            load(url.toString());
        }, 150);
    });

    root.addEventListener('submit', (e) => {
        const form = e.target.closest('[data-analytics-range]');
        if (!form) return;
        e.preventDefault();
        const url = new URL(form.action, window.location.origin);
        new FormData(form).forEach((v,k) => { if (v) url.searchParams.set(k, v); });
        load(url.toString());
    });

    window.addEventListener('popstate', () => window.location.reload());
})();
</script>
@endsection
