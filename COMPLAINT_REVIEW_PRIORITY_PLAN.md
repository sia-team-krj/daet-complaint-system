# Complaint Review and Priority Workflow Plan

**Date:** September 25, 2026
**Scope:** Remove resident urgency, add system suggestions, mandatory department review, confirmed priority, verified public visibility, and staff pseudonym privacy
**Status:** Implemented and verified on September 25, 2026

## Goal

Make complaint intake review-first and privacy-safe:

1. Residents report the problem without choosing urgency.
2. The system suggests a priority from category and description.
3. Complaints remain pending until a department verifies legitimacy.
4. Staff confirm the final priority after review.
5. The system shows suggested and confirmed priority separately.
6. A complaint becomes publicly visible only after verification and explicit public consent.
7. Staff see only a resident alias/ pseudonym; administrators retain real-identity access.

## Current-state risks to address

- Resident complaint form currently requires an urgency value.
- Complaint status currently starts as `Submitted` without a separate verification state.
- Public visibility is controlled by `is_public` but is not gated by verification.
- Staff views may display `full_name` or real identity.
- Existing moderation fields and immutable activity logging should be reused rather than duplicated.

## Implementation plan

### Data model

Add migration fields to `complaints`:

- `suggested_priority` — system-generated, non-binding
- `confirmed_priority` — department-confirmed value
- `review_status` — `pending`, `verified`, `needs_information`, `duplicate`, `rejected`, `escalated`
- `reviewed_by` — staff/admin reviewer
- `reviewed_at`
- `review_notes` — concise internal/public-safe review note as appropriate

Use a controlled priority vocabulary:

- `routine`
- `elevated`
- `urgent`
- `critical`

Preserve the existing `urgency` column temporarily for historical records, but remove it from new resident submissions and stop displaying it as resident input.

### System suggestion

Create a dedicated suggestion service that evaluates:

- Category baseline
- Safety/access keywords
- Blocked-access and essential-service signals
- Repeated nearby reports when available

The result is advisory only. It must not approve, reject, or change the complaint status.

### Review workflow

- New complaints start with `review_status = pending` and `confirmed_priority = null`.
- Staff review queue shows pending complaints first.
- Staff can verify, request information, mark duplicate, escalate, or reject with a reason.
- Verification requires a confirmed priority and reviewer metadata.
- Status transitions and review decisions create immutable complaint logs and activity logs.
- Only verified complaints can be marked public by authorized staff/admin.

### Privacy

- Resident forms and complaint descriptions never require a legal name in public views.
- Staff list/detail views use `user->public_name`/display alias only.
- Real identity is shown only in the admin-only identity panel.
- Public APIs/transparency data use aliases only.
- Review notes shown to staff must not contain private identity data.

### UI

- Remove urgency controls from the resident complaint form.
- Explain that the department reviews urgency after submission.
- Add review status, suggested priority, and confirmed priority to staff queue/detail views.
- Add a clear staff review action panel.
- Add admin-only identity disclosure.
- Add verified-public eligibility messaging.

### Tests

Add coverage for:

- Resident form no longer requires urgency.
- New complaints start pending and receive a suggestion.
- Staff cannot see real resident names.
- Admin can see real identity only through the admin surface.
- Verification requires confirmed priority.
- Unverified complaints cannot become public.
- Review decisions and priority changes are audit logged.
- Existing complaint workflows and role isolation remain intact.

## Verification

- Run migrations and the full test suite.
- Run Blade cache, production build, and diff checks.
- Verify complaint submission, staff queue/detail, admin detail, and public transparency manually where possible.
