# Localization / Language Switcher — Implementation Log

**Date:** 2026-11-08  
**Scope:** English + Filipino (`en`, `fil`) localization for the resident-facing portal.  
**Status:** Implemented and tested. The language switcher works on public, citizen, staff, and admin surfaces. Staff/admin sidebar navigation is translated; the switcher itself remains hidden on staff/admin shells.

---

## 1. Goal and decisions

- Residents can switch the portal between **English** and **Filipino**.
- **Filipino** uses the locale code `fil` (not `tl`). The UI label is "Filipino" (the official language name). If the team prefers the label "Tagalog", change only the `fil` label in `config/localization.php`; the locale code and translation files stay the same.
- **No Bikol (`bcl`) yet.** It can be added later without renaming existing keys.
- **Department names are not translated.** They are proper names and stay as-is.
- **Process wording is translated** so residents can understand how the complaint process works.
- **Staff/admin sidebar navigation** is translated (sidebars use `nav.*` keys). The language switcher itself remains hidden on staff/admin shells — they inherit the resident's locale choice via session/cookie/user preference but do not show the switcher.
- User-generated complaint text, updates, and attachments are **not** machine-translated.
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
- **Public transparency pages** are fully translated (en + fil):
  - Transparency register: header, map, filters, table, mobile cards, privacy note, empty/no-results states
  - Transparency detail: progress, report, evidence, updates, details, privacy, footer
  - Transparency inline script: map status messages and filter results via `TRANSPARENCY_STRINGS` JS object
  - Status enum labels via `statuses.*` keys
- Filipino transparency translations are simplified and Daet-localized; technical terms like "Loading…" kept in English.

---

## 3. What has been translated (second slice)

### Public transparency pages
- **Transparency register** (`resources/views/livewire/transparency-register.blade.php`): header, map card, filter toolbar, results count, visibility note, table headers, empty states, private note, row links.
- **Transparency detail** (`resources/views/pages/transparency/show.blade.php`): back link, browse link, progress section, report/evidence/updates/details sections, labels, privacy note, return link, footer. Status labels come from `ComplaintStatus::label()` which now uses `__('statuses.*')`.
- **Transparency script** (`resources/views/pages/transparency/partials/register-script.blade.php`): map status messages and filter results injected as `TRANSPARENCY_STRINGS` JavaScript object.
- **Transparency index** title (`resources/views/pages/transparency/index.blade.php`): uses `__('transparency.index.title')`.
- **TransparencyController**: public description fallback and address redaction now use `__('transparency.show.*')`.

### Auth pages
- **Login, register, forgot password, reset password, verify email**: headings, labels, placeholders, helper text already using `auth_ui.*` keys; remaining hardcoded placeholders (`your@email.com`, `Juan`, `dela Cruz`, `09XX XXX XXXX`) now use `auth_ui.common.email_placeholder`, `auth_ui.register.first_name_placeholder`, `auth_ui.register.last_name_placeholder`, `auth_ui.register.phone_placeholder`.
- **Auth validation messages** (`AuthController`): first/last name regex, email unique, phone regex, barangay invalid, password confirmed, terms accepted now use `__()` from `auth_ui.*` and `auth.*`.
- **Auth flash messages** (`AuthController`, `EmailVerificationController`): account created, login failed, deactivated, temporary password expired, reset link missing, reset send failed, reset throttled, reset status, email verified, email verified and updated, start email change before resend, wait for resend, several codes requested, could not send verification email, new verification code sent now use `__('auth.*')`.
- **Password change** (`PasswordChangeController`): password confirmation message uses `__('auth_ui.confirmed')`.

### Status enums
- `ComplaintStatus::label()` and related enums (`ComplaintReviewStatus`, `ComplaintPriority`, `VerificationMethod`, `InformationRequestStatus`) now return `__('statuses.*')` from `lang/en/statuses.php` and `lang/fil/statuses.php`.

---

## 4. Architecture

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

## 5. Files added

| File | Purpose |
| --- | --- |
| `config/localization.php` | Supported locales (`en`, `fil`), default locale, cookie name/lifetime. |
| `app/Http/Middleware/SetLocale.php` | Resolves and applies the active locale on every web request. |
| `app/Http/Controllers/LocaleController.php` | Validates and stores the resident's language choice. |
| `database/migrations/2026_11_08_000000_add_locale_to_users_table.php` | Adds `users.locale` (default `en`). |
| `resources/views/layouts/partials/language-switcher.blade.php` | Shared switcher form + scoped `@once` CSS. |
| `lang/en/auth.php` | English auth flash/validation messages. |
| `lang/fil/auth.php` | Filipino auth flash/validation messages. |
| `lang/en/nav.php` | English nav/drawer labels. |
| `lang/fil/nav.php` | Filipino nav/drawer labels. |
| `lang/en/footer.php` | English footer labels. |
| `lang/fil/footer.php` | Filipino footer labels. |
| `lang/en/home.php` | English landing-page copy. |
| `lang/fil/home.php` | Filipino landing-page copy. |
| `lang/en/statuses.php` | English status/enum labels. |
| `lang/fil/statuses.php` | Filipino status/enum labels. |
| `lang/en/transparency.php` | English transparency copy. |
| `lang/fil/transparency.php` | Filipino transparency copy. |
| `tests/Feature/LocalizationTest.php` | Feature coverage for switching, persistence, fallback, and visibility. |
| `docs/localization.md` | This implementation log. |
| `docs/transparency-redesign.md` | Transparency page redesign: theme, UI, components, map rules, filter bar, layout, accessibility. |
| `resources/views/components/button.blade.php` | Shared button component (primary/secondary/ghost/danger, size variants). |
| `resources/views/components/card.blade.php` | Shared card component (white, border, radius, padding variants). |
| `resources/views/components/pill.blade.php` | Shared status pill component (color variants per status). |
| `resources/views/components/chip.blade.php` | Shared filter chip component (selected/unselected states). |
| `resources/views/components/input.blade.php` | Shared input component (label, placeholder, required, error, helper). |
| `resources/views/components/report-card.blade.php` | Shared mobile report card component (ticket, status pill, title, meta). |

## 6. Files modified

| File | Change |
| --- | --- |
| `bootstrap/app.php` | Appended `SetLocale` to the `web` middleware group. |
| `routes/web.php` | Added `POST /locale` named `locale.update`. |
| `app/Models/User.php` | Added `locale` to `$fillable`. |
| `app/Enums/ComplaintStatus.php` | `label()` uses `__('statuses.complaint_status.*')`. |
| `app/Http/Controllers/AuthController.php` | Validation messages and flash messages use `__()`. |
| `app/Http/Controllers/EmailVerificationController.php` | Flash/error messages use `__('auth.*')`. |
| `app/Http/Controllers/PasswordChangeController.php` | Validation message uses `__('auth_ui.confirmed')`. |
| `app/Http/Controllers/TransparencyController.php` | Public description fallback and redaction use `__('transparency.show.*')`. |
| `resources/views/layouts/guest-navbar.blade.php` | Translated labels; added compact switcher + mobile switcher. |
| `resources/views/layouts/navbar.blade.php` | Translated labels; added citizen-only desktop/mobile switchers. |
| `resources/views/layouts/partials/mobile-drawer.blade.php` | Translated labels; added citizen-only switcher. |
| `resources/views/layouts/partials/site-footer.blade.php` | Translated labels; added footer switcher for guests/citizens. |
| `resources/views/layouts/app.blade.php` | Dynamic `<html lang>`. |
| `resources/views/layouts/guest.blade.php` | Dynamic `<html lang>`. |
| `resources/views/pages/home/guest.blade.php` | Translated the real landing page. |
| `resources/views/pages/home.blade.php` | Also translated, but unused by `HomeController`. |
| `resources/views/auth/login.blade.php` | Email placeholder translated. |
| `resources/views/auth/register.blade.php` | Name/email placeholders translated. |
| `resources/views/auth/forgot-password.blade.php` | Email placeholder translated. |
| `resources/views/livewire/transparency-register.blade.php` | Complete redesign: compact header (no buttons), map section, filter bar (chips + dropdowns + sort), desktop sticky table, mobile ReportCard list, map data attribute. |
| `resources/views/pages/transparency/index.blade.php` | Rewritten as thin Livewire shell wrapper — all visible markup lives in the component view. |
| `resources/views/pages/transparency/show.blade.php` | All visible copy translated via `__('transparency.show.*')`. |
| `resources/views/pages/transparency/partials/register-script.blade.php` | Complete rewrite: status chips, conditional clear-filters, sort select, client-side filtering with URL-ready state, no floating map badge. |
| `app/Services/TransparencyFeed.php` | `mapPoints()` returns `intensity: 0` and `low_volume: true` for barangays with fewer than 3 reports. |
| `resources/views/layouts/partials/mobile-drawer.blade.php` | "File a complaint" moved to top of citizen mobile drawer (primary gold button). |
| `resources/views/layouts/navbar.blade.php` | "File a complaint" added to top of citizen mobile menu (authenticated shell). |
| `resources/views/layouts/partials/site-footer.blade.php` | Language switcher removed from footer. |

> **Note:** `resources/views/pages/home.blade.php` is not rendered today. `HomeController@index` returns `pages.home.guest`. The translation work there is harmless but not visible.

---

## 7. Translation namespaces

| Namespace | Used by |
| --- | --- |
| `nav.*` | Citizen nav, admin sidebar, staff sidebar (operations, people, workspace, verification, account sections and all menu items). |
| `footer.*` | Site footer. |
| `home.*` | Shared landing-page keys. |
| `home.guest.*` | The real landing page (`pages/home/guest.blade.php`). |
| `auth_ui.*` | Auth forms: login, register, forgot password, reset password, verify email, password change. |
| `auth.*` | Auth flash/error messages (AuthController, EmailVerificationController). |
| `statuses.*` | Complaint status, review status, priority, verification method, information request labels. |
| `transparency.*` | Public transparency register, detail page, and script strings. |

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

## 8. Tests

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

---

## 9. Operational notes

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

## 10. Known limitations / next steps

### Not translated yet

- Citizen workspace: dashboard, complaint index, complaint wizard, complaint detail, tracking page (some may already be partially translated).
- Notifications and email templates.
- Legal pages (terms, privacy policy). If translated, add an "English version prevails" clause.
- Staff/admin surfaces (sidebar navigation translated; switcher remains hidden on staff/admin shells).

### Recommended next slices

1. **Citizen workflow** — dashboard, complaint wizard, complaint detail, tracking, statuses.
2. **Notifications/emails** — store notification `type` + `context` and render titles through translation keys so old notifications follow the active locale.
3. **Legal pages** — professional translation with an English-prevails clause.
4. **Staff/admin** — sidebar navigation already translated; if full staff/admin page translation is needed later, expand to dashboard, complaint views, etc. Switcher stays hidden on staff/admin shells.
5. **Profile preference** — expose `users.locale` in the profile form so residents can change language from account settings too.
6. **Optional Bikol** — add `bcl` once Filipino copy is stable.

### Deliberately not done

- No machine translation of user-submitted complaint titles/descriptions/updates.
- No department-name translation.
- No automatic locale detection beyond `Accept-Language`; explicit resident choice wins.

---

## 11. Quick verification checklist

- [ ] `php artisan migrate` completed.
- [ ] Guest sees `EN | FIL` in the header and full labels in the mobile menu/footer.
- [ ] Clicking `FIL` reloads the page in Filipino and sets the `locale` cookie.
- [ ] The landing page process/FAQ/CTA are in Filipino.
- [ ] Signed-in citizen's choice is saved in `users.locale`.
- [ ] Staff/admin do not see the switcher.
- [ ] `php artisan test --filter=LocalizationTest` passes.
- [ ] Auth pages (login, register, forgot password, reset password, verify email) show Filipino labels/placeholders when locale is `fil`.
- [ ] Transparency register and detail pages show Filipino copy when locale is `fil`.
- [ ] Status labels show Filipino when locale is `fil`.
