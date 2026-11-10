# Audit Trail Drill-Down Browser — Design Plan

**Status:** approved, implemented (2026-11-10)
**Page:** `/admin/audit-trail` (`AdminController::auditIndex`, `resources/views/admin/audit/index.blade.php`)

## 1. Problem

The audit trail is one flat page with four filter dropdowns (Department, Actor, Action,
Search). To inspect a single office or person you must choose them from a dependent
dropdown and press "Filter trail". There is no directory of who exists, no sense of how
active each office or person is, and no way to move between entities without going back
to the dropdowns.

## 2. Locked decisions

| Decision | Choice |
|---|---|
| Core model | **Drill-down browser** — pick one department, one person, or "Everyone"; the trail below shows only that entity |
| People layout | **Grouped by department** |
| Existing filters | **Keep** search + Action as secondary filters |
| Counts | **Yes, total counts** per department/person |
| Person view | **Filtered audit trail** (stay on `/admin/audit-trail`) |
| People scope | **Everyone with audit entries** — staff/admins under their department, residents in a "Residents" group, plus a "System" row |
| System records | **A "System" row** in the directory |
| Count basis | **Total counts**, independent of search/action |
| URL state | Query string (see §4) |

## 3. The new page

The Department and Actor dropdowns are removed and replaced by a **directory card** with
two panes. The trail, day grouping, search and Action filter are unchanged.

```
┌────────────────────────────────────────────────────────────────────┐
│ Audit trail                                                        │
│ One immutable record of staff and administrator actions…           │
├────────────────────────────────────────────────────────────────────┤
│ ┌ Browse ────────────────────────────────────────────────────────┐ │
│ │ Showing: Engineering Office · 312 entries            [Clear]   │ │
│ │ ┌──────────────────────────┬─────────────────────────────────┐│ │
│ │ │ Everyone          1,248  │ ENGINEERING OFFICE              ││ │
│ │ │ DEPARTMENTS              │  JD  Juan dela Cruz  Staff   86 ││ │
│ │ │ [ENG] Engineering  312   │  AS  Ana Santos      Staff   41 ││ │
│ │ │ [HLTH] Health      208   │ HEALTH OFFICE                   ││ │
│ │ │ [GSO] General S.   174   │  …                              ││ │
│ │ │ …                        │ ADMINISTRATION                  ││ │
│ │ │                          │  SA  System Administrator       ││ │
│ │ │                          │ RESIDENTS                       ││ │
│ │ │                          │  PP  Pedro Penduko          12  ││ │
│ │ │                          │ SYSTEM                          ││ │
│ │ │                          │  ⚙  Automated records       57  ││ │
│ │ └──────────────────────────┴─────────────────────────────────┘│ │
│ └────────────────────────────────────────────────────────────────┘ │
│ Search [........................]  Action [All actions ▾] [Filter]  │
├────────────────────────────────────────────────────────────────────┤
│ Today                                                              │
│ … existing day-grouped trail, filtered …                           │
└────────────────────────────────────────────────────────────────────┘
```

**Behavior**

- **Everyone** (default) → `/admin/audit-trail`; full trail; people pane shows all groups.
- **Click a department** → `?department_id=3`; trail filters to that office; people pane
  narrows to that office's staff.
- **Click a person** → `?actor_id=7`; trail filters to that person; person row highlighted.
- **Click System** → `?system=1`; trail filters to `actor_id IS NULL`.
- **Everyone / Clear** → back to the bare route.
- Search + Action stay as secondary filters and persist while you move around.

**Counts** (totals, independent of search/action):

- Everyone = total rows.
- Department = rows tagged with that office (includes residents' filings routed there).
- Person = rows by that actor across offices.
- System = rows with no actor.

**Mobile**: the directory collapses into a "Browse" disclosure (mirroring the existing
`admin-audit-filters-collapsible` pattern); the "Showing: …" summary stays visible.

## 4. Security note — should the selection be in the URL?

**Recommendation: yes, keep it in the query string.** For a government system this is the
safer *and* more useful choice, for one reason above all:

> **A URL filters the view; it never authorizes it.** Every request still passes through
> `auth` + `email.verified` + `role:admin` middleware, and the controller re-validates
> every ID. A forged `?department_id=999999` can only narrow results within what the
> admin is already allowed to see — it cannot escalate privileges. Authority is never
> encoded in the URL.

Supporting reasons:

- **Reproducibility is an audit feature.** An investigator can bookmark exactly what they
  saw, share it with a supervisor, and return after a refresh. "Who inspected what" is
  itself traceable in the server access logs.
- **Refresh / back / forward / pagination all work**, with no lost state and no hidden
  in-page state.
- **The log is immutable** — filters mutate nothing, so URL state can't corrupt the record.
- **The one real caveat:** query strings land in browser history and web-server access
  logs. Entity IDs are not personal data. The existing search box is *already* a GET form,
  so free-text terms (which could contain a person's name) are already persisted in URLs
  today — that is the thing to protect via access-log retention and by not sharing search
  URLs, not the entity filter. Moving search to POST would break refresh/back and is not
  necessary.

Optional next step (not in this plan): a meta-audit row logging *access* to the audit
trail itself — "who watched the watchers".

## 5. URL scheme

| Selection | URL |
|---|---|
| Everyone | `/admin/audit-trail` |
| Department | `?department_id=3` |
| Person | `?actor_id=7` |
| System | `?system=1` |
| Secondary filters | `&search=…&action=…&page=…` (preserved) |

Existing `?department_id=` bookmarks/tests keep working. New params are validated
server-side; `system` and `actor_id` are mutually exclusive.

## 6. Data & queries

Two cheap aggregate queries (indexes already exist on `department_id`, `actor_id`,
`action`):

```php
$departmentCounts = ActivityLog::query()
    ->selectRaw('department_id, count(*) as total')
    ->whereNotNull('department_id')->groupBy('department_id')
    ->pluck('total', 'department_id');

$actorCounts = ActivityLog::query()
    ->selectRaw('actor_id, count(*) as total')
    ->whereNotNull('actor_id')->groupBy('actor_id')
    ->pluck('total', 'actor_id');
```

People are derived from the log itself (`$actorCounts` keys → users, with
`withTrashed()`), so residents, moved staff and soft-deleted accounts all appear:

- staff/admin with a department → under that office,
- admins (no department) → **Administration** group,
- citizens → **Residents** group,
- automated entries → **System** group.

## 7. Design system work (required before the view ships)

Per `AGENTS.md`, the new component is documented first:

1. **`docs/design-system.md` §5.33 "Audit directory"** — purpose, anatomy (contexts pane /
   people pane / counts / selected state), variants, states, tokens, responsive + a11y.
2. **`resources/css/tokens.css`** — only if a new token is truly needed (the plan reuses
   existing tokens).
3. **`/design-system`** — add a swatch/demo of the directory.
4. **Changelog** — entry in `docs/design-system.md` §12 **and**
   `docs/design-changelog.md` (same commit), plus the §11 Definition of Done checklist.
5. Run `php scripts/design-system-guard.php`.

## 8. Files touched

| File | Change |
|---|---|
| `app/Http/Controllers/AdminController.php` | `auditIndex`: counts, people groups, selection state; validate `system`/`actor_id` |
| `resources/views/admin/audit/index.blade.php` | Replace dept/actor dropdowns with the directory; add selection summary; keep search/action + trail |
| `resources/css/pages/admin-workspace.css` | Directory styles (tokens only) |
| `lang/en/audit.php` + `lang/fil/audit.php` | New keys |
| `docs/design-system.md`, `docs/design-changelog.md` | Component spec + changelog |
| `resources/views/pages/design-system.blade.php` | Swatch/demo |
| `tests/Feature/ActivityLogTest.php` + new tests | Directory counts, person filter, system filter, search/action persistence, non-admin access |

## 9. Test plan

- Department filter still works (existing `test_admin_audit_page_can_filter_by_department`
  must stay green).
- New: directory lists all departments with correct counts; selecting a person filters to
  `actor_id`; the System row filters to null actor; counts unaffected by search; the
  legacy combined `?department_id=&actor_id=` link still filters both; nobody is listed
  in two groups; non-admin is bounced to the dashboard by the existing `role:admin`
  middleware (the app redirects rather than 403ing).
- Browser verification at 393px and 1310px, `en` + `fil` (screenshots optional per the
  owner waiver; evidence recorded in the changelog).

## 10. Open questions

1. Preserve search/action when switching entity? — **Yes** (current query params carry over).
2. Label for admins — **"Administration"** group.
3. Department counts include residents' filings — a one-line hint is included in the
   directory summary.
4. Mobile — the directory collapses behind **"Browse"** (mirroring Filters).
