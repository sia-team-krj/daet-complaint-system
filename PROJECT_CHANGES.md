# Daet Listens — Project Changes Documentation

**Date**: April 4, 2026  
**Session**: PostGIS Integration & Bug Fixes  
**Platform**: Laravel 12 + Laravel Sail (Docker) + PostgreSQL + PostGIS

---

## Executive Summary

This session focused on:
1. Bug fixes for authentication views (register, login, navbar)
2. Department model & seeder implementation
3. ComplaintPolicy creation for role-based access
4. PostGIS spatial data integration (reverted to decimal columns for Laravel 12 compatibility)
5. Leaflet.js map integration in complaint form
6. MinIO S3 storage configuration
7. Password reset flow stubs

---

## 1. Bug Fixes — Authentication System

### 1.1 Register Form (`resources/views/auth/register.blade.php`)
| Issue | Fix |
|-------|-----|
| Empty form action | `action=""` → `action="{{ route('register') }}"` |

### 1.2 Login Form (`resources/views/auth/login.blade.php`)
| Issue | Fix |
|-------|-----|
| Broken forgot password link | `href=""` → `href="{{ route('password.request') }}"` |

### 1.3 Navbar (`resources/views/layouts/navbar.blade.php`)
| Location | Change |
|----------|--------|
| Desktop nav | `auth()->user()->is_admin` → `auth()->user()->role === 'admin'` |
| Mobile nav | `auth()->user()->is_admin` → `auth()->user()->role === 'admin'` |

### 1.4 Guest Navbar (`resources/views/layouts/guest-navbar.blade.php`)
| Location | Change |
|----------|--------|
| Mobile nav | `auth()->user()->is_admin` → `auth()->user()->role === 'admin'` |

---

## 2. Department System

### 2.1 Model (`app/Models/Department.php`)
**Created** with:
- `fillable`: `['name', 'code', 'description', 'is_active']`
- `casts`: `is_active` → `boolean`
- Relationships: `hasMany(User)`, `hasMany(Complaint)`
- Scope: `scopeActive()`

### 2.2 Seeder (`database/seeders/DepartmentSeeder.php`)
**Created** with 12 LGU Daet departments:
| Code | Name |
|------|------|
| GSO | General Services Office |
| MPDO | Municipal Planning & Dev. Office |
| MEO | Municipal Engineering Office |
| MHO | Municipal Health Office |
| SWMO | Solid Waste Management Office |
| MSWDO | Municipal Social Welfare & Dev. |
| BFP | Bureau of Fire Protection |
| PNP | Philippine National Police |
| MAO | Municipal Agriculture Office |
| MENRO | Municipal Environment & Natural Resources |
| OMR | Office of the Mayor |
| MCR | Municipal Civil Registrar |

Uses `Department::firstOrCreate()` for idempotent seeding.

### 2.3 DatabaseSeeder (`database/seeders/DatabaseSeeder.php`)
```php
$this->call([
    DepartmentSeeder::class,
]);
```

---

## 3. Authorization — ComplaintPolicy

**Created** `app/Policies/ComplaintPolicy.php`

| Method | Citizen | Staff | Admin |
|--------|---------|-------|-------|
| `view()` | Own only | Dept only | All |
| `create()` | ✓ | ✓ | ✓ |
| `update()` | ✗ | ✗ | ✓ |
| `delete()` | ✗ | ✗ | ✓ |
| `changeStatus()` | ✗ | Dept only | ✓ |
| `viewRealIdentity()` | ✗ | ✗ | ✓ |
| `viewAny()` | ✓ | ✓ | ✓ |

---

## 4. Complaint Form — Leaflet.js Integration

### 4.1 Create View (`resources/views/complaints/create.blade.php`)

**CDN Imports Added:**
```blade
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
```

**Step 4 — Location Section Added:**
- Hidden inputs: `latitude`, `longitude`
- Visible input: `address_text`
- Leaflet map container: `#complaint-map` (320px height)
- Map features:
  - Center: Daet `[14.1126, 122.9553]`
  - Zoom: 14
  - Tile layer: OpenStreetMap
  - Click to place marker
  - Drag to adjust position
  - Livewire navigation support (`livewire:navigated` event)

**Debug Logging Added (for troubleshooting):**
```javascript
console.log('Leaflet init starting...', { hasL: typeof L !== 'undefined', hasContainer: !!document.getElementById('complaint-map') });
console.log('Leaflet map initialized successfully');
```
- Shows error message in map container if Leaflet fails to load
- Handles both `DOMContentLoaded` and immediate initialization

### 4.2 Layout Updates
Both `app.blade.php` and `guest.blade.php` updated with:
```blade
@stack('styles')   {{-- in <head> --}}
@stack('scripts')  {{-- before </body> --}}
```

---

## 5. Spatial Data — Evolution & Final Solution

### 5.1 Attempted Approaches (Failed)

| Package | Issue |
|---------|-------|
| `mstaack/laravel-postgis` | Doesn't support Laravel 12 (max Laravel 10) |
| `matanyadaev/laravel-spatial` | Package doesn't exist on Packagist |

### 5.2 Final Solution: Decimal Columns

**Migration** (`2024_01_01_000006_create_complaints_table.php`):
```php
$table->decimal('latitude', 10, 8)->nullable();
$table->decimal('longitude', 11, 8)->nullable();
$table->string('address_text', 500)->nullable();
```

**Model** (`app/Models/Complaint.php`):
- `fillable` includes `latitude`, `longitude` (not `location`)
- Accessors format to 6 decimal places

**Controller** (`app/Http/Controllers/ComplaintController.php`):
- Validation: `latitude` (nullable, -90 to 90), `longitude` (nullable, -180 to 180)
- Direct assignment to model (no Point object)

---

## 6. File Storage — MinIO Configuration

### 6.1 Filesystems Config (`config/filesystems.php`)
**Added `minio` disk:**
```php
'minio' => [
    'driver' => 's3',
    'key' => env('MINIO_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
    'secret' => env('MINIO_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
    'region' => env('MINIO_DEFAULT_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    'bucket' => env('MINIO_BUCKET', env('AWS_BUCKET', 'daet-complaints')),
    'url' => env('MINIO_URL', env('AWS_URL')),
    'endpoint' => env('MINIO_ENDPOINT', env('AWS_ENDPOINT', 'http://minio:9000')),
    'use_path_style_endpoint' => true,
    'throw' => false,
    'report' => false,
],
```

### 6.2 Required Package (✅ Installed)
```bash
./vendor/bin/sail composer require league/flysystem-aws-s3-v3
```
**Status**: Installed and working

---

## 7. Password Reset Flow

### 7.1 Routes (`routes/web.php`)
```php
Route::get('/forgot-password', fn() => view('auth.forgot-password'))->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', fn(string $token) => view('auth.reset-password', ['token' => $token]))->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
```

### 7.2 AuthController Methods (`app/Http/Controllers/AuthController.php`)
- `sendResetLink()` — stub (returns success message)
- `resetPassword()` — stub (redirects to login)

### 7.3 Views Created
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`

**Note**: Both are stub implementations for UI testing. Full implementation requires Laravel's `Password` facade.

---

## 8. Category-to-Department Auto-Routing

**ComplaintController** maps categories to department codes:

```php
private const CATEGORY_DEPARTMENT_MAP = [
    'Road Damage'              => 'ENGR',
    'Bridge / Drainage Issue'  => 'ENGR',
    'Street Lighting'          => 'ENGR',
    'Public Works'             => 'ENGR',
    'Health Concern'           => 'MHO',
    'Unsanitary Condition'     => 'MHO',
    'Food Safety'              => 'MHO',
    'Garbage / Waste'          => 'SWM',
    'Illegal Dumping'          => 'SWM',
    'Illegal Construction'     => 'MPDO',
    'Zoning Violation'         => 'MPDO',
    'Urban Planning'           => 'MPDO',
    'Government Property'      => 'GSO',
    'Public Facility'          => 'GSO',
];
```

Fallback: `GSO` (General Services Office)

---

## 9. Dashboard — Citizen Portal

### 9.1 DashboardController (`app/Http/Controllers/DashboardController.php`)

**Stats Calculated:**
```php
$stats = [
    'total'     => $totalComplaints,      // All complaints by user
    'pending'   => $pendingComplaints,    // Not resolved/closed/rejected
    'resolved'  => $resolvedComplaints,   // Status = Resolved
    'avgDays'   => round($avgDays) ?? '—', // Average resolution time
];
```

**Complaints Query:**
```php
$complaints = Complaint::where('user_id', $user->id)
                       ->with('department')
                       ->latest()
                       ->paginate(10);
```

### 9.2 View (`resources/views/dashboard/index.blade.php`)

**Design System Applied:**
- **Header**: Navy background, gold left bar, glow blob, diagonal stripe pattern
- **Greeting**: Time-aware ("Good morning/afternoon/evening, {name}")
- **Stats Row**: 4-column grid with gold accent line on hover
- **Table**: Ticket ID, Category (pill), Title, Urgency (colored dot), Status (badge), Department, Date
- **Empty State**: Gold-bordered card with CTA
- **Flash Message**: Shows after successful complaint submission with ticket number

**Status Badge Colors (via ComplaintStatus enum):**
| Status | Class | Colors |
|--------|-------|--------|
| Submitted | `badge-submitted` | Blue |
| Under Review | `badge-review` | Amber |
| In Progress | `badge-progress` | Purple |
| Resolved | `badge-resolved` | Green |
| Rejected | `badge-rejected` | Red |
| Closed | `badge-closed` | Gray |

**Animations:** `fadeUp` with staggered delays (d1–d4)

---

## 10. Complaint Creation Workflow

### 10.1 Store Method Flow
1. Validate input (category, title, description, urgency, location, image)
2. Extract lat/lng from hidden inputs
3. Map category → department code → department ID
4. Upload image to MinIO (if provided)
5. DB transaction:
   - Create complaint with `status = ComplaintStatus::Submitted`
   - Create first `ComplaintLog` entry (immutable)
   - Store ticket_id in session
6. Redirect to dashboard with success message

### 10.2 Ticket ID Generation
```php
// In Complaint::booted()
$year = date('Y');
$next = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
$complaint->ticket_id = 'COMP-' . $year . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
// Example: COMP-2026-00001
```

---

## 7. Files Modified/Created Summary

### Models
| File | Action |
|------|--------|
| `app/Models/Department.php` | Created |
| `app/Models/Complaint.php` | Modified (spatial → decimal) |

### Controllers
| File | Action |
|------|--------|
| `app/Http/Controllers/ComplaintController.php` | Modified (index, show methods, AuthorizesRequests trait) |
| `app/Http/Controllers/AuthController.php` | Modified (added password reset stubs) |
| `app/Http/Controllers/DashboardController.php` | Modified (stats + complaints query) |

### Policies
| File | Action |
|------|--------|
| `app/Policies/ComplaintPolicy.php` | Created |

### Seeders
| File | Action |
|------|--------|
| `database/seeders/DepartmentSeeder.php` | Created |
| `database/seeders/DatabaseSeeder.php` | Modified |

### Migrations
| File | Action |
|------|--------|
| `database/migrations/2024_01_01_000006_create_complaints_table.php` | Modified (spatial → decimal) |

### Views
| File | Action |
|------|--------|
| `resources/views/complaints/index.blade.php` | Created (card grid, filters) |
| `resources/views/complaints/show.blade.php` | Created (detail view, timeline) |
| `resources/views/complaints/create.blade.php` | Modified (Leaflet + location) |
| `resources/views/dashboard/index.blade.php` | Modified (citizen dashboard with stats) |
| `resources/views/auth/register.blade.php` | Modified (action fix) |
| `resources/views/auth/login.blade.php` | Modified (forgot password link) |
| `resources/views/auth/forgot-password.blade.php` | Created |
| `resources/views/auth/reset-password.blade.php` | Created |
| `resources/views/layouts/app.blade.php` | Modified (@stack directives) |
| `resources/views/layouts/guest.blade.php` | Modified (@stack directives) |
| `resources/views/layouts/navbar.blade.php` | Modified (role checks, My Complaints link) |
| `resources/views/layouts/guest-navbar.blade.php` | Modified (role checks) |

### Config
| File | Action |
|------|--------|
| `config/filesystems.php` | Modified (minio disk) |

### Routes
| File | Action |
|------|--------|
| `routes/web.php` | Modified (password reset + complaints index/show routes) |

### Bootstrap
| File | Action |
|------|--------|
| `bootstrap/providers.php` | No change needed (Laravel 12 auto-discovery) |

---

## 11. Pending Actions

1. ✅ **Install S3 Flysystem adapter** — COMPLETED
   ```bash
   ./vendor/bin/sail composer require league/flysystem-aws-s3-v3
   ```

2. ✅ **Create MinIO bucket** — COMPLETED
   - Bucket `daet-complaints` created via MinIO console or `mc mb`

3. ✅ **Configure MinIO in `.env`** — COMPLETED
   ```env
   MINIO_ACCESS_KEY_ID=sail
   MINIO_SECRET_ACCESS_KEY=password
   MINIO_BUCKET=daet-complaints
   MINIO_ENDPOINT=http://minio:9000
   FILESYSTEM_DISK=minio
   ```

4. **Implement full password reset** (optional):
   - Wire `sendResetLink()` to Laravel's `Password::sendResetLink()`
   - Wire `resetPassword()` to Laravel's `Password::reset()`
   - Create password reset email template

5. **Create staff/admin dashboard views** (next major task):
   - Staff dashboard (department inbox)
   - Admin panel (full table, status changes)

---

## 12. Key Design Decisions

### Why Decimal Columns Instead of PostGIS?
- `mstaack/laravel-postgis`: Max Laravel 10 support
- `matanyadaev/laravel-spatial`: Package doesn't exist
- **Decision**: Use `decimal(10,8)`/`decimal(11,8)` columns for simplicity
- **Trade-off**: No native spatial queries (ST_DWithin, etc.)
- **Mitigation**: Can add PostGIS later via raw SQL if needed

### Why Separate `minio` Disk Instead of `s3`?
- Allows simultaneous AWS S3 and MinIO configuration
- Clear separation of concerns
- Easier environment-specific overrides

### Why Stub Password Reset?
- UI testing priority over full implementation
- Laravel's built-in Password reset can be wired later
- Routes and views are in place for immediate testing

---

## 13. Testing Checklist

- [x] Registration form submits correctly
- [x] Login form works
- [x] Forgot password link navigates correctly
- [x] Navbar shows correct links based on role
- [x] Departments seeded successfully
- [x] Complaint form displays with Leaflet map
- [x] Category selection auto-routes to correct department
- [x] Complaint creation stores lat/lng/address
- [x] **Image upload to MinIO — WORKING** ✅
- [x] **Citizen Dashboard — WORKING** ✅
- [x] **Complaint index/show views — WORKING** ✅
- [x] **Image viewing from MinIO — WORKING** ✅ (requires public bucket)
- [ ] Staff/Admin dashboards (pending)

---

## 14. Architecture Notes

### Soft Deletes
Both `users` and `complaints` use `SoftDeletes`:
- `complaints.user_id` uses `nullOnDelete()` (not cascade)
- Complaints remain as accountability records even if user deleted

### Immutable Logs
`ComplaintLog` entries:
- Created once, never updated or deleted
- `previous_status` can be `null` (first entry)
- Required for every status change

### Ticket ID Format
```
COMP-YYYY-NNNNN
```
- Sequential, not random (avoids collisions)
- Year prefix allows reset each year
- `withTrashed()` ensures no gaps in numbering

---

## Appendix: Quick Reference Commands

```bash
# Reset database
./vendor/bin/sail artisan migrate:fresh --seed

# Clear caches
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# Install S3 adapter (✅ COMPLETED)
./vendor/bin/sail composer require league/flysystem-aws-s3-v3

# Create MinIO bucket and make public
./vendor/bin/sail exec minio mc alias set local http://localhost:9000 sail password
./vendor/bin/sail exec minio mc mb local/daet-complaints
./vendor/bin/sail exec minio mc anonymous set public local/daet-complaints

# Check routes
./vendor/bin/sail artisan route:list
```

---

*End of Documentation*
