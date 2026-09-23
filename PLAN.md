# 📋 Daet Complaint System — Project Plan

> **App:** Laravel 12 · PostgreSQL · Livewire v4 · Tailwind CSS  
> **Status:** Auth & UI scaffolding complete · Core complaint system to be built  
> **Last Updated:** 2026-09-23  
> **Architecture:** Fixed 4-Role System (Admin · Approver · Worker · Resident)

---

## 🎯 Project Vision

Build a **multi-role LGU Complaint Portal** where:
- **Residents** file complaints
- **Approvers** verify complaints are legitimate
- **Workers** handle the actual work and log progress
- **Admins** manage departments, generate invitation codes, and see all activity logs
- **Every action is logged** as an audit trail (footprint)
- **Auto-close** complaints after 7 days of inactivity

---

## 🔐 Role Definitions (FIXED — Not Configurable)

| Role | Can Do | Cannot Do |
|---|---|---|
| **`admin`** | Everything. Create departments. Generate invitation codes. View all logs. Manage all complaints/users. | — |
| **`approver`** | Review & approve/reject complaints assigned to their department. | Create departments, generate codes, view other departments' complaints. |
| **`worker`** | Log progress updates on assigned complaints. Mark as done. | Approve complaints (different role), create departments, generate codes. |
| **`resident`** | File complaints. Edit their own complaints while in early stages. Close complaint if worker marks done. | Access admin panel, approve complaints, view other residents' complaints. |

> **Key principle:** Roles are hardcoded constants. No admin can add, remove, or modify roles. This keeps the system simple, secure, and predictable.

---

## 📊 Current State vs. Target State

| Feature | Current | Target |
|---|---|---|
| User roles | `is_admin` boolean only | `role` enum: `admin, approver, worker, resident` |
| Departments | None | Full department model + management |
| Complaint workflow | None | Filed → Approved → In Progress → Done → Closed |
| Activity logs | None | Every action recorded with user, action, target, IP, timestamp |
| Invitation codes | None | Admin generates 10-min expiry, single-use codes per department |
| Auto-close | None | 7-day inactivity auto-closes complaints |
| Admin panel | Missing | Full admin dashboard with logs |

---

## 🗄️ Database Schema

### Users Table (MODIFIED)
```sql
-- Replace `is_admin` boolean with `role` enum
ALTER TABLE users DROP COLUMN is_admin;
ALTER TABLE users ADD COLUMN role ENUM('admin','approver','worker','resident') DEFAULT 'resident';
ALTER TABLE users ADD COLUMN department_id INT NULL AFTER role;
ALTER TABLE users ADD COLUMN created_by INT NULL; -- For invitation-based accounts
```

### Departments Table (NEW)
```sql
CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    email VARCHAR(255) NULL,
    contact_number VARCHAR(15) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT UNSIGNED NULL, -- FK to users (admin who created it)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Complaint Statuses Table (NEW)
```sql
CREATE TABLE complaint_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,     -- 'filed', 'approved', 'rejected', 'in_progress', 'done', 'closed', 'auto_closed'
    label VARCHAR(50) NOT NULL,            -- 'Filed', 'Approved', 'Rejected', 'In Progress', 'Done', 'Closed', 'Auto-Closed'
    color VARCHAR(20) NOT NULL,            -- Tailwind color class
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```
Seed data: `filed`, `approved`, `rejected`, `in_progress`, `done`, `closed`, `auto_closed`

### Complaints Table (NEW)
```sql
CREATE TABLE complaints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_number VARCHAR(50) NOT NULL UNIQUE,  -- CM-YYYYMMDD-XXXX
    user_id INT UNSIGNED NOT NULL,                  -- FK (resident who filed)
    department_id INT UNSIGNED NOT NULL,            -- FK (target department)
    category VARCHAR(50) NOT NULL,                  -- 'Infrastructure', 'Sanitation', 'Public Safety', etc.
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    status ENUM('filed','approved','rejected','in_progress','done','closed','auto_closed') DEFAULT 'filed',
    -- Workflow fields
    approved_by INT UNSIGNED NULL,                  -- FK (approver who approved/rejected)
    approved_at TIMESTAMP NULL,
    assigned_worker_id INT UNSIGNED NULL,           -- FK (worker assigned to do the work)
    assigned_at TIMESTAMP NULL,
    completed_by INT UNSIGNED NULL,                 -- FK (worker who marked done)
    completed_at TIMESTAMP NULL,
    closed_by INT UNSIGNED NULL,                    -- FK (user who closed it)
    closed_at TIMESTAMP NULL,
    -- Auto-close tracking
    due_at DATETIME NULL,                           -- created_at + 7 days
    closed_reason VARCHAR(255) NULL,                -- 'resolved', 'auto_close', 'user_cancelled'
    -- Attachment
    attachment_path VARCHAR(500) NULL,
    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX idx_complaints_status ON complaints(status);
CREATE INDEX idx_complaints_department ON complaints(department_id);
CREATE INDEX idx_complaints_due ON complaints(due_at);
CREATE INDEX idx_complaints_user ON complaints(user_id);
```

### Complaint Updates Table (NEW — Activity Footprint)
```sql
CREATE TABLE complaint_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,             -- FK
    user_id INT UNSIGNED NOT NULL,                  -- FK (who performed this action)
    user_role VARCHAR(20) NOT NULL,                 -- 'admin', 'approver', 'worker', 'resident'
    action VARCHAR(100) NOT NULL,                   -- e.g. 'complaint.filed', 'complaint.approved', 'complaint.status_changed'
    message TEXT NULL,                              -- Optional note/comment
    target_type VARCHAR(100) NULL,                  -- e.g. 'status', 'assignment', 'comment'
    target_id INT UNSIGNED NULL,                    -- FK to related record
    ip_address VARCHAR(45) NULL,                    -- For audit
    created_at TIMESTAMP NULL,
    INDEX idx_updates_complaint (complaint_id),
    INDEX idx_updates_user (user_id)
);
```

### Invitation Codes Table (NEW)
```sql
CREATE TABLE invitation_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,               -- Random 32-char string
    department_id INT UNSIGNED NOT NULL,            -- FK
    created_by INT UNSIGNED NOT NULL,               -- FK (admin who generated it)
    expires_at DATETIME NOT NULL,                   -- NOW() + 10 minutes
    max_uses INT DEFAULT 1,                          -- Single-use
    used_count INT DEFAULT 0,
    redeemed_by INT UNSIGNED NULL,                  -- FK (who used it)
    redeemed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_invitations_expires (expires_at),
    INDEX idx_invitations_active (is_active)
);
```

---

## 🔄 Complaint Lifecycle (The Core Workflow)

```
┌─────────────┐
│   FILED      │  ← Resident submits complaint
└──────┬──────┘
       │
       ▼  [Approver reviews]
┌─────────────┐     REJECTED     ┌─────────────┐
│  APPROVED    │ ──────────────→  │  REJECTED   │  ← Approver says "not legitimate"
│  (assigned   │                  │  (no work   │      Resident sees rejection reason
│   to worker) │                  │  happens)   │      and can file new complaint
└──────┬──────┘                  └─────────────┘
       │
       ▼  [Worker starts work]
┌─────────────┐
│ IN PROGRESS  │  ← Worker logs progress updates
└──────┬──────┘
       │
       ▼  [Worker marks done]
┌─────────────┐
│   DONE       │  ← Worker marks complete
└──────┬──────┘
       │
       ├──→ [Resident closes] ──→ CLOSED
       │
       └──→ [7 days pass, no action] ──→ AUTO-CLOSED
```

### Role Access to Complaint Actions:

| Action | Resident | Approver | Worker | Admin |
|---|---|---|---|---|
| File complaint | ✅ | ❌ | ❌ | ❌ |
| Edit own complaint | ✅ (Filed only) | ❌ | ❌ | ✅ |
| View own complaints | ✅ | ❌ | ❌ | ✅ |
| Review/Approve complaint | ❌ | ✅ (their dept) | ❌ | ✅ |
| Reject complaint | ❌ | ✅ (their dept) | ❌ | ✅ |
| Assign worker | ❌ | ❌ | ❌ | ✅ |
| Log progress | ❌ | ❌ | ✅ (assigned) | ✅ |
| Mark as done | ❌ | ❌ | ✅ (assigned) | ✅ |
| Close complaint | ✅ (if done) | ❌ | ❌ | ✅ |
| View logs | ❌ (own only) | ❌ (own dept) | ❌ (own dept) | ✅ (ALL) |
| Generate invite codes | ❌ | ❌ | ❌ | ✅ |
| Create departments | ❌ | ❌ | ❌ | ✅ |
| View all complaints | ❌ | ❌ (own dept) | ❌ (own dept) | ✅ |

---

## 📦 Phase-by-Phase Plan

---

### 📦 Phase 1 — Foundation: Roles, Departments, Models (3-4 days)

**Goal:** Replace `is_admin` with role enum, create all new models and migrations.

#### 1.1 Migration Files (NEW)
- `2026_09_23_000001_replace_is_admin_with_role.php` — ALTER users table
- `2026_09_23_000002_create_departments_table.php`
- `2026_09_23_000003_create_complaint_statuses_table.php`
- `2026_09_23_000004_create_complaints_table.php`
- `2026_09_23_000005_create_complaint_updates_table.php`
- `2026_09_23_000006_create_invitation_codes_table.php`

#### 1.2 Models (NEW/MODIFIED)

**`User.php`** (MODIFIED):
```php
// Role constants
const ROLE_ADMIN = 'admin';
const ROLE_APPROVER = 'approver';
const ROLE_WORKER = 'worker';
const ROLE_RESIDENT = 'resident';
const ROLES = [self::ROLE_ADMIN, self::ROLE_APPROVER, self::ROLE_WORKER, self::ROLE_RESIDENT];

// Relationships
public function complaints() { return $this->hasMany(Complaint::class); }
public function complaintsAsApprover() { return $this->hasMany(Complaint::class, 'approved_by'); }
public function complaintsAsWorker() { return $this->hasMany(Complaint::class, 'assigned_worker_id'); }
public function department() { return $this->belongsTo(Department::class); }
public function departmentComplaints() { return $this->hasManyThrough(Complaint::class, Department::class); }
public function invitationsCreated() { return $this->hasMany(InvitationCode::class, 'created_by'); }
```

**`Department.php`** (NEW):
```php
class Department extends Model {
    protected $fillable = ['name', 'slug', 'description', 'email', 'contact_number', 'is_active'];
    public function complaints() { return $this->hasMany(Complaint::class); }
    public function workers() { return $this->hasMany(User::class)->where('role', 'worker'); }
    public function approvers() { return $this->hasMany(User::class)->where('role', 'approver'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function invitationCodes() { return $this->hasMany(InvitationCode::class); }
}
```

**`Complaint.php`** (NEW):
```php
class Complaint extends Model {
    protected $fillable = [...];
    protected $casts = ['due_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function statusRecord() { return $this->belongsTo(ComplaintStatus::class, 'status'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function assignedWorker() { return $this->belongsTo(User::class, 'assigned_worker_id'); }
    public function updates() { return $this->hasMany(ComplaintUpdate::class); }
}
```

**`ComplaintStatus.php`** (NEW):
```php
class ComplaintStatus extends Model {
    protected $fillable = ['name', 'label', 'color', 'description', 'sort_order'];
    public function complaints() { return $this->hasMany(Complaint::class, 'status'); }
}
```

**`ComplaintUpdate.php`** (NEW):
```php
class ComplaintUpdate extends Model {
    protected $fillable = ['complaint_id', 'user_id', 'user_role', 'action', 'message', 'target_type', 'target_id', 'ip_address'];
    public function complaint() { return $this->belongsTo(Complaint::class); }
    public function user() { return $this->belongsTo(User::class); }
}
```

**`InvitationCode.php`** (NEW):
```php
class InvitationCode extends Model {
    protected $fillable = ['code', 'department_id', 'created_by', 'expires_at', 'max_uses', 'used_count', 'redeemed_by'];
    protected $casts = ['expires_at' => 'datetime', 'created_at' => 'datetime'];

    public function department() { return $this->belongsTo(Department::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function redeemedBy() { return $this->belongsTo(User::class, 'redeemed_by'); }

    // Helper methods
    public function isExpired() { return $this->expires_at->isPast(); }
    public function isUsed() { return $this->used_count >= $this->max_uses; }
    public function isActive() { return !$this->isExpired() && !$this->isUsed() && $this->is_active; }
}
```

#### 1.3 Seeders (NEW)

**`DepartmentSeeder.php`** — Seed default Daet LGU departments:
```php
$departments = [
    ['name' => 'Public Information Office', 'slug' => 'pio', 'email' => 'pio@daet.gov.ph'],
    ['name' => 'Engineering Office', 'slug' => 'engineering', 'email' => 'eng@daet.gov.ph'],
    ['name' => 'Health Office', 'slug' => 'health', 'email' => 'health@daet.gov.ph'],
    ['name' => 'Social Services Office', 'slug' => 'social-services', 'email' => 'social@daet.gov.ph'],
    ['name' => 'Environmental Office', 'slug' => 'environmental', 'email' => 'env@daet.gov.ph'],
    ['name' => 'Public Works Office', 'slug' => 'public-works', 'email' => 'pw@daet.gov.ph'],
    ['name' => 'Treasury Office', 'slug' => 'treasury', 'email' => 'treasury@daet.gov.ph'],
    ['name' => 'Legal Office', 'slug' => 'legal', 'email' => 'legal@daet.gov.ph'],
];
```

**`ComplaintStatusSeeder.php`** — Seed statuses: filed, approved, rejected, in_progress, done, closed, auto_closed

**`UserSeeder.php`** (NEW/REPLACE):
```php
// Create admin user (from .env or hardcoded)
// Create sample approver user (for Engineering department)
// Create sample worker user (for Engineering department)
// Create sample resident user
// ALL assigned to appropriate departments
```

**`DatabaseSeeder.php`** (UPDATED):
```php
$this->call([DepartmentSeeder::class, ComplaintStatusSeeder::class, UserSeeder::class]);
```

#### 1.4 Factories (NEW)
- `DepartmentFactory.php`
- `ComplaintFactory.php`
- `InvitationCodeFactory.php`

---

### 📦 Phase 2 — Invitation System (2-3 days)

**Goal:** Admin can generate time-limited invitation codes for departments. Workers/approvers redeem codes to get accounts.

#### 2.1 Controllers

**`InvitationController`**:
```php
class InvitationController extends Controller {
    public function index() { /* List all active codes */ }
    public function store() { /* Generate new code (admin only) */ }
    public function redeem($code) { /* Redeem code → create user account */ }
    public function destroy($id) { /* Delete unused code (admin only) */ }
}
```

#### 2.2 Key Logic

**Generating a code:**
```php
public function store(Request $request) {
    $request->validate(['department_id' => 'required|exists:departments,id']);
    
    $code = Str::random(32);
    $expiresAt = now()->addMinutes(10);
    
    InvitationCode::create([
        'code' => $code,
        'department_id' => $request->department_id,
        'created_by' => auth()->id(),
        'expires_at' => $expiresAt,
        'max_uses' => 1,
        'used_count' => 0,
    ]);
    
    // Log the action
    activity_log(auth()->user(), 'invitation.generated', ['code' => $code]);
}
```

**Redeeming a code:**
```php
public function redeem(Request $request, $code) {
    $invitation = InvitationCode::where('code', $code)->firstOrFail();
    
    // Validate
    if ($invitation->isExpired()) { abort(403, 'Code expired'); }
    if ($invitation->isUsed()) { abort(403, 'Code already used'); }
    
    // Create user account (worker role, assigned to department)
    $user = User::create([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => User::ROLE_WORKER,  // Default role for invitation redemption
        'department_id' => $invitation->department_id,
        'created_by' => auth()->id(), // Admin who generated the code
    ]);
    
    // Mark code as used
    $invitation->update([
        'used_count' => $invitation->used_count + 1,
        'redeemed_by' => $user->id,
        'redeemed_at' => now(),
    ]);
    
    // Log the action
    activity_log(auth()->user(), 'invitation.redeemed', ['code' => $code, 'user' => $user->id]);
    
    // Login automatically or redirect to login
}
```

#### 2.3 Routes
```php
Route::middleware('auth')->prefix('invitations')->name('invitations.')->group(function () {
    Route::get('/', [InvitationController::class, 'index'])->name('index');
    Route::post('/', [InvitationController::class, 'store'])->name('store');
    Route::delete('/{id}', [InvitationController::class, 'destroy'])->name('destroy');
});
Route::get('/invite/{code}', [InvitationController::class, 'redeemForm'])->name('redeem.form');
Route::post('/invite/{code}/redeem', [InvitationController::class, 'redeem'])->name('redeem');
```

#### 2.4 Views
- `pages.invitations.index` — Admin view: list all codes, generate new ones (shows: code, department, expires in, used status)
- `pages.invitations.redeem` — Public form: name, email, password fields (after validating code)

---

### 📦 Phase 3 — Core Complaint System (4-5 days)

**Goal:** Full CRUD with role-based access control and the complete lifecycle workflow.

#### 3.1 Controllers

**`ComplaintController`**:
```php
class ComplaintController extends Controller {
    public function index() { /* Filter by role: residents see own, admin/sees all, approvers/workers see dept only */ }
    public function create() { /* Form with department/category/barangay dropdowns */ }
    public function store() { /* Validate, generate complaint number, set status='filed', log action */ }
    public function show($id) { /* Detail with timeline, action buttons based on role */ }
    public function edit($id) { /* Only resident in 'filed' status */ }
    public function update($id) { /* Only resident editing own filed complaint */ }
    public function destroy($id) { /* Only resident in early stages, or admin anytime */ }
}
```

**`ApprovalController`**:
```php
class ApprovalController extends Controller {
    public function index() { /* List complaints awaiting approval in approver's department */ }
    public function show($id) { /* Detail + approve/reject form */ }
    public function approve($id) { /* Set status='approved', approved_by=user, assigned_worker=auto-assign or admin */ }
    public function reject($id) { /* Set status='rejected', approved_by=user, notify resident */ }
}
```

**`WorkerController`**:
```php
class WorkerController extends Controller {
    public function index() { /* List complaints assigned to this worker */ }
    public function show($id) { /* Detail + progress log */ }
    public function logUpdate($id) { /* Add progress update */ }
    public function markDone($id) { /* Set status='done', completed_by=user */ }
}
```

#### 3.2 Helper: Activity Logging

Create a helper function to log every action:

```php
// app/Helpers/activity_log.php
function activity_log(User $user, string $action, ?array $data = null, ?Complaint $complaint = null) {
    ComplaintUpdate::create([
        'complaint_id' => $complaint?->id,
        'user_id' => $user->id,
        'user_role' => $user->role,
        'action' => $action,
        'message' => $data['message'] ?? null,
        'target_type' => $data['target_type'] ?? null,
        'target_id' => $data['target_id'] ?? null,
        'ip_address' => request()->ip(),
    ]);
}
```

#### 3.3 Complaint Number Generation

```php
// CM-YYYYMMDD-XXXX format
function generateComplaintNumber() {
    $today = now()->format('Ymd');
    $count = Complaint::whereDate('created_at', today())->count() + 1;
    return "CM-{$today}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}
```

#### 3.4 Auto-Close Logic

**Option A: Scheduled Command (Recommended)**

```php
// app/Console/Commands/AutoCloseComplaints.php
class AutoCloseComplaints extends Command {
    protected $signature = 'complaints:auto-close';
    protected $description = 'Auto-close complaints after 7 days of inactivity';

    public function handle() {
        $dueComplaints = Complaint::where('status', 'in_progress')
            ->where('due_at', '<=', now())
            ->orWhere(function($q) {
                $q->where('status', 'approved')
                  ->whereNull('assigned_worker_id')
                  ->where('created_at', '<=', now()->subDays(7));
            })
            ->get();

        foreach ($dueComplaints as $complaint) {
            $complaint->update([
                'status' => 'auto_closed',
                'closed_reason' => 'auto_close_7_days',
                'closed_at' => now(),
            ]);
            activity_log(
                User::find($complaint->user_id), // System action logged under resident
                'complaint.auto_closed',
                ['reason' => '7 days inactivity'],
                $complaint
            );
        }

        $this->info('Auto-closed ' . count($dueComplaints) . ' complaints.');
    }
}
```

**Option B: Livewire Polling (Real-time in UI)**

```php
// In dashboard.blade.php - check every 30 seconds
public function checkAutoClose() {
    // Check if any complaint needs auto-close
}
```

#### 3.5 Routes

```php
// Resident complaint routes
Route::middleware(['auth', 'role:resident'])->prefix('complaints')->name('complaints.')->group(function () {
    Route::get('/', [ComplaintController::class, 'index'])->name('index');
    Route::get('/create', [ComplaintController::class, 'create'])->name('create');
    Route::post('/', [ComplaintController::class, 'store'])->name('store');
    Route::get('/{complaint}', [ComplaintController::class, 'show'])->name('show');
    Route::get('/{complaint}/edit', [ComplaintController::class, 'edit'])->name('edit');
    Route::put('/{complaint}', [ComplaintController::class, 'update'])->name('update');
    Route::delete('/{complaint}', [ComplaintController::class, 'destroy'])->name('destroy');
});

// Approver routes
Route::middleware(['auth', 'role:approver'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/', [ApprovalController::class, 'index'])->name('index');
    Route::get('/{complaint}', [ApprovalController::class, 'show'])->name('show');
    Route::post('/{complaint}/approve', [ApprovalController::class, 'approve'])->name('approve');
    Route::post('/{complaint}/reject', [ApprovalController::class, 'reject'])->name('reject');
});

// Worker routes
Route::middleware(['auth', 'role:worker'])->prefix('work')->name('work.')->group(function () {
    Route::get('/', [WorkerController::class, 'index'])->name('index');
    Route::get('/{complaint}', [WorkerController::class, 'show'])->name('show');
    Route::post('/{complaint}/updates', [WorkerController::class, 'logUpdate'])->name('updates');
    Route::post('/{complaint}/done', [WorkerController::class, 'markDone'])->name('done');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('complaints.show');
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/complaint/{complaint}', [AdminLogController::class, 'show'])->name('logs.complaint');
    Route::get('/departments', [AdminDepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [AdminDepartmentController::class, 'store'])->name('departments.store');
    Route::delete('/departments/{id}', [AdminDepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::get('/invitations', [AdminInvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [AdminInvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{id}', [AdminInvitationController::class, 'destroy'])->name('invitations.destroy');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
});
```

#### 3.6 Views (NEW)

| View | Layout | Description |
|---|---|---|
| `pages.complaints.index` | `layouts.app` | List complaints with status filters, action buttons per role |
| `pages.complaints.create` | `layouts.app` | Form: category, title, description, barangay, department, priority |
| `pages.complaints.show` | `layouts.app` | Detail + activity timeline + action buttons |
| `pages.complaints.edit` | `layouts.app` | Edit form (filed status only) |
| `pages.approvals.index` | `layouts.app` | List complaints awaiting approval |
| `pages.approvals.show` | `layouts.app` | Detail + approve/reject form |
| `pages.work.index` | `layouts.app` | List assigned complaints |
| `pages.work.show` | `layouts.app` | Detail + progress log + mark done |
| `pages.admin.dashboard` | `layouts.admin` | Stats overview, all complaints |
| `pages.admin.logs.index` | `layouts.admin` | Full activity log search |
| `pages.admin.logs.complaint` | `layouts.admin` | Footprint of a single complaint |
| `pages.admin.invitations.index` | `layouts.admin` | Generate/view codes |
| `pages.admin.departments.index` | `layouts.admin` | Create/manage departments |
| `pages.admin.users.index` | `layouts.admin` | View all users, role assignments |
| `pages.invitations.redeem` | `layouts.guest` | Redeem invitation code form |

---

### 📦 Phase 4 — Admin Dashboard & Logs (3-4 days)

**Goal:** Admin sees everything — all complaints, all logs, all departments.

#### 4.1 Admin Dashboard Features

- **Summary Cards:** Total complaints, today's filings, approved/rejected ratio, pending approvals, auto-closed count
- **Complaint Table:** All complaints with filters (status, department, barangay, category, date range, priority)
- **Quick Actions:** Assign worker, change status, view logs
- **Department List:** Add new departments (for invitation codes)

#### 4.2 Activity Log / Footprint

Every action in the system creates a `ComplaintUpdate` record:

```
┌─────────────────────────────────────────────────────────┐
│  FOOTPRINT VIEW (Admin can see ALL)                     │
├─────────┬─────────┬──────────────┬──────────┬───────────┤
│  User   │  Role   │   Action     │  Target  │ Timestamp │
├─────────┼─────────┼──────────────┼──────────┼───────────┤
│ Juan    │ Resident│ Filed CM-... │ Complaint│ 09/23...  │
│ Maria   │ Approver│ Approved     │ Complaint│ 09/24...  │
│ Pedro   │ Worker  │ Log update   │ Complaint│ 09/25...  │
│ Pedro   │ Worker  │ Marked done  │ Complaint│ 09/26...  │
│ Juan    │ Resident│ Closed       │ Complaint│ 09/26...  │
└─────────┴─────────┴──────────────┴──────────┴───────────┘
```

**`AdminLogController`**:
```php
public function index() {
    // Search by user, action, complaint number, date range
    $logs = ComplaintUpdate::with('user', 'complaint')
        ->orderBy('created_at', 'desc')
        ->paginate(50);
    return view('pages.admin.logs.index', compact('logs'));
}

public function show(Complaint $complaint) {
    // Full footprint of a single complaint
    $footprint = $complaint->updates()
        ->with('user')
        ->orderBy('created_at', 'asc')
        ->get();
    return view('pages.admin.logs.complaint', compact('footprint', 'complaint'));
}
```

---

### 📦 Phase 5 — Notifications & Alerts (1-2 days)

**Goal:** Notify relevant parties on key events.

| Event | Notification Target | Method |
|---|---|---|
| Complaint filed | Admin + Department approver | Database notification |
| Complaint approved | Resident + Assigned worker | Database notification |
| Complaint rejected | Resident | Database notification |
| Worker logs progress | Resident + Admin | Database notification |
| Worker marks done | Resident | Database notification |
| Complaint auto-closed | Resident | Database notification |
| Invitation code generated | (Admin sees in dashboard) | — |
| Invitation code redeemed | Admin (department notification) | Database notification |

```php
// Use Laravel's built-in Notification system
class ComplaintStatusNotification extends Notification {
    public function toDatabase($notifiable) {
        return [
            'title' => 'Complaint Update',
            'message' => "Your complaint has been {$this->status}.",
            'action_url' => route('complaints.show', $this->complaint->id),
        ];
    }
}
```

---

### 📦 Phase 6 — Polish & Testing (2-3 days)

#### 6.1 Testing
- Unit tests for role validation logic
- Feature tests for complaint lifecycle (file → approve → work → done → close)
- Feature tests for invitation code (generate → redeem → expiry)
- Feature tests for auto-close command
- Feature tests for role-based access middleware

#### 6.2 Security
- Audit all `activity_log` entries are written
- Ensure workers can only update complaints assigned to them
- Ensure approvers can only approve complaints in their department
- Validate that invitation codes expire correctly
- Rate limiting on complaint creation (already in `AuthController`)

#### 6.3 Performance
- Indexes on all foreign keys and status columns
- Eager loading to prevent N+1 queries
- Cache department lists and status lists

---

## 📁 Final File Structure

```
app/
├── Models/
│   ├── User.php                          (MODIFIED — role enum, relationships)
│   ├── Department.php                    (NEW)
│   ├── Complaint.php                     (NEW)
│   ├── ComplaintStatus.php               (NEW)
│   ├── ComplaintUpdate.php               (NEW — activity log)
│   └── InvitationCode.php                (NEW)
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php            (existing)
│   │   ├── HomeController.php            (existing)
│   │   ├── PageController.php            (existing)
│   │   ├── ComplaintController.php       (NEW)
│   │   ├── ApprovalController.php        (NEW)
│   │   ├── WorkerController.php          (NEW)
│   │   ├── AdminController.php           (NEW)
│   │   ├── AdminComplaintController.php  (NEW)
│   │   ├── AdminDepartmentController.php (NEW)
│   │   ├── AdminInvitationController.php (NEW)
│   │   ├── AdminLogController.php        (NEW)
│   │   ├── AdminUserController.php       (NEW)
│   │   └── InvitationController.php      (NEW)
│   └── Middleware/
│       ├── AdminMiddleware.php            (UPDATED — role-based)
│       └── RoleMiddleware.php             (NEW)
└── Console/
    └── Commands/
        └── AutoCloseComplaints.php        (NEW)

database/
├── migrations/
│   ├── 0001_01_01_000001_create_users_table.php  (MODIFIED — add role, department_id)
│   ├── 2026_03_07_035114_add_is_admin_to_users_table.php (REPLACED by role migration)
│   ├── 2026_09_23_000002_create_departments_table.php       (NEW)
│   ├── 2026_09_23_000003_create_complaint_statuses_table.php (NEW)
│   ├── 2026_09_23_000004_create_complaints_table.php         (NEW)
│   ├── 2026_09_23_000005_create_complaint_updates_table.php  (NEW)
│   └── 2026_09_23_000006_create_invitation_codes_table.php   (NEW)
├── seeders/
│   ├── DepartmentSeeder.php           (NEW)
│   ├── ComplaintStatusSeeder.php      (NEW)
│   ├── UserSeeder.php                 (NEW — admin + sample users)
│   └── DatabaseSeeder.php             (UPDATED)
└── factories/
    ├── DepartmentFactory.php          (NEW)
    ├── ComplaintFactory.php           (NEW)
    └── InvitationCodeFactory.php      (NEW)

resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php               (existing)
│   │   ├── guest.blade.php             (existing)
│   │   ├── navbar.blade.php            (existing)
│   │   ├── guest-navbar.blade.php      (existing)
│   │   └── admin.blade.php             (NEW)
│   ├── auth/                          (existing)
│   ├── components/
│   │   ├── ComplaintCard.php           (NEW)
│   │   ├── StatusBadge.php             (NEW)
│   │   ├── ComplaintTimeline.php       (NEW)
│   │   └── ComplaintForm.php           (NEW)
│   └── pages/
│       ├── home/                       (existing)
│       ├── complaints/                 (NEW)
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── show.blade.php
│       │   └── edit.blade.php
│       ├── approvals/                  (NEW)
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── work/                       (NEW)
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── admin/                      (NEW)
│       │   ├── dashboard.blade.php
│       │   ├── complaints.blade.php
│       │   ├── departments.blade.php
│       │   ├── invitations.blade.php
│       │   ├── logs.blade.php
│       │   └── users.blade.php
│       └── invitations/                (NEW)
│           └── redeem.blade.php
└── Livewire/                           (optional)
    ├── ComplaintList.php
    ├── ComplaintTimeline.php
    ├── StatusTracker.php
    └── ComplaintForm.php
```

---

## ⏱️ Updated Timeline

| Phase | Features | Est. Days |
|---|---|---|
| **Phase 1** | Roles, Departments, Models, Migrations, Seeders | 3-4 days |
| **Phase 2** | Invitation System (generate codes, redeem, expiry) | 2-3 days |
| **Phase 3** | Core Complaint System (CRUD + lifecycle + auto-close) | 4-5 days |
| **Phase 4** | Admin Dashboard + Activity Logs | 3-4 days |
| **Phase 5** | Notifications & Alerts | 1-2 days |
| **Phase 6** | Testing + Polish | 2-3 days |
| **Total** | | **~15-21 days** |

---

## ✅ Recommended Build Order

### Week 1: Foundation
1. ✅ Migration: Replace `is_admin` with `role` enum
2. ✅ Create all models (Department, Complaint, ComplaintStatus, ComplaintUpdate, InvitationCode)
3. ✅ Create seeders + seed departments, statuses, admin user
4. ✅ Update User.php relationships
5. ✅ Update middleware to use roles instead of `is_admin`

### Week 2: Core Features
6. ✅ Invitation system (generate + redeem + expiry check)
7. ✅ Complaint CRUD (resident workflow)
8. ✅ Approval workflow (approver)
9. ✅ Worker progress logging

### Week 3: Admin + Polish
10. ✅ Admin dashboard (all complaints, departments, users)
11. ✅ Activity logs (full footprint)
12. ✅ Auto-close command (7-day scheduler)
13. ✅ Notifications
14. ✅ Testing + deployment

---

## 🔑 Key Implementation Notes

- **Role constants** go in `User.php` — never create new roles dynamically
- **Middleware** needs a `RoleMiddleware` that checks role in routes
- **Activity logs** are created on EVERY state change — this is the "footprint"
- **Invitation codes** are 32-char random strings, expire in 10 minutes, single-use
- **Auto-close** uses Laravel's `Schedule` (runs via cron: `php artisan schedule:run`)
- **Dashboard layout** (`layouts/admin.blade.php`) must be created — it doesn't exist yet
- **`$mainLayout`** from `AppServiceProvider` still controls which layout extends — handle carefully
- **Barangay list** should be moved to a single source (model constant) to avoid duplication
- **`wire:navigate`** is used everywhere — keep this consistent for new pages
- **Color scheme** remains Navy `#0B1F3A` + Gold `#C9A84C`
