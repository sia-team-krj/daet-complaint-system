# Audit Directory — Scaling Plan (v2)

**Status:** approved and implemented (2026-11-10)
**Page:** `/admin/audit-trail` (`AdminController::auditIndex`, `app/Services/AuditDirectory.php`, `resources/views/admin/audit/`)
**Follows:** `docs/audit-trail-redesign-plan.md` (the drill-down redesign that shipped)
**Owner decisions:** cap at 8 + "See all N" pager (25/page) · inline accordion in the pane ·
server-side type-ahead · 60-second cache.

---

## 1. The problem, measured

The v1 directory renders **one row per actor that appears in the log**, in full, on
every request. Measured on the local database with 3,000 synthetic residents +
3,000 synthetic log rows (inserted inside a transaction and rolled back):

| metric | 3,000 people | extrapolated @ 20,000 |
|---|---|---|
| controller (queries + roster) | 253 ms | ~1.4 s |
| view render | 95 ms | ~600 ms |
| **total server time** | **347 ms** | **~2 s** |
| **HTML response size** | **2.87 MB** | **~19 MB** |
| roster DOM rows | ~3,000 | 20,000+ |
| `GROUP BY actor_id` aggregate | 4.5 ms | ~30 ms (index-only scan) |
| `count(*)` | 1.0 ms | ~10 ms |

(The ~950 bytes per person row is the link + avatar + name + role + count.)

**The aggregates are not the problem — the roster is.** The counts come from an
index-only scan; the cost is hydrating 3,000 `User` models and rendering 3,000
`<a>` elements per request. At 20k residents the page stops being usable at all:
a 19 MB response on a government office's connection.

## 2. What is bounded and what is not

| set | size | grows with |
|---|---|---|
| offices (contexts pane) | 8 | the municipality, not users |
| staff + admins | tens–hundreds | employees |
| **residents with activity** | **unbounded** | filings |
| system records | 1 pseudo-row | nothing |

So the roster must be **demand-driven**: bounded rows by default, the rest loaded
only when an admin asks for them. Searching for one person must not require
scrolling past thousands of names.

## 3. Proposed design (v2)

### 3.1 Bounded roster — cap every group

Each group renders **at most `AUDIT_ROSTER_PREVIEW` (8) rows**. A group with more
people gets a footer:

```
RESIDENTS (3,010 people)
  JD  Jhosweem Dave Aguilar   Citizen   6
  ...  (8 rows shown)
  Showing 8 of 3,010        [ See all 3,010 → ]
```

- Staff/admins are usually under the cap, so they still read as a complete list.
- Residents are the bulk, so they are the group that gets paged — 25 at a time.
- Selecting an office lists that office's staff (bounded by employees).

### 3.2 "See all" = paginated, on demand

`[ See all N ]` opens the group's list, **25 people per page**, in place:

```
GET /admin/audit-trail?people_group=residents&people_page=2
```

The list renders inside the same pane; the page behind it is untouched.
**Transport decision: the query string, not a fetch.** The pager is a set of
links, so it works without JavaScript, survives a refresh, can be bookmarked and
shared with a colleague, and adds no second markup for the row. The *type-ahead*
is the only part that fetches, and it fetches a **rendered fragment** from
`/admin/audit-trail/people` — the same Blade partial the page renders — so the
row markup exists in exactly one file either way.

### 3.3 Directory search — find a person without scrolling

A search field inside the browser card:

```
[ Find a person… ]   →  matches across every group, server-side
```

- `?people_q=` — matches name/email, returns the **top 20** matches with office +
  entry count, each linking to the same `?actor_id=` contract.
- Only people who appear in the log are searchable — this is an audit surface,
  not a register of registered accounts.
- The field is *not* the trail search (`search=`) — different target, kept apart.
- The type-ahead fetches the rendered fragment and swaps it in; without
  JavaScript the form submits and the page renders the same results.

### 3.4 Counts: keep them, but stop re-aggregating

- Department counts and the total stay as they are (one `GROUP BY`, index-only).
- **Per-person counts** are fetched only for the ids actually on screen
  (`WHERE actor_id IN (…)`), instead of aggregating every actor.
- **Group head counts** ("3,010 people") come from the same snapshot.
- The one query that scales with the log — the per-actor aggregate — is
  **cached for 60 s** (`Cache::remember`), together with the roster ids. Counts
  that are 60 s stale on an audit screen are fine; a 2 s page load is not. The
  office list is flushed from the cache when an office is created, renamed,
  archived or restored.
- The trail itself is unaffected: the selected person's entries are always exact.

### 3.5 Server work after v2

| step | rows touched |
|---|---|
| cached snapshot (per 60 s) | 1 aggregate + 1 narrow user pass |
| visible roster slice (≤ 8 rows × ~7 groups) | ~56 |
| people search | ≤ 20 |
| expanded group page | 50 |
| trail | 30 |

**Measured with 3,012 actors in the log** (the synthetic set from §1):

| state | before | after |
|---|---|---|
| Everyone (preview) | 347 ms · 2.87 MB | **62 ms · 179 KB** |
| office selected | — | 43 ms · 170 KB |
| person selected | — | 40 ms · 101 KB |
| group expanded (page 3) | — | 47 ms · 195 KB |
| people search | — | 48 ms · 169 KB |
| snapshot rebuild (once per 60 s) | — | 20 ms |

The roster renders 57 rows whatever the log holds.

### 3.6 Mobile

Unchanged in spirit: the directory stays behind the "Browse departments and
people" disclosure, the summary row stays visible, the caps and the pager behave
identically (the pager is the same rows, so the touch targets are the same 44 px).

### 3.7 URL contract

| purpose | URL |
|---|---|
| entity selection (unchanged) | `?department_id=` / `?actor_id=` / `?system=1` |
| trail filters (unchanged) | `&search=…&action=…&page=…` |
| directory search (new) | `&people_q=…` |
| expanded group page (new, ephemeral) | `&people_group=…&people_page=…` |

Everything stays GET + query string, under the same security note as the v1 plan:
**a URL filters the view, it never authorizes it** — `role:admin` middleware still
runs on every request, and the controller re-validates every id.

## 4. Decisions taken (owner, 2026-11-10)

1. **Roster strategy** — cap at 8 rows per group + `See all N` pager (25/page). Nothing is ever dumped.
2. **"See all" presentation** — inline accordion in the pane; the pager is
   query-string driven so it works without JavaScript and can be shared.
3. **Directory search** — server-side type-ahead over people with activity,
   answered with a rendered Blade fragment (one row markup for every path).
4. **Count freshness** — 60-second cache for the snapshot; office changes flush
   it.

## 5. What was built

| # | Step | Files |
|---|---|---|
| 1 | Cached snapshot (totals, per-actor counts, roster ids), sliced roster, people search | `app/Services/AuditDirectory.php` |
| 2 | Controller: selection state, roster groups, people fragment endpoint, cache flush on office changes | `app/Http/Controllers/AdminController.php`, `routes/web.php` |
| 3 | View: scope band, search band, scope rail, roster, partials (one row markup) | `resources/views/admin/audit/**` |
| 4 | CSS (tokens only, desktop + mobile) | `resources/css/pages/admin-workspace.css` |
| 5 | Strings (en + fil) | `lang/{en,fil}/audit.php` |
| 6 | Docs: §5.33, §12 changelog, design changelog, this plan | `docs/` |
| 7 | `/design-system` demo | `resources/views/pages/design-system.blade.php` |
| 8 | Tests: bounded roster, expanded group paging, people search, JSON fragment, legacy URLs | `tests/Feature/ActivityLogTest.php` |
| 9 | Re-run the scaling benchmark | `/tmp/opencode/audit-ui/measure.php` |

## 6. Still open (not in this pass)

- The trail itself still pages 30 entries at a time; a 1M-row log will want a
  date-range filter on the trail, not just on the directory.
- The per-actor aggregate is still a full pass over the log every 60 s. At a
  million entries that is a few hundred milliseconds once a minute; if it ever
  hurts, the next step is a `activity_log_actors` counter table maintained on
  write, not a bigger cache.
