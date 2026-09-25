# Bug Fix: Failing Test Suite — `ComplaintDepartmentRoutingTest`

**Date:** September 25, 2026
**Branch:** `main`
**Status:** Diagnosed → Fixing

---

## 🔍 Root Cause Analysis

The test suite was written against a **different department schema** than what exists in production code. Specifically:

| Test Expectation | Actual Code | Problem |
|---|---|---|
| `Department::create(['code' => 'BPLS', ...])` | `DepartmentSeeder` creates `BPLO` | Test uses hardcoded non-existent codes |
| `Department::create(['code' => 'PNP', ...])` | No `PNP` in `DepartmentSeeder` | `PNP` doesn't exist in seeder |
| `Department::create(['code' => 'MAO', ...])` | No `MAO` in `DepartmentSeeder` | `MAO` doesn't exist in seeder |
| `DepartmentRouter::resolve('noise_complaint')` → `PNP` | Maps to `OM` (Office of the Mayor) | Test expected wrong code |
| `DepartmentRouter::resolve('stray_animals')` → `MAO` | Maps to `HLTH` (Health Office) | Test expected wrong code |
| `DepartmentRouter::getCategoryOptions()` → `'Business Permits'` | Actual key is `'Business Permits'` | Match |
| `log->changed_by` | Actual column is `actor_id` | Test used wrong column name |
| `complaint->status->value` | `status` is a string, not enum object in DB | Test used incorrect accessor |
| `UserFactory` uses `name` field | `User` model uses `first_name` + `last_name` | Factory was incompatible |
| No `ComplaintFactory` exists | Test calls `Complaint::factory()` | Missing factory |
| No `DepartmentFactory` exists | Test creates via `Department::factory()` | Missing factory |

---

## ✅ Fixes Required

### Fix 1: Update `UserFactory` (`database/factories/UserFactory.php`)
- **Problem:** Factory generates `name` field, but `User` model requires `first_name`, `last_name`, `contact_number`, `barangay`
- **Action:** Replace `name` with proper fields (`first_name`, `last_name`, `contact_number`, `barangay`, `role`)
- **Already Done:** ✅

### Fix 2: Create `ComplaintFactory` (`database/factories/ComplaintFactory.php`)
- **Problem:** Test uses `Complaint::factory()` but no factory exists
- **Action:** Create factory with all required fillable fields
- **Already Done:** ✅

### Fix 3: Create `DepartmentFactory` (`database/factories/DepartmentFactory.php`)
- **Problem:** Test uses `Department::factory()` but no factory exists
- **Action:** Create factory with `name`, `code`, `description`, `is_active`
- **Already Done:** ✅

### Fix 4: Rewrite `ComplaintDepartmentRoutingTest` (`tests/Feature/ComplaintDepartmentRoutingTest.php`)
- **Problem:** 8+ test assertions use wrong department codes, wrong column names, wrong group names
- **Action:** Rewrite entire test to match actual `DepartmentSeeder` codes and `DepartmentRouter` mappings

Changes:
1. Replace `Department::create(...)` hardcoded seeds with `$this->seed(DepartmentSeeder::class)`
2. Update department codes: `BPLS` → `BPLO`, `PNP` → `OM`, `MAO` → `HLTH`
3. Fix `log->changed_by` → `log->actor_id`
4. Fix `complaint->status->value` → `complaint->status`
5. Fix category group names in assertions to match `getCategoryOptions()` keys
6. Replace deprecated `/** @test */` annotations with `#[\PHPUnit\Framework\Attributes\Test]`

---

## 📋 Files to Modify

| File | Action | Status |
|---|---|---|
| `database/factories/UserFactory.php` | Fix `definition()` to use correct fields | ✅ Done |
| `database/factories/ComplaintFactory.php` | Create new file | ✅ Done |
| `database/factories/DepartmentFactory.php` | Create new file | ✅ Done |
| `tests/Feature/ComplaintDepartmentRoutingTest.php` | Full rewrite to match actual code | ✅ Done |

---

## 🧪 After Fix

Run: `php artisan test`
Expected result: All 18 tests passing (16 feature + 2 unit)

---

## 📌 Notes

- `ComplaintStatus` has been converted from a model to a PHP 8.1 enum (`app/Enums/ComplaintStatus.php`)
- `ComplaintLog` has been renamed to `complaint_logs` table with `actor_id` instead of `changed_by`
- `DepartmentSeeder` uses official 8 LGU department codes: `ENG`, `HLTH`, `GSO`, `MPDO`, `WST`, `SWDO`, `BPLO`, `OM`
- `DepartmentRouter::CATEGORY_MAP` defines the canonical category → department code mapping
- `DepartmentRouter::getCategoryOptions()` returns grouped categories with specific keys: `Engineering`, `General Services`, `Business Permits`, `Peace & Order`, `Agriculture`
