        </div>

    </div>
</div>

<style>
    .workspace-card {
        background: var(--surface);
        border: 1px solid var(--line-soft);
        box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,0.06));
        padding: 20px;
        border-radius: 10px;
    }
    .workspace-card__header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--line-soft);
    }
    .workspace-card__header h2.tr-h2 {
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.01em;
        line-height: 1.25;
        color: var(--text);
        margin: 0;
    }
    .workspace-card__icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: rgba(16,41,75,0.08);
        color: var(--navy-800);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .map-container {
        height: 280px;
        width: 100%;
        isolation: isolate;
        z-index: 0;
    }

    .map-unavailable {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        margin: 0;
        padding: 24px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.5;
        text-align: center;
    }

    /* ═══════════════════════════════════════════════════════════
       THE BANNER IS NAVY. The chips that read on a white card read
       as dark-on-dark here, so they get an on-navy treatment:
       the ticket id takes the gold accent, the priority pill takes
       its family hue as a tint with white text.
       ═══════════════════════════════════════════════════════════ */
    .staff-complaint-detail .tr-banner .ticket-id {
        color: var(--gold-300);
    }
    .staff-complaint-detail .tr-banner .status-pill--neutral {
        background: color-mix(in srgb, var(--surface) 18%, transparent);
        color: var(--surface);
    }
    .staff-complaint-detail .tr-banner .status-pill--info {
        background: color-mix(in srgb, var(--status-submitted-dot) 32%, transparent);
        color: var(--surface);
    }
    .staff-complaint-detail .tr-banner .status-pill--warning {
        background: color-mix(in srgb, var(--status-progress-dot) 32%, transparent);
        color: var(--surface);
    }
    .staff-complaint-detail .tr-banner .status-pill--danger {
        background: color-mix(in srgb, var(--status-rejected-dot) 34%, transparent);
        color: var(--surface);
    }

    /* ═══════════════════════════════════════════════════════════
       COMPLAINT DETAIL — two balanced columns under the full-width
       header. Each column is its own flex stack, so the columns start
       at the same top edge and a short card never opens a hole in the
       other column (a shared grid row would).
       ═══════════════════════════════════════════════════════════ */
    .staff-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.75fr);
        gap: 24px;
        align-items: start;
        width: 100%;
    }
    /* 1024–1279px: the staff sidebar is still docked, so the workspace is
       narrow. The sidebar column keeps a usable minimum and the complaint
       column takes the rest — the cards are never squeezed below ~260px. */
    @media (min-width: 1024px) and (max-width: 1279px) {
        .staff-detail-grid {
            grid-template-columns: minmax(0, 1fr) minmax(260px, 0.6fr);
            gap: 20px;
        }
    }
    .staff-detail-col {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0;
    }
    .staff-detail-card,
    .staff-detail-col > * {
        min-width: 0;
        max-width: none;
        box-sizing: border-box;
    }

    /* Tablet and below: one column. The column wrappers dissolve so every
       card is a grid item, every card takes the full content width, and the
       cards keep the phone reading order — who reported it and where, then
       the complaint, then the actions. */
    @media (max-width: 1023px) {
        .staff-detail-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 16px;
        }
        .staff-detail-col { display: contents; }
        .staff-detail-grid > *,
        .staff-detail-col > * {
            width: 100%;
            min-width: 0;
            max-width: none;
            grid-column: 1 / -1;
            justify-self: stretch;
            box-sizing: border-box;
        }
        .staff-detail-card--resident { order: 1; }
        .staff-detail-card--location { order: 2; }
        .staff-detail-card--description { order: 3; }
        .staff-detail-card--timeline { order: 4; }
        .staff-detail-card--response { order: 5; }
        .staff-detail-card--evidence { order: 6; }
        .staff-detail-card--review { order: 7; }
        .staff-detail-card--marking { order: 8; }
        .staff-detail-card--followup { order: 9; }
        .staff-detail-card--assignment { order: 10; }
        .staff-detail-card--hold { order: 11; }
        .staff-detail-card--moderation,
        .staff-detail-card--publication,
        .staff-detail-card--closure { order: 12; }
    }

    @media (max-width: 599px) {
        .workspace-card {
            padding: 16px;
        }
        .review-status-subgrid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .review-locked-summary {
            grid-template-columns: 1fr !important;
        }
    }

    .review-status-subgrid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
    .review-locked-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .review-locked-summary__note {
        grid-column: 1 / -1;
    }

    /* Collapsible review/verification body — tokens only, no new colours. */
    .review-panel-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 12px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--navy-700);
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
    }
    .review-panel-toggle:hover { background: var(--navy-50); }
    .review-panel-toggle:focus-visible { outline: none; box-shadow: var(--ring-inset); }
    .review-toggle__icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .review-toggle__icon { transition: none; }
    }
    .review-panel-toggle[aria-expanded="false"] .review-toggle__icon { transform: rotate(180deg); }
</style>
    </div>
</div>
