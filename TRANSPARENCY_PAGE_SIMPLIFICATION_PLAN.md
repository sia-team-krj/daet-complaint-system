# Transparency Page Simplification Plan

**Date:** September 25, 2026
**Scope:** Reduce the transparency page to a direct public heatmap and searchable complaint register
**Status:** Implemented and verified on September 25, 2026

## Product decision

The transparency page should answer two questions quickly:

1. **Where are public reports concentrated?**
2. **What public complaints are currently recorded?**

The page will contain exactly two content sections:

1. **Heatmap** — approximate public report areas only.
2. **Complaints** — all public complaints in a searchable, filterable table.

Private complaints will not be searchable on the public page. Authenticated residents will use **My Complaints** to view and track the reports they filed.

## Privacy decision

- Do not render exact complaint coordinates as individual markers.
- Cluster coordinates to a coarse grid before sending them to the browser.
- Render a heat layer only; do not show address popups.
- Show a broad location label such as `Barangay VI, Daet` in the table.
- Keep names, email addresses, and phone numbers out of public data.
- Exclude private complaints and likely spam from all public queries.
- Public detail pages expose only broad location labels, aliases, public logs, and media attached to public complaints; internal notes and private identity data remain hidden.
- Administrator navigation does not link to the public transparency register.

## Heatmap fix

- Keep Leaflet below the fixed navbar using an isolated map stacking context.
- Override Leaflet control stacking inside the map so controls cannot rise over the navbar.
- Add scroll margin to account for the fixed navbar.
- Keep a text/table fallback when Leaflet or the heat plugin cannot load.

## Complaint table

- Show every public, non-spam complaint.
- Add client-side search across ticket, title, category, department, location, and reporter alias.
- Add department, category, and status filters.
- Show ticket, complaint, category, department, broad location, status, and date.
- Link each ticket to a privacy-safe public detail page with status dates, progress, resident evidence, and public documentation.
- Keep the table responsive with a mobile-first card layout and a desktop table layout.
- Link authenticated residents to `/complaints`; do not add a duplicate public tracker.

## Verification

- Add tests for public/private filtering, spam exclusion, approximate coordinates, table rendering, filters, and authenticated layout assets.
- Add public detail coverage for progress dates, resident evidence, public documentation, privacy redaction, and administrator navigation.
- `php artisan test`: 83 tests / 397 assertions passing.
- `npm run build` and `php artisan view:cache` complete successfully.
- Impeccable detection completed with no findings on the changed transparency surfaces.
