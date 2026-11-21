# Sub-Ticketing for Complaints — Plan & Decisions

**Date:** 2026-11-22
**Status:** Decisions captured — implementation pending

---

## The feature, in one line

When a complaint is **finished** (Resolved / Closed) and the citizen isn't satisfied —
or the same issue simply persists — they can file a **new ticket that becomes a child
(sub-ticket) of the original**. Same issue, same location, like a follow-up.

## Decisions

| # | Decision | Detail |
|---|----------|--------|
| 1 | Sub-ticket semantics | **Follow-up child** of a resolved/closed complaint. (Not a "split" — clarified after early confusion; the user settled on follow-up semantics.) |
| 2 | Who creates it (v1) | **Citizen self-service** only. Staff-created follow-ups deferred to post-v1. |
| 3 | One at a time | A parent may hold **one open follow-up at a time**. Once that follow-up resolves / closes, the parent may accept a new follow-up later. |
| 4 | Ticket identity | Sub gets its **own ticket number** (`COMP-2026-XXXXX`), citizen-trackable like any complaint. |
| 5 | Lifecycle | Sub is a **new `Complaint` row** (status `Submitted`), independent, **seeded from the parent** (category / location / intake). Sub carries a **fresh SLA**; the parent **stays Closed**. |
| 6 | Reopen handling | **No reopen status** — a follow-up replaces reopen semantics. Follow-up = new ticket + parent linkage. |
| 7 | Privacy | Sub inherits the parent's visibility logic: private → owner sees; public → same transparency rules. |

## How it fits the existing model

- `ComplaintStatus` is **terminal** (`Closed → []`, `Resolved → [Closed]`), so a follow-up
  cannot be a status transition — it must be a new complaint row linked back as parent.
- Precedent for self-referencing complaints: `duplicate_of_id` / `duplicates()`.
- Ticket ID generation is handled by the `creating` hook (`COMP-{year}-{5-char}`), so a
  child complaint automatically receives its own number with no extra work.

## Open items deferred past v1

- Staff-created follow-ups.
- Whether/when multiple historical follow-ups should be browsable per parent beyond the
  single open child.
- SLA policy details for follow-ups (fresh clock per sub vs. inherited start time) — decided:
  **fresh SLA**.

## Next steps (when implementation starts)

1. Migration — nullable self-FK (`parent_id` → `complaints.id`) on `complaints`.
2. Model — `parent()` / follow-up relation on `Complaint`.
3. Service/controller — citizen follow-up creation on resolved/closed complaints
   (ownership-checked; one-open-follow-up guard).
4. UI — "file a follow-up" action on citizen resolved/closed complaint views + tracking.
5. i18n (`en` + `fil`), mail, notifications, tests.
6. Design-system guard + `docs/design-changelog.md` entry if UI is touched.

---

*Captured 2026-11-22 during requirements gathering for the sub-ticketing feature.*
