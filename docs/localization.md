# Localization / Language Switcher — Implementation Log

**Date:** 2026-11-08  
**Scope:** First slice of English + Filipino (`en`, `fil`) localization for the resident-facing portal.  
**Status:** Implemented and tested. The language switcher works on public and citizen surfaces; staff/admin surfaces remain English for now.

---

## 1. Goal and decisions

- Residents can switch the portal between **English** and **Filipino**.
- **Filipino** uses the locale code `fil` (not `tl`). The UI label is "Filipino" (the official language name). If the team prefers the label "Tagalog", change only the `fil` label in `config/localization.php`; the locale code and translation files stay the same.
- **No Bikol (`bcl`) yet.** It can be added later without renaming existing keys.
- **Department names are not translated.** They are proper names and stay as-is.
- **Process wording is translated** so residents can understand how the complaint process works.
- User-generated complaint text, updates, and attachments are **not** machine-translated.
- Staff/admin shells are not translated in this slice, and the switcher is hidden there.
- English remains the fallback for any missing translation.

---

## 2. What works

- A visible language switcher appears in:
  - the public/guest desktop navbar,
  - the public mobile menu,
  - the authenticated citizen desktop navbar,
  - the authenticated citizen mobile menu,
  - the citizen mobile drawer,
  - the site footer on public/citizen pages.
- Switching is a plain `POST /locale` form with CSRF protection. It works without JavaScript.
- The choice persists in:
  1. the session (current visit),
  2. a one-year cookie (guests / across logout),
  3. `users.locale` (signed-in residents).
- `SetLocale` middleware resolves the active locale before controllers/views run.
- `<html lang="...">` is now dynamic in both the guest and authenticated layouts.
- The public landing page (`pages.home.guest`) is translated: hero, stats labels, process steps, features, privacy section, FAQ, and CTA.
- Guest navbar, authenticated navbar, mobile drawer, and footer labels are translated.
- Unsupported locales are rejected by validation and by the middleware.
- The switcher is **not** shown to staff/admin.

---

## 3. Architecture

### Request flow

```
Browser POST /locale { locale: fil }
  -> LocaleController@update
     -> validate against config('localization.supported')
     -> session('locale') = fil
     -> queue cookie('locale', fil, 1 year)
     -> if signed in: users.locale = fil
     -> redirect back
  -> next request: SetLocale middleware
     -> session('locale')
     -> cookie('locale')
     -> auth()->user()->locale
     -> Accept-Language
     -> config('localization.default')
     -> App::setLocale(...) + Carbon::setLocale(...)
```

### Locale priority

The middleware uses:

1. session
2. cookie
3. signed-in user's saved `locale`
4. browser `Accept-Language`
5. application default (`en`)

Only values present in `config/localization.php` are accepted.

### Why a POST form

A language switch changes stored state. Using `POST` means link prefetchers, crawlers, and accidental navigation cannot silently change a resident's language. The form includes `@csrf`.

---

## 4. Files added

| File | Purpose |
| --- | --- |
| `config/localization.php` | Supported locales (`en`, `fil`), default locale, cookie name/lifetime. |
| `app/Http/Middleware/SetLocale.php` | Resolves and applies the active locale on every web request. |
| `app/Http/Controllers/LocaleController.php` | Validates and stores the resident's language choice. |
| `database/migrations/2026_11_08_000000_add_locale_to_users_table.php` | Adds `users.locale` (default `en`). |
| `resources/views/layouts/partials/language-switcher.blade.php` | Shared switcher form + scoped `@once` CSS. |
| `lang/en/nav.php` | English nav/drawer labels. |
| `lang/fil/nav.php` | Filipino nav/drawer labels. |
| `lang/en/footer.php` | English footer labels. |
| `lang/fil/footer.php` | Filipino footer labels. |
| `lang/en/home.php` | English landing-page copy. |
| `lang/fil/home.php` | Filipino landing-page copy. |
| `tests/Feature/LocalizationTest.php` | Feature coverage for switching, persistence, fallback, and visibility. |
| `docs/localization.md` | This implementation log. |

## 5. Files modified

| File | Change |
| --- | --- |
| `bootstrap/app.php` | Appended `SetLocale` to the `web` middleware group. |
| `routes/web.php` | Added `POST /locale` named `locale.update`. |
| `app/Models/User.php` | Added `locale` to `$fillable`. |
| `resources/views/layouts/guest-navbar.blade.php` | Translated labels; added compact switcher + mobile switcher. |
| `resources/views/layouts/navbar.blade.php` | Translated labels; added citizen-only desktop/mobile switchers. |
| `resources/views/layouts/partials/mobile-drawer.blade.php` | Translated labels; added citizen-only switcher. (This file was already untracked before this work.) |
| `resources/views/layouts/partials/site-footer.blade.php` | Translated labels; added footer switcher for guests/citizens. |
| `resources/views/layouts/app.blade.php` | Dynamic `<html lang>`. |
| `resources/views/layouts/guest.blade.php` | Dynamic `<html lang>`. |
| `resources/views/pages/home/guest.blade.php` | Translated the real landing page; fixed empty complaint/track hrefs in the `@auth` branch. |
| `resources/views/pages/home.blade.php` | Also translated, but this view is currently unused by `HomeController`. |

> **Note:** `resources/views/pages/home.blade.php` is not rendered today. `HomeController@index` returns `pages.home.guest`. The translation work there is harmless but not visible.

---

## 6. Translation namespaces

| Namespace | Used by |
| --- | --- |
| `nav.*` | Guest navbar, authenticated navbar, mobile menu, mobile drawer. |
| `footer.*` | Site footer. |
| `home.*` | Shared landing-page keys (some used by the unused `pages/home.blade.php`). |
| `home.guest.*` | The real landing page (`pages/home/guest.blade.php`). |

### Adding a new string

1. Add the key to `lang/en/<file>.php`.
2. Add the same key to `lang/fil/<file>.php`.
3. Use `{{ __('file.key') }}` in Blade or `__('file.key')` in PHP.
4. Keep proper nouns (department names, "Daet", "Camarines Norte", "RA 6713") untranslated unless there is an official translation.

### Adding a new locale later

1. Add the code and label to `config/localization.php`:

   ```php
   'supported' => [
       'en' => 'English',
       'fil' => 'Filipino',
       'bcl' => 'Bikol',
   ],
   ```

2. Create `lang/bcl/` with the same file names/keys as `lang/en/`.
3. Add the locale to the `LocalizationTest` expectations if needed.
4. Run `php artisan optimize:clear` if config/lang caches are enabled.

---

## 7. Tests

### Localization tests

```bash
php artisan test --filter=LocalizationTest
```

Result: **7 passed, 35 assertions**.

Covered:

- guest switches to Filipino and the choice survives the next request,
- unsupported locale is rejected,
- signed-in user's locale is saved to `users.locale`,
- saved user locale is used without a session choice,
- invalid session locale falls back to English,
- switcher is visible on public/auth pages,
- switcher is hidden from staff/admin shells.

### Related UI/navigation tests

```bash
php artisan test --filter='LocalizationTest|ShellControllerTest|TransparencyPageTest|LegalPagesTest|RoleDashboardTest|AdminMobileNavigationTest|PageOverflowTest|NotificationDropdownWidthTest'
```

Result: **38 passed, 1 failed**.

The failure is `AdminMobileNavigationTest > account menu orders dashboard then complaints account and sign out`. It expects "My Complaints" in the admin account menu. The current working tree wraps that item in `@if(auth()->user()->role !== 'admin')`, so the test and the uncommitted admin-menu change disagree. This is **not caused by localization**; the condition was already present in the dirty working tree before this work.

### Full suite

```bash
php artisan test
```

Result at the time of this log: **374 passed, 7 failed**.

The failures observed are outside the localization slice:

- `AdminQueueTest`: admin queue markup/seed-count expectations.
- `InlineScriptRuntimeTest`: jsdom environment is missing `window.matchMedia`.
- `StaffWorkspaceTest`: "My activity" appears twice because the pre-existing untracked mobile drawer and the staff sidebar both render it.

The working tree already contained unrelated uncommitted UI/notification work before localization started, so re-run the full suite after that work is settled.

---

## 8. Operational notes

- Run the migration in each environment:

  ```bash
  php artisan migrate
  ```

- If config or translations are cached:

  ```bash
  php artisan optimize:clear
  ```

- The language switcher CSS is self-contained in the partial and guarded by `@once`, so including the partial in both the navbar and footer does not duplicate the `<style>` block.

- The cookie is named `locale` by default and lives for one year. Change it in `config/localization.php`.

- The `users.locale` column is a string(8) defaulting to `en`.

- `LocaleController` uses `forceFill(['locale' => ...])->save()` so the controller works even if `locale` is not in `$fillable`; it is in `$fillable` anyway for future profile forms.

---

## 9. Known limitations / next steps

### Not translated yet

- Auth forms: login, register, forgot password, reset password, verify email.
- Citizen workspace: dashboard, complaint index, complaint wizard, complaint detail, tracking page.
- Complaint statuses and enum `label()` methods.
- Validation messages and flash messages.
- Notifications and email templates.
- Legal pages (terms, privacy policy). If translated, add an "English version prevails" clause.
- Staff/admin surfaces.
- Public transparency register.

### Recommended next slices

1. **Auth pages** — translate headings, labels, placeholders, helper text, and error summaries.
2. **Citizen workflow** — dashboard, complaint wizard, complaint detail, tracking, statuses.
3. **Notifications/emails** — store notification `type` + `context` and render titles through translation keys so old notifications follow the active locale.
4. **Legal pages** — professional translation with an English-prevails clause.
5. **Staff/admin** — only if operators need Filipino; otherwise keep English and keep the switcher hidden.
6. **Profile preference** — expose `users.locale` in the profile form so residents can change language from account settings too.
7. **Optional Bikol** — add `bcl` once Filipino copy is stable.

### Deliberately not done

- No machine translation of user-submitted complaint titles/descriptions/updates.
- No department-name translation.
- No automatic locale detection beyond `Accept-Language`; explicit resident choice wins.

---

## 10. Quick verification checklist

- [ ] `php artisan migrate` completed.
- [ ] Guest sees `EN | FIL` in the header and full labels in the mobile menu/footer.
- [ ] Clicking `FIL` reloads the page in Filipino and sets the `locale` cookie.
- [ ] The landing page process/FAQ/CTA are in Filipino.
- [ ] Signed-in citizen's choice is saved in `users.locale`.
- [ ] Staff/admin do not see the switcher.
- [ ] `php artisan test --filter=LocalizationTest` passes.
