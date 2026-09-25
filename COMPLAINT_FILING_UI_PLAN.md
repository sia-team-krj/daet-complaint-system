# Complaint Filing UI Redesign Plan

**Date:** September 25, 2026
**Scope:** Redesign the resident complaint filing interface for clarity and review-first submission
**Status:** Implemented and verified on September 25, 2026

## Goal

Make filing a complaint feel short, clear, and trustworthy while preserving the review-first workflow.

## New layout

1. **Compact header**
   - Explain that the department verifies the report.
   - Remove marketing-style hero content and excessive decoration.

2. **Progress stepper**
   - Step 1: Category and department
   - Step 2: Details and evidence
   - Step 3: Location and review
   - Show the active/completed state clearly.

3. **Category step**
   - Keep the existing category-to-department routing.
   - Show the assigned department immediately after selection.
   - Use concise helper copy.

4. **Details step**
   - Keep title and description.
   - Make the review notice prominent: no resident urgency selection.
   - Keep required photo upload with clear evidence and GPS guidance.

5. **Location and final review step**
   - Keep optional address and Leaflet map.
   - Show a concise submission summary before the final action.
   - Keep terms confirmation and submit action.

6. **Responsive behavior**
   - Single-column flow on mobile.
   - Sticky step navigation only where it does not obstruct the form.
   - Preserve keyboard focus and validation visibility.

## Photo and location requirement

- A photo is required for every new complaint.
- Read embedded EXIF GPS coordinates from the uploaded photo on the server.
- Use photo GPS coordinates as the preferred location source.
- Keep the address/map fields as a fallback when a photo does not contain GPS metadata.
- Show residents that location-enabled photos help the department verify the report.

## Constraints

- Do not reintroduce resident urgency selection.
- Do not expose private identity fields.
- Preserve existing category routing, coordinates, image upload, validation, and submission endpoint.
- Keep the current map and upload JavaScript behavior.
- Use existing navy/gold/cream design tokens and authored SVG icons.

## Verification

- Complaint form renders without urgency controls.
- Category routing, validation, upload, map, and submit behavior remain functional.
- Add/update feature coverage for the redesigned form.
- Run full tests, Blade cache, production build, and diff checks.
