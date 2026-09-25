# 🐛 Bug Fix Plan — AdminController `actor_id` & Staff Toggle

**Date:** September 25, 2026
**Priority:** Critical (breaks audit trail)
**Estimated Time:** 20 minutes

---

## 🎯 Objective

Fix two bugs in `AdminController.php` that cause audit log entries to have `actor_id = null` instead of recording the admin who performed the action. This breaks the core "accountability through immutability" principle of Daet Listens.

---

## Bug #1: `bulkReassign` — Wrong column name

**File:** `app/Http/Controllers/AdminController.php`
**Line:** ~143

**Current:**
```php
'changed_by' => Auth::id(),
```

**Fix:**
```php
'actor_id' => Auth::id(),
```

**Context:** The `bulkReassign` method creates `ComplaintLog` entries when complaints are reassigned between departments. The `complaint_logs` table uses `actor_id` (not `changed_by`). The `ComplaintLog` model's `$fillable` only includes `actor_id`.

---

## Bug #2: `complaintUpdate` — Wrong column name

**File:** `app/Http/Controllers/AdminController.php`
**Line:** ~288

**Current:**
```php
'changed_by' => Auth::id(),
```

**Fix:**
```php
'actor_id' => Auth::id(),
```

**Context:** Same as above — the `complaintUpdate` method creates `ComplaintLog` entries when admin updates status/department/staff. Same `actor_id` issue.

---

## Bug #3: `staffToggleStatus` — Stub implementation

**File:** `app/Http/Controllers/AdminController.php`
**Lines:** 357-365

**Current:** Method is a stub — just returns back with a message, does nothing:
```php
public function staffToggleStatus(User $user): RedirectResponse
{
    if ($user->role !== 'staff') {
        return back()->with('error', 'Can only toggle staff accounts.');
    }
    // Stub — does nothing
    return back()->with('status', 'Staff status updated.');
}
```

**Fix plan:**
1. Add `is_active` boolean column to `users` table (or use existing field if any)
2. Implement toggle logic: `$user->update(['is_active' => !$user->is_active])`
3. Create `is_active` boolean field in `User` model `$fillable`
4. Add database migration for `is_active` column

**Wait — check if `is_active` already exists on users table.** Need to verify before adding migration.

---

## 📋 Step-by-Step Implementation

### Step 1: Fix Bug #1 — `bulkReassign` actor_id
```php
// In bulkReassign method, line ~143
ComplaintLog::create([
    'complaint_id' => $complaint->id,
    'previous_status' => $complaint->statusEnum->value,
    'new_status' => $complaint->statusEnum->value,
    'comment' => "Reassigned from {$oldDeptName} to {$newDeptName} by admin",
    'actor_id' => Auth::id(),  // Was 'changed_by'
]);
```

### Step 2: Fix Bug #2 — `complaintUpdate` actor_id
```php
// In complaintUpdate method, line ~288
ComplaintLog::create([
    'complaint_id' => $complaint->id,
    'previous_status' => $oldStatus->value,
    'new_status' => $updates['status'] ?? $oldStatus->value,
    'comment' => $note,
    'actor_id' => Auth::id(),  // Was 'changed_by'
]);
```

### Step 3: Fix Bug #3 — `staffToggleStatus` implementation
1. Check if `is_active` column exists on `users` table
2. If not, create migration to add it
3. Implement toggle logic
4. Add `is_active` to User model `$fillable`

### Step 4: Verify all tests still pass
```bash
php artisan test
```

### Step 5: Update `PROGRESS.md` with completion

---

## ⚠️ Risks

- Adding `is_active` to users table requires a migration and may affect existing seed data
- The `User` model may already have an `is_active` field — need to verify before adding
- `staffToggleStatus` route may not exist in `routes/web.php` — need to verify

---

## ✅ Acceptance Criteria

1. All 18 tests pass after fixes
2. `ComplaintLog` entries from admin actions have correct `actor_id`
3. `staffToggleStatus` actually toggles staff account status
4. No new regressions introduced
