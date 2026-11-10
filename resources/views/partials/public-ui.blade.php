{{--
    Shared public components (extracted from the Transparency register per the
    design system: a component consumed by a second page is extracted, not forked).

    Contains: .tr-container · .tr-stats/.tr-stat · .ticket-id · .cat-icon(+variants)
    · .status-pill(+variants) · .progress/.progress__seg.

    Consumed by: resources/views/livewire/transparency-register.blade.php (via
    pages.transparency.partials.component-styles is no longer self-contained --
    include this partial before it) and resources/views/pages/home/guest.blade.php.
--}}
<style>
            .tr-container {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 24px;
        }

        @media (max-width: 768px) {
            .tr-container { padding: 0 16px; }
        }

                /* ── Stat strip ─────────────────────────────────────── */
        .tr-stats {
            position: relative; /* joins the banner's stacking phase so the cards paint over the banner background */
            z-index: 1;
            margin-top: -32px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 900px) {
            .tr-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        }

        .tr-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: var(--surface);
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
        }

        @media (max-width: 768px) {
            .tr-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .tr-stat { padding: 12px; gap: 8px; }
            .tr-stat__icon { width: 32px; height: 32px; flex: 0 0 32px; }
            .tr-stat__icon svg { width: 16px; height: 16px; }
            .tr-stat__value { font-size: 24px; }
            .tr-stat__label { font-size: 10.5px; margin-top: 2px; }
        }

        .tr-stat__icon {
            flex: 0 0 auto;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .tr-stat__icon svg { width: 18px; height: 18px; }

        .tr-stat__icon--navy   { background: var(--navy-50);           color: var(--navy-700); }
        .tr-stat__icon--amber  { background: var(--status-progress-bg); color: var(--status-progress-text); }
        .tr-stat__icon--green  { background: var(--status-resolved-bg); color: var(--status-resolved-text); }
        .tr-stat__icon--sky    { background: var(--cat-flood-bg);       color: var(--cat-flood-fg); }

        .tr-stat__value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
            color: var(--text);
            font-variant-numeric: tabular-nums lining-nums;
            letter-spacing: -0.02em;
            text-align: left;
        }

        .tr-stat__label {
            display: block;
            margin-top: 4px;
            font-size: 13px;
            color: var(--muted);
            text-align: left;
        }

                .transparency-page .ticket-id,
        .ds-root .ticket-id,
        .ds-embed .ticket-id,
        .lp-root .ticket-id {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 600;
            color: var(--navy-700);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        /* Category icon circles */
        .cat-icon {
            flex: 0 0 auto;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .cat-icon svg { width: 18px; height: 18px; }

        .cat-icon--road        { background: var(--cat-road-bg);        color: var(--cat-road-fg); }
        .cat-icon--noise       { background: var(--cat-noise-bg);       color: var(--cat-noise-fg); }
        .cat-icon--flood       { background: var(--cat-flood-bg);       color: var(--cat-flood-fg); }
        .cat-icon--waste       { background: var(--cat-waste-bg);       color: var(--cat-waste-fg); }
        .cat-icon--streetlight { background: var(--cat-streetlight-bg); color: var(--cat-streetlight-fg); }
        .cat-icon--other       { background: var(--cat-other-bg);       color: var(--cat-other-fg); }

                .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 26px;
            padding: 0 10px;
            border-radius: var(--radius-pill);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-pill__dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-pill.submitted   { background: var(--status-submitted-bg); color: var(--status-submitted-text); }
        .status-pill.submitted   .status-pill__dot { background: var(--status-submitted-dot); }
        .status-pill.under_review,
        .status-pill.verified,
        .status-pill.assigned    { background: var(--status-review-bg);    color: var(--status-review-text); }
        .status-pill.under_review .status-pill__dot,
        .status-pill.verified     .status-pill__dot,
        .status-pill.assigned     .status-pill__dot { background: var(--status-review-dot); }
        .status-pill.in_progress { background: var(--status-progress-bg); color: var(--status-progress-text); }
        .status-pill.in_progress .status-pill__dot { background: var(--status-progress-dot); }
        .status-pill.resolved    { background: var(--status-resolved-bg); color: var(--status-resolved-text); }
        .status-pill.resolved    .status-pill__dot { background: var(--status-resolved-dot); }
        .status-pill.rejected    { background: var(--status-rejected-bg); color: var(--status-rejected-text); }
        .status-pill.rejected    .status-pill__dot { background: var(--status-rejected-dot); }
        .status-pill.closed      { background: var(--status-closed-bg);   color: var(--status-closed-text); }
        .status-pill.closed      .status-pill__dot { background: var(--status-closed-dot); }
        /* Review statuses ride the same four families — no new colours:
           pending is in flight but stable, needs-information is action
           needed, duplicate is cooling off, escalated is negative. */
        .status-pill.pending     { background: var(--status-review-bg);    color: var(--status-review-text); }
        .status-pill.pending     .status-pill__dot { background: var(--status-review-dot); }
        .status-pill.needs_information { background: var(--status-progress-bg); color: var(--status-progress-text); }
        .status-pill.needs_information .status-pill__dot { background: var(--status-progress-dot); }
        .status-pill.duplicate    { background: var(--status-closed-bg);   color: var(--status-closed-text); }
        .status-pill.duplicate    .status-pill__dot { background: var(--status-closed-dot); }
        .status-pill.escalated    { background: var(--status-rejected-bg); color: var(--status-rejected-text); }
        .status-pill.escalated    .status-pill__dot { background: var(--status-rejected-dot); }

        .progress {
            display: flex;
            gap: 3px;
            width: 150px;
        }

        .progress__seg {
            flex: 1 1 0;
            height: 4px;
            border-radius: 2px;
            background: var(--line);
        }

        .progress__seg--complete { background: var(--navy-700); }
        .progress__seg--current  { background: var(--gold-500); }
</style>
