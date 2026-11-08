<style>
    .complaints-wrap { margin-top: -20px; position: relative; z-index: 1; }
    .tr-banner--compact { padding: 20px 0 40px; }
    .tr-banner--compact .tr-h1 { font-size: 24px; font-weight: 700; margin: 4px 0 8px; }
    @media (min-width: 768px) { .tr-banner--compact { padding: 20px 0 40px; } .tr-banner--compact .tr-h1 { font-size: 30px; } }
    .tr-goldbar { display: block; width: 40px; height: 3px; border-radius: 2px; background: var(--gold-500); margin: 0; }

    .tr-back {
        display: inline-flex; align-items: center; min-height: 44px;
        color: rgba(255,255,255,.85); font-size: 14px; font-weight: 600;
        text-decoration: none; margin-bottom: 4px;
    }
    .tr-back:hover { color: #fff; text-decoration: underline; text-underline-offset: 3px; }
    .tr-back:focus-visible { outline: none; box-shadow: var(--ring); border-radius: 4px; }

    /* Filter card */
    .tr-filters-card { padding: 16px; }
    .cf-row1 { display: flex; gap: 8px; align-items: stretch; }
    .cf-row1 .tr-search { flex: 1 1 auto; min-width: 0; }
    .tr-search { position: relative; display: block; }
    .tr-search__icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--faint); pointer-events: none; }
    .tr-search input {
        width: 100%; height: 48px; padding: 0 16px 0 44px;
        border: 1px solid var(--line); border-radius: var(--radius-sm);
        background: var(--surface); color: var(--text); font-size: 15px; box-sizing: border-box;
    }
    .tr-search input:focus { outline: none; border-color: var(--gold-500); box-shadow: var(--ring); }

    .cf-filters-btn { display: none; }
    .js .cf-filters-btn {
        display: inline-flex; align-items: center; gap: 6px; flex: none;
        height: 48px; padding: 0 14px; min-width: 48px;
        border: 1px solid var(--line); border-radius: var(--radius-sm);
        background: var(--surface); color: var(--navy-700); font-weight: 600; font-size: 14px;
        cursor: pointer;
    }
    .cf-filters-btn:focus-visible { outline: none; box-shadow: var(--ring); }
    .cf-count-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
        background: var(--navy-700); color: #fff; font-size: 11px; font-weight: 700;
    }

    .cf-chips-row {
        display: flex; gap: 6px; margin-top: 8px; overflow-x: auto; scroll-snap-type: x mandatory;
        white-space: nowrap; scrollbar-width: none; padding-bottom: 2px;
        -webkit-overflow-scrolling: touch;
    }
    .cf-chips-row::-webkit-scrollbar { display: none; }
    .cf-chip {
        flex: 0 0 auto; scroll-snap-align: start;
        display: inline-flex; align-items: center; height: 40px; padding: 0 14px;
        border-radius: 999px; border: 1px solid var(--line); background: var(--surface);
        color: var(--navy-700); font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap;
    }
    .cf-chip--active { background: var(--navy-700); color: #fff; border-color: var(--navy-700); }
    .cf-chip:focus-visible { outline: none; box-shadow: var(--ring); }

    .cf-panel { width: 100%; box-sizing: border-box; margin-top: 12px; padding: 16px;
        background: #f8fafc; border-radius: 12px; border: 1px solid var(--line-soft); }
    .cf-panel[hidden] { display: none; }
    .cf-panel-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    @media (max-width: 767px) { .cf-panel-grid { grid-template-columns: 1fr; } }
    .cf-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .cf-label { font-size: 13px; font-weight: 600; color: var(--muted); }
    .cf-field select {
        width: 100%; max-width: 100%; height: 48px; padding: 0 12px; box-sizing: border-box;
        border: 1px solid var(--line); border-radius: var(--radius-sm);
        background: var(--surface); color: var(--text); font-size: 14px;
    }
    .cf-panel-actions { display: flex; gap: 12px; margin-top: 12px; align-items: center; }
    .cf-apply {
        height: 48px; padding: 0 20px; border: none; border-radius: var(--radius-sm);
        background: var(--navy-700); color: #fff; font-weight: 600; font-size: 14px; cursor: pointer;
    }
    .cf-apply:focus-visible { outline: none; box-shadow: var(--ring); }
    .cf-clear { color: var(--muted); font-size: 14px; font-weight: 600; text-decoration: none; padding: 0 8px; }
    .cf-clear:hover { text-decoration: underline; }

    .cf-active-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .cf-active-chip {
        display: inline-flex; align-items: center; height: 32px; padding: 0 10px;
        border-radius: 999px; background: var(--navy-50); color: var(--navy-700);
        font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap;
    }
    .cf-active-chip:hover { background: var(--navy-100); }
    .cf-active-chip--clear { background: transparent; color: var(--muted); text-decoration: underline; }

    .cf-results { margin: 12px 0 0; font-size: 13px; color: var(--muted); font-variant-numeric: tabular-nums; }

    /* Card list */
    .complaint-list { list-style: none; margin: 12px 0 0; padding: 0; display: grid; gap: 12px; }
    .complaint-card {
        position: relative; background: var(--surface); border: 1px solid var(--line-soft);
        border-radius: 14px; box-shadow: 0 1px 2px rgba(15,23,42,.05);
        padding: 16px; margin: 0; cursor: pointer;
        transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
    }
    @media (min-width: 768px) { .complaint-card { padding: 16px 20px; } }
    .complaint-card:hover { border-color: var(--gold-500); box-shadow: 0 4px 16px rgba(201,168,76,.18); transform: translateY(-1px); }
    .complaint-card:active { transform: translateY(0); }
    .complaint-card .cc-chevron { transition: transform 160ms ease, color 160ms ease; color: var(--navy-700); }
    .complaint-card:hover .cc-chevron { transform: translateX(2px); color: var(--gold-700); }
    .complaint-card__link { display: block; color: inherit; text-decoration: none; position: relative; }
    .complaint-card__link::after { content: ""; position: absolute; inset: 0; }
    .complaint-card__link:focus-visible { outline: none; }
    .complaint-card__link:focus-visible::after { box-shadow: var(--ring); border-radius: 14px; }
    .complaint-card:focus-within { box-shadow: var(--ring); }

    .cc-row1 { display: flex; gap: 12px; align-items: flex-start; }
    .cc-title-col { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
    .cc-title {
        font-size: 15px; font-weight: 600; color: var(--text); line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .cc-meta { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--muted); flex-wrap: wrap; }
    .cc-dot { color: var(--faint); }
    .cc-brgy { font-size: 13px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px; }

    .cc-divider { height: 1px; background: #eef2f7; margin: 12px 0; }

    .cc-row2 {
        display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; min-height: 24px;
    }
    @media (max-width: 359px) {
        .cc-row2 .dept-badge { display: none; }
    }
    .status-pill { display: inline-flex; align-items: center; gap: 6px; height: 24px; padding: 0 10px;
        border-radius: var(--radius-pill); font-size: 11px; font-weight: 600; letter-spacing: .04em;
        text-transform: uppercase; white-space: nowrap; width: auto; }
    .verified-tick { display: inline-flex; align-items: center; color: var(--status-review-text); }
    .verified-tick svg { width: 16px; height: 16px; }
    .dept-badge {
        display: inline-flex; align-items: center; width: auto; padding: 2px 6px; border-radius: 6px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .cc-date { margin-left: auto; font-size: 13px; color: var(--muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .cc-chevron { color: var(--faint); flex: 0 0 auto; }

    /* Desktop card layout */
    @media (min-width: 768px) {
        .complaint-card { padding: 14px 20px; }
        .complaint-card__link { display: grid; grid-template-columns: 40px minmax(0,1fr) 176px 150px 64px 16px; gap: 16px; align-items: center; }
        .cc-row1 { display: contents; }
        .cc-title-col { display: block; }
        .cc-title { -webkit-line-clamp: 1; }
        .cc-meta { margin-top: 2px; }
        .cc-divider { display: none; }
        .cc-row2 { display: contents; }
        .cc-row2 > * { align-self: center; }
        .cc-date { margin-left: 0; }
    }

    .verified-tick { display: inline-flex; }

    .cf-results { margin: 12px 0 0; font-size: 13px; color: var(--muted); font-variant-numeric: tabular-nums; }

    .complaints-pagination {
        display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap;
        margin: 24px 0 40px;
    }
    .page-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 44px; min-height: 44px; padding: 0 12px;
        border-radius: var(--radius-sm); border: 1px solid var(--line);
        background: var(--surface); color: var(--navy-700);
        font-size: 14px; font-weight: 600; text-decoration: none;
    }
    .page-btn:hover { background: var(--navy-50); }
    .page-btn:focus-visible { outline: none; box-shadow: var(--ring); }
    .page-btn--current { background: var(--navy-700); color: #fff; border-color: var(--navy-700); }
    .page-btn--disabled { color: var(--faint); background: var(--navy-50); cursor: not-allowed; pointer-events: none; }
    .page-numbers { display: flex; gap: 6px; }
    .page-mobile-label { display: none; font-size: 13px; color: var(--muted); font-variant-numeric: tabular-nums; }
    @media (max-width: 767px) {
        .page-numbers { display: none; }
        .page-mobile-label { display: inline; }
    }

    .empty-state { padding: 32px 16px; text-align: center; }
    .empty-state__icon { display: inline-flex; width: 48px; height: 48px; border-radius: 50%; background: var(--navy-50); color: var(--navy-700); align-items: center; justify-content: center; margin-bottom: 12px; }
    .empty-state__icon svg { width: 24px; height: 24px; }
    .empty-state strong { display: block; font-size: 16px; color: var(--text); margin-bottom: 6px; }
    .empty-state p { margin: 0 0 12px; color: var(--muted); font-size: 14px; }

    .tr-button { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px; border-radius: var(--radius-sm); border: 1px solid var(--line); background: var(--surface); color: var(--navy-700); font-weight: 600; text-decoration: none; }

    .cf-empty-zero { margin: 8px 0 0; color: var(--muted); font-size: 13px; }

    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
</style>