# Profile Resolution Query Fix Plan

**Date:** September 25, 2026
**Scope:** Fix the ambiguous PostgreSQL timestamp reference on `/profile`
**Status:** Implemented and verified on September 25, 2026

## Problem

`ProfileController` joins `complaint_logs` to `complaints` and calculates an average using an unqualified `created_at`. PostgreSQL reports `42702: Ambiguous column: created_at`.

## Fix

- Qualify the complaint timestamp as `complaints.created_at`.
- Qualify the log timestamp as `complaint_logs.created_at` where needed.
- Preserve the existing actor/status/month/year filters.
- Register the profile update/password POST routes required by the existing profile form so the page remains usable after the query is fixed.
- Add a feature regression test that renders `/profile` for a user with complaint activity.

## Verification

- `/profile` returns successfully with PostgreSQL-compatible SQL.
- Regression test passes.
- Full test suite, Blade cache, production build, and diff checks pass.
