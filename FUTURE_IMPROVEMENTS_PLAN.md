# Daet Listens Future Fixes & Improvements Roadmap

**Date:** September 25, 2026
**Status:** Planned — no implementation changes are included in this document
**Scope:** Email delivery, user notifications, abuse prevention, and visual theming

## Purpose

This document records the next improvements discussed after the complaint transparency and mobile UI work. Each item should be implemented as a separate, reviewable change so security and privacy behavior can be verified without combining unrelated releases.

## Priority order

| Priority | Work item | Why it comes next |
|---|---|---|
| P0 | Real password-reset email | Users must be able to recover accounts outside the local test environment. |
| P1 | User notifications for complaint updates | Residents should know when their reports receive a response or status change. |
| P1 | Additional account and complaint anti-spam controls | Reduce automated abuse without punishing legitimate residents. |
| P2 | Color theme change and design-token system | Improve visual consistency after the highest-risk account workflows are stable. |

---

## 1. Real password-reset email

### Current gap

The application has a forgot-password flow, but local development uses Mailpit. Mailpit is useful for testing and is not a real external delivery service. A resident cannot reliably recover an account until a production mail provider is configured.

### Proposed implementation

1. Select a production mail provider or approved SMTP service.
2. Configure the provider through environment variables; do not commit credentials.
3. Keep Mailpit for local development and automated tests.
4. Verify Laravel's password-reset broker sends the reset notification through the configured mailer.
5. Queue mail delivery where the provider supports it, with retry and failure handling.
6. Add delivery observability without logging reset tokens or full email addresses.

### Minimum environment configuration

Document the required variables before enabling the feature:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="Daet Listens"
APP_URL=https://leaf-1.tail05a1ab.ts.net
```

The final variable names may follow the selected provider's Laravel driver. Secrets must be supplied through the deployment environment.

### Security requirements

- Use a short-lived, single-use reset token.
- Return the same generic response whether the email exists or not, to prevent account enumeration.
- Rate-limit reset requests by IP and email identifier.
- Do not reveal whether a complaint or account is registered.
- Do not place reset tokens in logs, URLs sent to third parties, or analytics events.
- Invalidate or rotate active sessions after a successful password reset if the account-security review approves it.
- Preserve the existing minimum password rules: eight characters, mixed case, and a number.
- Do not reintroduce a breach-database password lookup unless a separate privacy and security decision approves it.

### Acceptance criteria

- A real resident receives a reset email in a staging environment.
- The reset link opens a valid form and successfully changes the password.
- A token cannot be reused after success or expiry.
- Expired, malformed, and already-used tokens return a safe error.
- Repeated requests are throttled and cannot be used to enumerate accounts.
- Mail failure does not expose internal provider details to the user.
- Tests use Mailpit/fakes and never send messages to real addresses.

### Likely files

- `app/Notifications/ResetPasswordNotification.php` (existing)
- `app/Http/Controllers/AuthController.php`
- `config/auth.php`
- `config/mail.php`
- `.env.example`
- `tests/Feature/PasswordRequirementsTest.php` or a new password-reset feature test

---

## 2. User notifications for complaint updates

### Goal

Keep residents informed when their own complaints change materially, while avoiding unnecessary email and never exposing internal staff notes or private account data.

### Notification events

The first release should cover:

- Complaint received and assigned to a department.
- Complaint enters `Under Review`.
- Complaint enters `In Progress`.
- Complaint is `Resolved`.
- Complaint is `Rejected` or `Closed`.
- A staff member adds a public response.

Internal notes, moderation notes, and private review details must never generate resident notifications unless explicitly marked as public.

### Channels

Start with two deliberate channels:

1. **In-app notification:** a notification center and unread indicator for signed-in residents.
2. **Email notification:** a queued email for users who have verified an email address and enabled updates.

Staff and admin notifications can be added separately after the resident flow is stable. Do not send every internal activity to every user.

### Proposed data model

Use Laravel notifications with a database channel. Add a notification preference record or fields for:

- Email notifications enabled.
- Status update notifications enabled.
- Documentation/photo update notifications enabled.
- Preferred email address and verification state.

### UX requirements

- Show a clear unread count in the resident navigation.
- Group notifications by complaint and newest-first order.
- Include ticket ID, plain-language status, date, and a direct link to the resident's complaint detail page.
- Use the complaint policy for every notification link.
- Provide an empty state and a way to mark notifications as read.
- Never show a public transparency URL for a private complaint.
- Make email unsubscribe/preferences explicit without removing the required legal/account notices.

### Reliability and privacy

- Queue notifications so a mail outage does not block a complaint update.
- Use idempotency so a retried job does not create duplicate notifications.
- Store only the minimum notification payload needed for display.
- Redact private descriptions, exact addresses, internal notes, and private evidence from notification previews.
- Apply retention rules to old notifications.

### Acceptance criteria

- A resident receives one in-app notification for each material public status change.
- A verified resident with email enabled receives one queued email per event.
- Internal notes never appear in resident notifications or email previews.
- Duplicate queue retries do not create duplicate notifications.
- A notification link always applies the complaint authorization policy.
- Users can disable non-essential email notifications.
- Notification tests cover authorization, deduplication, privacy, and email failure.

### Likely files

- `app/Events/ComplaintStatusUpdated.php` (new)
- `app/Listeners/SendComplaintNotifications.php` (new)
- `app/Notifications/` (new notification classes)
- `resources/views/notifications/`
- `resources/views/components/` or the resident navigation partial
- `database/migrations/*_create_notifications_table.php`
- `tests/Feature/ComplaintNotificationsTest.php`

---

## 3. Additional account and complaint anti-spam controls

### Goal

Reduce automated registrations, fake complaint submissions, and repeated abuse while keeping legitimate residents able to use the service. Existing detection and audit behavior must remain intact.

### Layered controls

#### A. Baseline rate limits

Apply Laravel throttling to:

- Login attempts.
- Registration submissions.
- Forgot-password requests.
- Complaint creation.
- Evidence upload attempts.
- Invitation redemption.

Use both authenticated and unauthenticated identifiers where appropriate. Return safe, user-readable retry messages.

#### B. Bot protection

Add a privacy-conscious CAPTCHA or challenge only after abuse is detected or for high-risk actions. Prefer a managed challenge such as Cloudflare Turnstile if approved by the deployment owner. Do not add a third-party tracker to public pages unnecessarily.

#### C. Email and account verification

- Require email verification before sensitive bulk actions.
- Detect obviously invalid or disposable email domains as a review signal, not an automatic permanent ban.
- Keep role assignment restricted to the existing admin/staff invitation workflow.
- Do not allow public users to select staff or admin roles.

#### D. Complaint abuse signals

Use existing signals before adding new data collection:

- Duplicate content and media fingerprints.
- Repeated category/location/text combinations.
- Submission rate and burst detection.
- Newly registered accounts submitting many reports.
- Metadata and moderation score.

Flag suspicious complaints for staff review instead of deleting them silently. Preserve the current public-visibility and audit safeguards.

#### E. Account protection

- Lock or challenge repeated failed logins without creating an easy denial-of-service vector.
- Invalidate suspicious sessions after a security event.
- Record security-relevant actions in the audit log without storing passwords, reset tokens, or unnecessary device fingerprints.
- Define retention and access rules for IP/device data before collecting it.

### Privacy guardrails

- No password or reset token logging.
- No breach-password lookup unless separately approved.
- No permanent account punishment based only on a heuristic.
- No public exposure of moderation signals or spam scores.
- Staff must be able to review and reverse false positives.

### Acceptance criteria

- Automated registration and complaint bursts are throttled.
- High-risk abuse requires a challenge or staff review rather than silent deletion.
- Legitimate residents can recover from a temporary challenge.
- Security events are auditable without exposing secrets.
- Spam signals never bypass complaint ownership and privacy policies.
- Automated tests cover throttles, challenge bypass attempts, duplicate reports, and moderation review.

---

## 4. Color theme change

### Decision needed first

The current Daet Listens identity uses navy, gold, cream, and restrained status colors. A theme change should begin with an approved palette and contrast target, not a collection of one-off CSS edits.

### Recommended direction

Create a centralized token layer for:

- Brand navy surfaces.
- Gold action and focus states.
- Cream/neutral page surfaces.
- Text colors for light and dark surfaces.
- Success, warning, error, and information states.
- Borders, muted text, shadows, and focus rings.

Then map the tokens consistently across:

- Public pages.
- Resident dashboard and complaint details.
- Staff workspace and queue.
- Admin workspace.
- Emails and notifications.

### Theme options to evaluate

1. Keep the current navy/gold identity and improve contrast tokens.
2. Add a dark mode using the same semantic tokens.
3. Introduce a restrained municipal accent palette while retaining navy as the structural color.

Do not introduce emoji as icons or rely on color alone to communicate status. Every state must retain a text label, icon, or pattern.

### Accessibility requirements

- Body text contrast of at least WCAG AA.
- Visible keyboard focus states.
- Status colors tested against both light and dark surfaces.
- No color-only meaning for status, errors, or verification.
- Responsive layouts tested on mobile, tablet, and desktop.
- User preference support if a theme toggle is introduced.

### Acceptance criteria

- Approved palette and semantic token names are documented.
- Public, resident, staff, and admin surfaces use the same token system.
- Contrast and focus checks pass on every supported theme.
- No private data or authorization behavior changes as part of the theme work.
- Visual regression checks cover the transparency register, resident complaint detail, staff queue, and admin complaint detail.

---

## Recommended delivery sequence

### Phase 1 — Account recovery

- Select and configure a real mail provider.
- Verify reset delivery in staging.
- Add throttling, generic responses, and reset-flow tests.
- Document secret rotation and provider failure handling.

### Phase 2 — Resident notifications

- Define notification events and privacy rules.
- Add the database notification channel.
- Add the resident notification center.
- Add queued email delivery and preferences.
- Test deduplication and authorization.

### Phase 3 — Abuse prevention

- Add baseline rate limits.
- Add a privacy-reviewed bot challenge.
- Add account and complaint monitoring signals.
- Test false-positive recovery and audit coverage.

### Phase 4 — Theme system

- Approve the palette and token names.
- Refactor shared styles.
- Add theme/accessibility regression checks.
- Roll out by surface with screenshots on mobile and desktop.

## Global guardrails

- Keep resident, staff, and admin role boundaries intact.
- Keep private complaints, internal notes, exact addresses, and MinIO paths private.
- Never send secrets, reset tokens, passwords, or full private payloads through notifications.
- Keep public and authenticated complaint authorization policy-protected.
- Do not push deployment changes until staging verification and environment configuration are complete.
- Update this document and the relevant feature plan as each item moves from planned to implemented.

## Future verification checklist

- [ ] Real password-reset email tested in staging.
- [ ] Password-reset abuse and enumeration tests pass.
- [ ] Resident notification events and preferences implemented.
- [ ] Notification deduplication and privacy tests pass.
- [ ] Rate limits and bot challenge reviewed by the deployment owner.
- [ ] Anti-spam false-positive recovery tested.
- [ ] Theme palette and contrast approved.
- [ ] Public, resident, staff, and admin surfaces visually reviewed.
- [ ] Full test suite, Blade cache, production build, and deployment smoke test pass.
