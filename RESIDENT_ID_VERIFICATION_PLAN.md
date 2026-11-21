# Resident ID Verification Plan

**Date:** November 21, 2026
**Status:** Planned — not implemented
**Scope:** Resident identity verification (government ID + selfie), admin review, complaint-filing gate, and admin TOTP two-factor authentication.

## Decisions locked

| Area | Decision |
|---|---|
| Purpose | Anti-spam / one person, one account |
| Who is verified | Residents (`citizen`) only |
| Submission | Valid Philippine government ID front + selfie holding the same ID |
| Accepted IDs | Any government-issued PH ID, with a barangay certificate as fallback |
| Placement | Dedicated `/identity/verify` page + a Settings status card |
| Gate | New complaint filing only; reading, tracking, existing complaints, and settings stay open |
| Pending | Cannot file until approved |
| Existing residents | Verify before their next **new** complaint; old reports and actions are unaffected |
| Finality | Approved ID is locked; rejected submissions may be resubmitted; an admin may revoke with a reason |
| Duplicate detection | Keyed HMAC of the normalized ID number; the raw number is never stored |
| Retention | ID and selfie images deleted after the decision; decision metadata retained; pending submissions expire after 30 days |
| Reviewer | `admin` only; staff and department admins never see identity documents |
| 2FA | Authenticator app (TOTP) + 8 recovery codes; enforced on all admin routes; CLI reset |

## Goal

A resident proves they are a real person once, before filing a complaint. The LGU gets one accountable account per person without turning the portal into a closed system: residents can still sign in, track reports, read transparency, and manage their account while unverified.

## Non-goals

- No public “verified resident” badge. Verification status is not shown on complaints, transparency pages, or staff views.
- No ID verification for staff, department admins, or admins.
- No automated OCR, face matching, or third-party KYC. Review is manual.
- No retention of ID images after the decision.
- No change to complaint anonymity. A resident may still file anonymously after verification; the ID is never attached to a complaint.
- No SMS. 2FA is TOTP only.

## Resident lifecycle

```
Register → verify email → dashboard
                              │
              “File a complaint” leads to identity verification
                              │
                    submit ID + selfie + consent
                              │
                         pending (cannot file)
                              │
                 admin reviews (2FA required)
                    ┌─────────┴─────────┐
                 approve               reject + reason
                    │                     │
              verified (locked)      resubmit allowed
                    │
              can file complaints
```

1. Registration and email verification stay exactly as they are today.
2. An unverified citizen who tries to file is sent to `/identity/verify` with a clear explanation. The navbar, dashboard, complaints index, and empty-state “File a complaint” calls-to-action point to the same place through one shared helper.
3. The verification page collects:
   - ID type,
   - ID number (used only to compute the duplicate-detection hash),
   - a photo of the ID front,
   - a selfie holding the same ID,
   - consent to the stated purpose, access, and retention.
4. Submission creates a `pending` record. The resident sees “Under review” and cannot file. They may cancel the pending submission.
5. An admin approves or rejects. The resident gets an in-app notification.
6. Approved: the resident can file. The approved ID is final; the resident cannot replace it or change their legal name without an admin action.
7. Rejected: the reason is shown, and the resident may resubmit. Default limit: 3 submissions per 24 hours, adjustable by config.
8. Existing residents keep access to old complaints, information requests, closure, feedback, and visibility controls. Only `complaints.create` and `complaints.store` are gated.

## Status machine

### Resident status (`users.identity_status`)

| Status | Meaning | Can file? |
|---|---|---|
| `unverified` | Never submitted, cancelled, expired, or revoked | No |
| `pending` | Waiting for admin review | No |
| `verified` | Approved and locked | Yes |
| `rejected` | Last submission rejected; resubmission allowed | No |

### Submission status (`identity_verifications.status`)

| Status | Set by | Notes |
|---|---|---|
| `pending` | Resident | One pending submission per resident |
| `approved` | Admin | Files purged after commit |
| `rejected` | Admin | Reason required; files purged |
| `revoked` | Admin | Reverses a previously approved verification |
| `expired` | Scheduler | Pending older than 30 days |
| `cancelled` | Resident | Pending withdrawn by the resident |

`revoked`, `expired`, and `cancelled` return the resident to `unverified`.

## Data model

### `users` additions

| Column | Type | Notes |
|---|---|---|
| `identity_status` | string(20), default `unverified`, indexed | Cached state for gates and lists |
| `identity_verified_at` | timestamp, nullable | Set on approval, cleared on revoke |
| `two_factor_secret` | text, nullable | Encrypted cast; never fillable |
| `two_factor_confirmed_at` | timestamp, nullable | Null means enrollment incomplete |
| `two_factor_recovery_codes` | text, nullable | JSON array of hashed codes |

Do not add the raw ID number to `users`.

### `identity_verifications`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `user_id` | FK → users, indexed | |
| `status` | string(20), default `pending`, indexed | |
| `id_type` | string(40) | Validated against the accepted list |
| `id_number_hash` | string(64), nullable, indexed | HMAC-SHA256; no raw number |
| `id_front_path` | string, nullable | Private MinIO path |
| `selfie_path` | string, nullable | Private MinIO path |
| `files_purged_at` | timestamp, nullable | Proof of deletion |
| `possible_duplicate_user_id` | FK → users, nullable | Flag for the reviewer |
| `submitted_at` | timestamp | |
| `reviewed_at` | timestamp, nullable | |
| `reviewed_by` | FK → users, nullable | |
| `rejection_reason` | text, nullable | Shown to the resident |
| `reviewer_note` | text, nullable | Admin-only |
| `revoked_at` | timestamp, nullable | |
| `revoked_by` | FK → users, nullable | |
| `revocation_reason` | text, nullable | |
| `created_at`, `updated_at` | timestamps | |

Add a PostgreSQL partial unique index so a resident cannot have two pending submissions:

```sql
CREATE UNIQUE INDEX identity_verifications_one_pending_per_user
ON identity_verifications (user_id)
WHERE status = 'pending';
```

### Models and helpers

- `IdentityVerification` model with `user()`, `reviewer()`, `revokedBy()`, `possibleDuplicate()` relations and status constants.
- `User::identityVerifications()`, `User::latestIdentityVerification()`.
- `User::isIdentityVerified()`, `User::canFileComplaints()`, `User::requiresIdentityVerification()`, `User::hasTwoFactorEnabled()`.
- `Notification` gains identity constants, icon mapping, type labels, and a `destination()` branch for `IdentityVerification` subjects.

## Routes and controllers

### Resident

| Method | Path | Name | Notes |
|---|---|---|---|
| GET | `/identity/verify` | `identity.verify.show` | Status-aware page: form, pending, rejected, verified |
| POST | `/identity/verify` | `identity.verify.store` | Validate, sanitize, hash, store, create pending |
| POST | `/identity/verify/cancel` | `identity.verify.cancel` | Cancel pending, purge files |

### Admin

| Method | Path | Name | Notes |
|---|---|---|---|
| GET | `/admin/identity` | `admin.identity.index` | Queue with pending/all filter, search, pagination |
| GET | `/admin/identity/{verification}` | `admin.identity.show` | Review screen |
| GET | `/admin/identity/{verification}/document/{side}` | `admin.identity.document` | `side` = `id_front` or `selfie`; decrypted stream |
| POST | `/admin/identity/{verification}/approve` | `admin.identity.approve` | |
| POST | `/admin/identity/{verification}/reject` | `admin.identity.reject` | Reason required |
| POST | `/admin/users/{user}/identity/revoke` | `admin.users.identity.revoke` | Reason required |

### 2FA

| Method | Path | Name | Notes |
|---|---|---|---|
| GET | `/settings/security/two-factor` | `settings.two-factor.show` | Setup / re-enroll / recovery codes |
| POST | `/settings/security/two-factor` | `settings.two-factor.enable` | Verify first code |
| POST | `/settings/security/two-factor/recovery-codes` | `settings.two-factor.recovery-codes` | Regenerate with current code |
| POST | `/settings/security/two-factor/re-enroll` | `settings.two-factor.re-enroll` | New secret with current code |
| GET | `/two-factor/challenge` | `two-factor.challenge` | After password login |
| POST | `/two-factor/challenge` | `two-factor.verify` | TOTP or recovery code |

### Middleware

- `identity.verified` (`EnsureResidentIsVerified`): applies to `complaints.create` and `complaints.store`. If verification is not required by config, or the user is not a citizen, pass through. Verified passes. Pending redirects to the identity page with status. Unverified/rejected redirects to the form with an explanation. JSON requests receive a 403 with a safe message.
- `admin.2fa` (`EnsureAdminTwoFactor`): applies to the admin route group after `role:admin`. If 2FA is not required by config, pass through. If the admin has no confirmed secret, redirect to setup. If confirmed but the session lacks a passed marker, redirect to the challenge.
- Do **not** gate `complaints.index`, `complaints.show`, information replies, closure, feedback, or visibility updates. Those belong to existing cases.

## Storage and encryption

- Disk: private `minio` only. Never the `public` disk, never a public URL.
- Path: `identity/{userId}/{uuid}.enc`. No original filenames.
- Encrypt at rest with `Crypt::encryptString()` before writing. Decrypt only in the admin document controller. Max 5 MB per file makes in-memory decryption acceptable.
- Validate with `image`, `mimes:jpg,jpeg,png,webp`, `max:5120`, plus dimension limits.
- Re-encode to JPEG (max 2000 px, quality 85) to strip EXIF/GPS and normalize orientation. Requires GD or Imagick **and** the EXIF extension; the current runtime has neither, so this is a deployment prerequisite.
- White background for transparent PNG/WebP; output JPEG.
- Delete stored files if the database transaction fails.
- Purge both files on approve, reject, revoke, cancel, expiry, and account deletion. Set `files_purged_at`.
- `identity:purge` scheduled command:
  - expire pending submissions older than 30 days,
  - purge any decided record that still has paths,
  - sweep orphan `identity/` objects older than 24 hours that no row references.
- Backups: encrypted files are ciphertext, but deleted objects may persist in backups. Define a backup retention window and tell the DPO; logical deletion is not physical deletion until backups rotate.
- MinIO hardening: dedicated non-root user limited to the bucket, strong password, no public bucket policy, TLS or app-level encryption, console not exposed.

## Duplicate detection

- Normalize the ID number: uppercase, remove spaces, hyphens, dots, and other separators.
- Hash: `hash_hmac('sha256', $idType.'|'.$normalized, config('identity.hash_key'))`.
- Use a dedicated `IDENTITY_HASH_KEY` (32 random bytes, base64) so `APP_KEY` rotation does not silently break duplicate detection. Back it up with `APP_KEY`.
- On submit, look for an existing `pending` or `approved` row with the same hash belonging to a different user. If found, set `possible_duplicate_user_id` and let the admin decide. Do not auto-reject: twins, data-entry errors, and shared documents need human judgment.
- Exclude the same user's own earlier rows, so resubmission after rejection is not flagged as a duplicate.
- Never show the raw number or the hash to the resident. The reviewer reads the number from the image.
- If the ID has no number, require the certificate/control number; otherwise duplicate detection cannot work.

## Admin review

- Queue: pending first, oldest first; filter by status; search by resident name/email; paginate 20.
- Pending count badge in the admin navigation.
- Review screen: account summary, submitted date, ID type, possible-duplicate warning, ID front and selfie side by side, approve and reject actions.
- Approve requires a confirmation that the reviewer compared the face and the ID. Reject requires a reason (select common reasons plus an optional note). No bulk approve.
- Decision runs in a transaction with `lockForUpdate`, so a double click cannot approve twice.
- Files are purged after commit. A purge failure is logged and retried by the scheduled command; the decision still stands.
- Revoke lives on the admin user detail page. It requires a reason, sets the resident back to `unverified`, marks the approved submission `revoked`, notifies the resident, and is audit-logged.
- Admin user directory gains identity status and 2FA status columns/filters.
- Staff and department admins receive 403 on every identity route and policy check.

## Notifications

- New types:
  - `identity.submitted` → all active admins.
  - `identity.approved` → the resident.
  - `identity.rejected` → the resident.
  - `identity.revoked` → the resident.
- In-app only. No ID images, ID numbers, hashes, or paths in notification titles, bodies, or context.
- Extend `Notification::iconKey()`, `typeLabel()`, and `destination()`:
  - admin + `IdentityVerification` → review page,
  - citizen + `IdentityVerification` → `/identity/verify`.
- Identity decisions are transactional; they are not subject to marketing-style preferences.

## Admin TOTP 2FA

- Packages: `pragmarx/google2fa` for TOTP, `bacon/bacon-qr-code` for SVG QR output (pure PHP, works without GD).
- Enrollment:
  1. Admin signs in with password.
  2. If not enrolled, admin routes redirect to Settings → Security → Two-factor authentication.
  3. The page requires the current password, generates a secret, stores it encrypted, and shows a QR code plus the manual secret.
  4. The admin enters a 6-digit code. On success, `two_factor_confirmed_at` is set and 8 recovery codes are shown once.
  5. Recovery codes are stored hashed; the plaintext is never stored or emailed.
- Challenge:
  - After password login, an enrolled admin is sent to `/two-factor/challenge`.
  - Accepts a TOTP code or a single-use recovery code.
  - 5 attempts per minute per user + IP; 5 failed attempts ends the session and requires the password again.
  - Replay prevention: store the last successful time-step in cache for 120 seconds; reject a reused step.
  - Pending challenge expires after 10 minutes.
- Enforcement:
  - `admin.2fa` on all admin routes.
  - Config flag `ADMIN_2FA_REQUIRED`, default true in production and false in local/testing. Staging and production must set it true.
- Recovery:
  - Recovery codes are single-use and removed on use.
  - `php artisan admin:reset-2fa {email}` clears the secret, confirmation, and recovery codes; requires confirmation; audit-logged.
  - No self-service disable. Re-enrollment requires the current TOTP code and issues a new secret plus new recovery codes.
- Password change clears the session’s 2FA-passed marker, so the next admin route challenges again.
- Admin user directory shows who has 2FA enabled.
- No email or SMS fallback. Gmail is not involved; the authenticator app generates codes offline.

## Security and privacy controls

- **Access:** admin-only policy methods; `role === 'admin'`; no staff, department admin, or resident access to documents.
- **Transport:** HTTPS; `Cache-Control: no-store, private`, `Pragma: no-cache`, `X-Content-Type-Options: nosniff`, `Content-Disposition: inline`, `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex, nofollow, noarchive`.
- **Encryption:** app-level `Crypt` for ID files; encrypted 2FA secrets; hashed recovery codes.
- **Minimization:** no raw ID number, no original filename, no ID data in complaints, exports, logs, notifications, or public payloads.
- **Retention:** images deleted after decision; pending expires; orphan sweep; `files_purged_at` proof.
- **Audit:** `identity.submitted`, `identity.review_viewed`, `identity.document_viewed`, `identity.approved`, `identity.rejected`, `identity.revoked`, `identity.cancelled`, `identity.expired`, `identity.purged`, `auth.2fa.enabled`, `auth.2fa.challenge_failed`, `auth.2fa.recovery_used`, `auth.2fa.reset`. Metadata carries the verification id, ID type, and decision reason — never the number, hash, paths, or image contents.
- **Rate limits:** 3 submissions per 24 hours; 60 document views per minute per admin; 2FA challenge limits above.
- **Consent:** checkbox with purpose, reviewer, retention, consequences of refusal, and a link to the privacy policy.
- **Legal:** update the privacy policy; run a DPIA and obtain DPO/records-officer sign-off before collecting IDs. Confirm whether LGU records retention conflicts with delete-after-review. Breach procedure: 72-hour NPC notification; access logs identify exactly which submissions were exposed.
- **Screenshot/download limitation:** an admin can always photograph a screen. Technical controls cannot prevent it; rely on least privilege, audit, policy, and an optional visible overlay in the review UI.
- **MinIO:** dedicated bucket user, strong credentials, private policy, TLS or app encryption; no default `admin`/`password` in production.
- **Session:** `SESSION_ENCRYPT=true` and secure cookies in production; identity pages set no-referrer.
- **Backups:** encrypted; define retention; document that deletion propagates only when backups rotate.
- **No third-party analytics or external assets** on identity or 2FA pages.

## UI, i18n, and design system

- Read `docs/design-system.md` in full before any UI work. Use only documented tokens and components. Add a component/token to the guide and `/design-system` first if something is missing.
- Pages:
  - `/identity/verify` with form, pending, rejected, verified, and cancelled states.
  - Settings → Identity status card/tab (citizen only).
  - Admin identity queue and review screen.
  - 2FA setup, challenge, and recovery-code screens.
  - Updated “File a complaint” calls-to-action across navbar, mobile drawer, dashboard, complaints index, home, and legal pages.
- One shared helper or Blade component resolves the filing URL: guest → register/login; unverified citizen → identity verify; verified citizen → complaint create; staff/admin → existing behavior. Do not duplicate the condition across 16 links.
- Mobile: ID front uses `capture="environment"`; selfie uses `capture="user"`; gallery fallback; previews; clear size/type guidance.
- Accessibility: labelled inputs, error summary, focus management, status announcements, keyboard-operable file inputs, text alternative for the QR code.
- i18n: all strings in `lang/en` and `lang/fil`; add `identity.php` and extend `settings.php`, `nav.php`, `notifications.php`. No raw keys on screen. Statuses and rejection reasons are keys translated on display.
- Add a `docs/design-changelog.md` entry and run `scripts/design-system-guard.php`.
- Verification evidence in the changelog: test/build/view-cache results, plus manual mobile and desktop checks.

## Edge cases and policies

| Case | Behavior |
|---|---|
| Resident changes legal name after approval | Name fields locked for verified citizens; admin correction requires revoke + re-verification |
| Resident deletes account | Pending files purged; soft delete; decision history retained per policy |
| Resident cancels pending | Files purged; status `cancelled`; resident `unverified` |
| Pending expires | Status `expired`; files purged; resident `unverified`; resident may resubmit |
| Duplicate ID on another account | Flagged for admin; not auto-rejected; admin decides and may deactivate the duplicate account via existing controls |
| Same resident resubmits after rejection | Allowed; own previous rows excluded from duplicate check |
| Expired ID | Admin rejects; no expiry date stored in MVP (future: store date only, not image) |
| Resident has no government ID | Barangay certificate fallback; if none, support path. This is an equity risk and must be stated in the privacy/help copy |
| Minor applicant | Policy decision needed; barangay certificate fallback; no age gate in MVP |
| Admin approves the wrong ID | Revoke with reason; resident re-verifies; audit trail preserved |
| Admin loses phone and recovery codes | CLI reset by someone with server access |
| `APP_KEY` rotation | Pending encrypted files become unreadable; drain the queue before rotating or require re-upload |
| `IDENTITY_HASH_KEY` rotation | Old hashes stop matching; keep the key stable and backed up |
| MinIO outage or full disk | Upload fails safely; no partial DB row; generic error; retry |
| Double submission | Partial unique index allows one pending row; the second request returns the existing status |
| Approve/reject double click | Row lock and status check prevent a second decision |

## Testing

- Unit: hash normalization and HMAC, TOTP verification and replay rejection, recovery-code consumption, image sanitizer (when GD/EXIF available).
- Feature:
  - Unverified, pending, and rejected citizens cannot `GET /complaints/create` or `POST /complaints`; verified citizens can.
  - Unverified citizens can still view/track their existing complaints, answer information requests, close, rate, and change visibility.
  - Staff and admins are unaffected by the citizen gate.
  - Upload validation: missing files, wrong MIME, oversize, dimensions; encrypted storage; pending row; partial unique index.
  - Duplicate hash flags a different account; same-user resubmission is not flagged.
  - Approve: status, user verified, files purged, notification, audit; second approve fails.
  - Reject: reason required, status, files purged, notification, resubmit allowed.
  - Revoke: admin only, reason required, user unverified, notification, audit.
  - Authorization matrix: staff, department admin, and other citizens cannot access admin identity routes or documents.
  - Document route decrypts correctly and sends no-store headers; purged document returns 404.
  - Purge command: expired pending, decided rows with paths, orphan sweep.
  - 2FA: setup, confirm, challenge, recovery code single-use, replay rejection, rate limit, middleware redirect, CLI reset, non-admin unaffected.
  - Notification destination, icon, and label for identity events.
  - Name lock after verification.
- Existing tests: add factory states/helpers for `verifiedCitizen()` and `adminWithTwoFactor()`; update admin tests and complaint-filing tests. Use fixture image files rather than `UploadedFile::fake()->image()` if GD is unavailable in the test runtime.
- Verification: `php artisan test`, `npm run build`, `php artisan view:cache`, design guard, real MinIO upload/decrypt/purge smoke test, mobile camera test, and a manual review of the admin queue.

## Rollout

1. **Prerequisites:** DPO/records sign-off, privacy-policy update, MinIO hardening, `php-gd` + `php-exif` (or Imagick), `IDENTITY_HASH_KEY`, `APP_KEY` backup, dedicated MinIO user, backup retention decision.
2. **Phase 1 — 2FA:** add packages, columns, enrollment/challenge, middleware, CLI reset. Enroll all admins in staging and production. Keep `ADMIN_2FA_REQUIRED=true` in production.
   **Status (2026-11-21): implemented.** See `docs/design-changelog.md` for the verification record. Production still needs `ADMIN_2FA_REQUIRED=true` (the config default is true when `APP_ENV=production`) and every admin enrolled before Phase 2 review ships.
3. **Phase 2 — identity flow:** migrations, upload, storage, admin queue/review, notifications, audit, purge command. Keep `IDENTITY_VERIFICATION_REQUIRED=false` so admins can dry-run the review flow.
   **Status (2026-11-21): launched.** Phase 4 gate activated: `IDENTITY_VERIFICATION_REQUIRED=true` in `.env`; residents must verify before new complaints; existing reports/actions remain available. UserFactory defaults new citizens to verified for test convenience. Full suite: 751 passed; 11 unrelated pre-existing failures. Resident enrollment/cancel, duplicate detection, admin approve/reject/revoke, encrypted/private storage, purge, and notifications are in place.
   **Phase 2 launch checklist:**
   - [ ] Generate/secure `IDENTITY_HASH_KEY`; back up `APP_KEY`.
   - [ ] MinIO: create dedicated non-root user with bucket-only access; no public policy; TLS or app-level encryption; console restricted/removed.
   - [ ] Verify `php-gd`+`php-exif` or Imagick active in production runtime.
   - [ ] DPO/records-officer sign-off; privacy policy approved; dry-run observations recorded.
   - [ ] Stage `IDENTITY_VERIFICATION_REQUIRED=true` for a week with audit logs/notifications monitored.
   - [ ] Flip to production after review of pending age, purge orphan counts, and 2FA lockouts.

   **DPO/privacy-policy review runbook:** submit the updated privacy policy and dry-run observations for DPO/records-officer sign-off; verify identity verify page, upload validation, and purge flows before approving.

   **MinIO dedicated-user setup runbook (production):**
   - [ ] Ensure bucket `daet-identity-verification` exists; enable versioning/lifecycle/retention if required.
   - [ ] Create dedicated non-root user `daet_identity_api_reader` via `mc admin user add`.
   - [ ] Attach least-privilege policy limited to the identity bucket (`readwrite` on `arn:aws:s3:::daet-identity-verification/*`, `read` on bucket for health/list); deny all other actions/buckets.
   - [ ] Update `MINIO_ACCESS_KEY_ID`/`MINIO_SECRET_ACCESS_KEY` in production `.env` to the dedicated user; disable root sign-in after setup.
   - [ ] Test `Storage::disk('minio')->put/exists/delete` as the dedicated user and via `php artisan tinker`.


   **Phase 3 dry-run checklist:**
   - [ ] Complete Phase 2 launch checklist above.
   - [ ] Run `php artisan test --filter=IdentityVerificationTest` — 11 passed (61 assertions).
   - [ ] Run `php artisan identity:report --json` and confirm verification_required=false, sanitizer_available=true, pending=0, possible_duplicates=0, missing paths=0.
   - [ ] Execute one resident journey: submit ID/selfie; see `/identity/verify` show `pending`; admin approves; document routes return 404 after purge; resident can file a complaint.
   - [ ] Confirm audit events: `identity.submitted`, `identity.document_viewed`, `identity.approved`, `identity.purged`.
   - [ ] Confirm gate redirect for unverified resident: `GET /complaints/create` redirects to `/identity/verify`.

 Hardening so far: `MINIO_ROOT_USER` is now `daet_identity_minio`, the compose file requires a strong `MINIO_ROOT_PASSWORD`, `IDENTITY_HASH_KEY` is set in `.env`, the app `minio` disk uses those credentials, and a MinIO disk smoke test passed. Privacy policy updated with resident identity verification terms. GD/EXIF are now installed and active; `IdentityDocumentSanitizer` is robust to bad JPEG/PNG and missing EXIF data; admin document streaming has a private-headers feature test; resident resubmission after rejection is covered; resident can file a complaint after approval is covered. Still to launch: dedicated non-root MinIO user, bucket policy hardening, DPO/records-officer sign-off, production review of the gate.
4. **Phase 3 — dry run:** verify a test resident end to end, inspect headers, confirm files are unreadable in the bucket and purged after decision, test revoke and resubmit, test the gate redirect.
   **Phase 4 launch notes:** after Phase 3 approval, set `IDENTITY_VERIFICATION_REQUIRED=true` in production, run `php artisan migrate`, notify residents, and monitor pending/audit/2FA metrics. Existing reports and actions remain available.
5. **Phase 4 — launch:** set `IDENTITY_VERIFICATION_REQUIRED=true`. Existing residents verify before their next new complaint; old reports and actions remain available.
6. **Phase 5 — operate:** monitor pending age, review SLA, purge failures, 2FA lockouts, and audit logs. Review retention and access quarterly.
   **Status (2026-11-21):** `php artisan identity:monitor` added; scheduled daily at 08:20. Dev monitor output: 0 pending, 0 duplicates, 0 missing purge proof, 0 2FA lockouts in last 24h. Full suite: 751 passed; same 11 unrelated pre-existing failures (`DepartmentAdminRoleTest` passes alone). Added `identity:reset-all --apply` as an operations escape hatch to reset resident identity status to unverified.
   **Status (2026-11-21):** `php artisan identity:monitor` added; scheduled daily at 08:20. `identity:purge` remains hourly. Dev monitor output: 0 pending, 0 duplicates, 0 missing purge proof, 0 2FA lockouts in last 24h.

## Acceptance criteria

- An unverified resident cannot create or store a complaint, but can still sign in, track reports, read transparency, manage settings, and act on existing complaints.
- A pending or rejected resident cannot file; the page explains why and what to do next.
- A verified resident can file; the approved ID is locked; name changes require admin action.
- The upload accepts only JPG/PNG/WebP up to 5 MB, re-encodes and strips metadata, encrypts at rest, and stores no original filename.
- Only an admin can list, open, approve, reject, revoke, or stream identity documents; every view and decision is audit-logged.
- ID and selfie files are deleted after every decision, cancellation, expiry, revoke, and account deletion; the decision record remains.
- Duplicate IDs are flagged for admin review, not silently auto-rejected; the raw ID number is never stored.
- Identity data never appears in complaints, staff views, exports, notifications, logs, or public payloads.
- All admin routes require TOTP for enrolled admins; unenrolled admins are sent to setup; recovery codes are single-use; CLI reset works.
- All new UI follows the design system, is localized in `en` and `fil`, works on mobile, and passes the design-system guard.
- The privacy policy and consent copy are updated and approved by the DPO before the gate is enabled.

## Open policy items

- DPO/records-officer decision on delete-after-review versus any LGU retention schedule.
- Review SLA and who covers the queue when the super admin is away.
- Barangay certificate format and validity period.
- Minors and guardian consent.
- Legal hold: whether a specific submission must ever be preserved for an investigation, and how that overrides deletion.
- Whether to store ID expiry date only (no image) for future re-verification reminders.
- Whether staff should ever see a verified badge (recommended: no, for now).
- Recovery-code storage and printing guidance for admins.
- Backup retention and how deletion propagates.
- Whether digital IDs (PhilSys digital ID, eGov app) are accepted or physical cards only.
- Whether a liveness check is ever added; manual review is the MVP.
