# Staff Activity Navigation Cleanup Plan

**Date:** September 25, 2026
**Scope:** Remove duplicated My Activity navigation from the staff dashboard
**Status:** Implemented and verified on September 25, 2026

## Problem

The staff dashboard currently presents the same destination in three places:

1. Staff sidebar: My activity
2. Main header: View my activity
3. Main tab bar: My activity

This makes the workspace feel repetitive and gives the page no clear primary navigation.

## Decision

- Keep the staff sidebar as the single primary navigation.
- Remove the duplicate header action button.
- Remove the redundant main tab bar.
- Keep the current queue/activity state in the page title and supporting copy.
- Preserve direct URLs and existing staff activity behavior.
- Keep the staff sidebar active state accurate for both views.

## Acceptance Criteria

- My Activity appears once in the staff sidebar.
- The dashboard header changes clearly between Department queue and My activity.
- Queue and activity links remain functional.
- No behavior or authorization changes.
- Tests and build pass.
