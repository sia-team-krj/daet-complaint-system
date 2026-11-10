# Soft Navigation — swapping page regions without a reload

**Status:** canonical technique for this repository (2026-11-10)
**Reference implementations:** the admin audit trail (`/admin/audit-trail`), the admin complaints queue (`/admin/complaints`) and the public transparency feed (`/transparency/complaints`)
**Skill:** `.opencode/skills/soft-navigation/` (the same recipe, operational)

---

## 1. The problem

A page that lists, filters and paginates re-draws itself on every click: click
an office, turn a page, type in a search — and the browser discards the DOM,
re-downloads the CSS and JS, re-renders the navbar, and scrolls you back to the
top. On a municipal connection that is seconds of nothing, and it throws away
the admin's place.

The fix is not a SPA. It is **one page load, then fragments**.

## 2. The rules (these are the invariants)

1. **The URL is the truth.** Every piece of state lives in the query string.
   A soft navigation *writes* the URL; it never owns it. Refresh, Back,
   Forward, a bookmark and a shared link all behave exactly as before.
2. **The server is the only renderer.** The browser never builds markup; it
   swaps HTML the server produced. One Blade file owns each list — the page
   and the fragments are the same file.
3. **Graceful degradation.** With JavaScript off, every link is a link and every
   form is a form: the page still works, it just reloads. A failed request
   falls back to a real navigation. The technique is an enhancement, never the
   only path.
4. **Never redraw what the user is typing in.** Inputs live outside the swapped
   regions, so focus, the caret and the text survive.
5. **The state helpers are shared.** The page and the endpoint call the same
   controller helpers, so a fragment can never disagree with the page.

## 3. The architecture

Four pieces. Everything else is the same on every page.

```
(a) Blade partials      one file per swappable region
(b) A JSON endpoint     renders those partials for a given URL's state
(c) Swap targets        the regions carry a data-* attribute
(d) A client controller fetches, swaps, and updates the history
```

### (a) Partials — one file per region

```
resources/views/<area>/partials/
    scope.blade.php        the scope band
    contexts.blade.php    the left rail
    roster.blade.php      the right panel
    trail.blade.php       the table + its pager
```

The page includes them; the endpoint renders them. **One markup per list.**

### (b) The endpoint — the server renders the fragments

```php
Route::get('/things/browser', [ThingController::class, 'browser'])
    ->name('things.browser');
```

```php
public function browser(Request $request): JsonResponse
{
    $scope = $this->thingScope($request);        // shared with the page
    $rows   = $this->thingRows($request, $scope); // shared with the page

    return response()->json([
        'scope'   => view('things.partials.scope',   ['scope' => $scope])->render(),
        'rows'    => view('things.partials.rows',    ['rows' => $rows])->render(),
    ]);
}
```

Give the endpoint the same URL shape as the page (`?scope=…&page=…`) so the
query string travels unchanged.

### (c) Swap targets

```blade
<div class="thing-panel" data-thing-scope>
    @include('things.partials.scope')
</div>

<div class="thing-rows" data-thing-rows>
    @include('things.partials.rows')
</div>
```

`data-*` attributes are the contract between the view and the script.

### (d) The client controller

```js
const endpoint = new URL('{{ route('things.browser') }}', window.location.origin);
const scopeEl = document.querySelector('[data-thing-scope]');
const rowsEl  = document.querySelector('[data-thing-rows]');

async function navigate(url, parts) {
    try {
        const target = new URL(endpoint, window.location.origin);
        target.search = url.search;              // carry the state over
        const response = await fetch(target, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) throw new Error('failed');
        const payload = await response.json();
        if (parts.includes('scope')) scopeEl.innerHTML = payload.scope;
        if (parts.includes('rows'))  rowsEl.innerHTML  = payload.rows;
        history.pushState({ soft: true }, '', url.toString());
    } catch (error) {
        window.location.href = url.toString();     // never leave stale fragments
    }
}
```

Then one click delegate for the whole page:

```js
document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target.closest('a[href]');
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

    const url = new URL(link.href, window.location.origin);
    if (url.origin !== window.location.origin) return;
    if (url.pathname !== pagePath) return;        // only this page's routes

    event.preventDefault();
    navigate(url, partsFor(link));
});
```

`partsFor(link)` decides which regions change: a scope link changes everything,
a pager link changes only its own region.

## 4. The recipe — applying it to another page

1. **Find the regions.** What changes when the admin clicks? Usually: a header
   band, a filter/selection area, a list, and a table. Each becomes a partial.
2. **Move the state into the URL.** Anything that changes what is shown must be
   a query parameter. If it is not in the URL, it cannot survive a refresh, a
   share or a Back button — fix that first.
3. **Extract the controller state into helpers.** `scope()`, `rows()`,
   `selection()`. The page and the endpoint both call them — that is what keeps
   the fragments honest.
4. **Add the endpoint** next to the page route, inside the same middleware
   group. It returns the fragments as JSON.
5. **Add the data attributes** to the regions, and keep every input *outside*
   the swapped regions.
6. **Add the client controller** (fetch → swap → `pushState`) and the click
   delegate.
7. **Intercept the forms** the same way: build the URL from the form's action
   and fields, then `navigate(url, parts)`.
8. **Handle `popstate`** — Back and Forward must re-fetch, not push again.
9. **Pin the paginator's path** (see pitfall 2).
10. **Verify with the marker trick** (§6).

## 5. The pitfalls (each one cost real time on the audit trail)

| # | Pitfall | Symptom | Fix |
|---|---|---|---|
| 1 | `form.action` is shadowed by a control named `action` | The URL becomes `/[object HTMLSelectElement]` | `form.getAttribute('action')` |
| 2 | Laravel's paginator links inherit the **current request** URL | A fragment rendered by the endpoint links back to the endpoint | `->withPath(route('x.index', [], false))` on the paginator |
| 3 | `route()` returns an **absolute** URL | `url.pathname !== route(...)` is never true, so nothing is intercepted | Compare `.pathname` to a `new URL(route(...)).pathname` |
| 4 | Re-rendering the region that holds the focused input | The caret and the typed text are lost mid-search | Keep inputs outside the swapped regions |
| 5 | `details` + `display: contents` | A closed `<details>` hides its content even with `display: contents` | Force `open` on desktop (`syncDetails()`), as the Filters disclosure already does |
| 6 | A prevented submit leaves the button busy | The "Filter" button stays spinning forever | The shared loader clears itself on the next tick; or mark the form `data-loading-managed` |
| 7 | A stale `page` after a new filter | Page 7 of the *new* filter, which may not exist | Delete `page` when the filter changes |
| 8 | Rapid typing fires many requests | Older responses arrive last and overwrite the newer ones | `AbortController` + ignore aborted responses |
| 9 | Swapping the region that holds the scroll position | The page jumps | Scroll the region, not the window, or restore the offset |
| 10 | The endpoint is not authorized like the page | A JSON route leaks data | Put it in the **same** middleware group as the page |
| 11 | The fragment endpoint answers a person | Raw JSON in the address bar | Answer fragments only to a script (JSON/XHR); redirect anything else to the page |
| 12 | Interception decided by "is the link inside a region" | A link that moved in the markup falls back to a full page load | Decide by **pathname**: any link to the same page is a change of state |

## 6. Verifying that nothing reloads

A marker on `window` survives a fragment swap and dies on a real navigation:

```js
await page.evaluate(() => { window.__softMarker = 'alive'; });
// … click / type …
const soft = await page.evaluate(() => window.__softMarker === 'alive');
```

In Playwright, click through **every** navigation the page offers — scope
changes, next/previous, the filters, the search, Back — and assert `soft` each
time. A `RELOAD` means one path was missed.

Checklist:

- [ ] Every link inside the page is intercepted (or deliberately excluded).
- [ ] Every form is intercepted, and its URL is correct.
- [ ] The address bar matches what is on screen after every action.
- [ ] Back and Forward re-render without a reload.
- [ ] With JavaScript disabled the page still works end to end.
- [ ] A failed request falls back to a full navigation (no stale fragments).
- [ ] The pager's links point at the page, not at the endpoint.
- [ ] Focus and the caret survive a swap.

## 7. The reference implementations, file by file

| File | What it holds |
|---|---|
| `app/Http/Controllers/AdminController.php` | `auditIndex` (the page), `auditBrowser` (the fragments), `auditPeople` (the roster only), and the shared helpers `auditScope()`, `auditLogs()`, `auditSelection()`, `auditQuery()`, `auditActiveFilters()`, `auditSelectionParams()`, `auditScopeCount()` |
| `resources/views/admin/audit/index.blade.php` | The page and the client controller (`navigate()`, the click delegate, the type-ahead) |
| `resources/views/admin/audit/partials/*.blade.php` | The four regions |
| `routes/web.php` | `admin.audit.browser`, `admin.audit.people` — in the same middleware group as the page |

The audit trail went from a full reload on every action to zero reloads, and it
kept its URL contract: `?department_id=`, `?actor_id=`, `?residents=1`,
`?system=1`, `?people_q=`, `?people_group=&people_page=`, `?page=`,
`?search=`, `?action=` — all still shareable, still bookmarkable, still
refresh-safe.

The public transparency feed (`/transparency/complaints`) took the same
treatment, and it adds one note: a public page must not leak. The fragments are
rendered by the same controller method as the page, so they carry the same
public payload — the test asserts that no resident identity reaches a fragment.

The complaints queue (`/admin/complaints`) took the same treatment, and it
adds two notes:

| File | What it holds |
|---|---|
| `app/Http/Controllers/AdminController.php` | `complaintsIndex` (the page), `complaintsBrowser` (the fragments), and the shared helpers `complaintsQueue()`, `complaintFilterLabels()`, `complaintQueueStats()` |
| `resources/views/admin/complaints/partials/*.blade.php` | `summary` (the filter chips), `list` (the rows), `pagination` (the pager) |
| `resources/views/admin/complaints/index.blade.php` | The client controller, and the bulk bar rewritten to listen **by delegation** |

The bulk bar is the part worth copying: it used to bind to every checkbox when
the page loaded. When the rows are swapped, those checkboxes are new elements,
so the bar would have gone deaf. Delegation (`form.addEventListener('change',
…)` plus a live `querySelectorAll` inside the handler) survives the swap, and a
`queue:refreshed` event lets the bar reset itself when the selection is
replaced.

## 8. When *not* to use it

- A page whose content is a single, short list — a reload costs nothing.
- Flows that must survive a hard refresh mid-way (payment, a wizard): keep
  those as real navigations.
- Anything that changes the *identity* of the page (a different route, a
  different layout) — that is a navigation, not a swap.
