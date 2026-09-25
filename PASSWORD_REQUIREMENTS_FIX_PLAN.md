# Password Checklist Interaction Fix

**Date:** September 25, 2026
**Issue:** The invitation password checklist remained static while typing.
**Status:** Fixed and verified on September 25, 2026

## Root Cause

The shared checklist initializes by finding `document.getElementById('password')`. The invitation form's password input did not have that ID, so no input listener was attached. The standard registration input already had the correct ID.

## Fix

Add `id="password"` to the invitation redemption password input and verify both forms initialize the checklist.

## Acceptance Criteria

- Typing in the invitation password field updates all three requirement rows.
- Standard registration behavior remains intact.
- Tests and build pass.
