# Transparency Page Plan

> **Superseded:** After user feedback, the page was intentionally simplified to two sections only. See `TRANSPARENCY_PAGE_SIMPLIFICATION_PLAN.md` for the current implementation plan.

**Date:** September 25, 2026
**Scope:** Public complaint transparency, location heatmap, and privacy-safe civic insights
**Status:** Superseded by the two-section simplification plan

## Goal

Turn the transparency page into a useful public accountability surface rather than a static information page. Residents should be able to see where issues are being reported, what categories are most common, and how municipal work is progressing without exposing private complaint data.

## Transparency Principles

- Show only complaints explicitly marked `is_public = true`.
- Use public-safe resident aliases, never private names or contact details.
- Do not place exact private home coordinates on the map.
- Display aggregate location intensity rather than a raw list of sensitive addresses.
- Keep complaint status and category information understandable to everyday residents.
- Preserve a direct path to submit or track a complaint.

## Page Design

1. **Public overview header**
   - Explain what the page shows.
   - Highlight total public complaints, resolved count, active departments, and current reporting period.

2. **Location heatmap**
   - Leaflet map centered on Daet.
   - Heat intensity from public complaint coordinates.
   - Markers for public complaints with category/status popups.
   - Graceful empty state when no geotagged public complaints exist.
   - Clear privacy note explaining that only public reports appear.

3. **Category and status insights**
   - Category breakdown bars.
   - Status breakdown with plain-language labels.
   - Useful “good aspects” callouts: public tracking, department accountability, visible resolution progress, and privacy safeguards.

4. **Recent public reports**
   - Ticket-safe public report cards.
   - Alias/display name instead of private identity.
   - Category, department, status, date, and a path to the complaint tracker.

5. **Resident action**
   - Prominent report issue and track complaint calls to action.

## Implementation

- Update `TransparencyController` to aggregate only public complaints.
- Rebuild `resources/views/pages/transparency/index.blade.php` using the existing navy/gold visual system.
- Add Leaflet assets defensively so a CDN failure does not break the page.
- Ensure both guest and authenticated layouts render the page style stack.
- Add a privacy-safe JSON payload for the heatmap, with coordinates clustered to three decimal places.
- Add feature tests for public/private filtering, spam exclusion, map data, and transparency page rendering.
- Verify on desktop/mobile through build and markup checks.

## Acceptance Criteria

- Private complaints never appear in the transparency data or map.
- Public geotagged complaints render as heatmap intensity and markers.
- Empty map data has a useful explanation.
- Dashboard totals match public-only query results.
- The page communicates concrete transparency benefits.
- Full test suite and production build pass.
