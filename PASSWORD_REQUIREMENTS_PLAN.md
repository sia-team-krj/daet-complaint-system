# Password Requirements UX Plan

**Date:** September 25, 2026
**Scope:** Registration and invitation redemption password fields
**Status:** Implemented and verified on September 25, 2026

## Goal

Make password requirements visible while a user types instead of relying only on a submit-time validation error.

## Forms

- Standard resident registration: `resources/views/auth/register.blade.php`
- Staff invitation redemption: `resources/views/invitations/redeem.blade.php`

## Behavior

- Show a compact requirements checklist below the password field.
- Update each item live as the password changes.
- Mark requirements as met or unmet without relying only on color.
- Keep the checklist accessible with `aria-live` and semantic list markup.
- Preserve the existing server-side validation rules:
  - At least 8 characters
  - Uppercase and lowercase letters
  - At least one number
- Apply the same behavior to password confirmation fields without duplicating the requirements list.
- Do not display or log the password value.

## Implementation

- Add a reusable password-checklist partial.
- Add a small reusable script initializer in the shared app JavaScript.
- Include the partial and script data attributes in both forms.
- Add a focused feature test for the rendered registration and invitation forms.

## Acceptance Criteria

- Both forms show the password requirements before submission.
- Requirements update live while typing.
- Existing validation remains unchanged.
- Tests and frontend build pass.
