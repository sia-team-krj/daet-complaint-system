<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransparencyController;
use App\Http\Controllers\RewardsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminInvitationController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\InvitationRedemptionController;
use App\Http\Controllers\StaffController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES — no login required
|--------------------------------------------------------------------------
*/

Route::get("/", [HomeController::class, "index"])->name("home");
Route::get("/transparency", [TransparencyController::class, "index"])->name(
    "transparency",
);
Route::get("/rewards", [RewardsController::class, "index"])->name("rewards");

/*
|--------------------------------------------------------------------------
| AUTH ROUTES — login, register, logout
|--------------------------------------------------------------------------
*/

// Register
Route::get("/register", [AuthController::class, "showRegister"])
    ->middleware("guest")
    ->name("register");

Route::post("/register", [AuthController::class, "register"])->middleware(
    "guest",
);

// Login
Route::get("/login", [AuthController::class, "showLogin"])
    ->middleware("guest")
    ->name("login");

Route::post("/login", [AuthController::class, "login"])->middleware("guest");

// Logout
Route::post("/logout", [AuthController::class, "logout"])
    ->middleware("auth")
    ->name("logout");

// ── PASSWORD RESET ───────────────────────
Route::middleware("guest")->group(function () {
    Route::get("/forgot-password", [AuthController::class, "showResetPassword"])->name("password.request");
    Route::post("/forgot-password", [AuthController::class, "sendResetLink"])->name("password.email");
    Route::get("/reset-password/{token}", [AuthController::class, "showResetPasswordForm"])->name("password.reset");
    Route::post("/reset-password", [AuthController::class, "resetPassword"])->name("password.update");
});

Route::middleware("guest")->group(function () {
    Route::get("/invitations/{code}", [InvitationRedemptionController::class, "show"])
        ->name("invitations.show");
    Route::post("/invitations/{code}", [InvitationRedemptionController::class, "redeem"])
        ->name("invitations.redeem");
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES — must be logged in + email verified
|--------------------------------------------------------------------------
*/

Route::middleware(["auth"])->group(function () {
    Route::get("/dashboard", [DashboardController::class, "index"])->name(
        "dashboard",
    );
    Route::get("/profile", [ProfileController::class, "index"])->name(
        "profile",
    );
    Route::post("/profile", [ProfileController::class, "update"])->name(
        "profile.update",
    );
    Route::post("/profile/password", [ProfileController::class, "updatePassword"])->name(
        "profile.password",
    );

    // ── COMPLAINTS ──────────────────────────
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::get('/complaints/{complaint}/success', [ComplaintController::class, 'success'])->name('complaints.success');
    Route::get('/track', [ComplaintController::class, 'track'])->name('complaints.track');
});

/*
|--------------------------------------------------------------------------
| STAFF ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(["auth", "role:staff,admin"])
    ->prefix("staff")
    ->group(function () {
        Route::get("/dashboard", [StaffController::class, "dashboard"])->name("staff.dashboard");
        Route::get("/activity", [StaffController::class, "activityIndex"])->name("staff.activity.index");
        Route::get("/complaints", [StaffController::class, "complaintsIndex"])->name("staff.complaints.index");
        Route::get("/complaints/{complaint}", [StaffController::class, "show"])->name("staff.complaints.show");
        Route::put("/complaints/{complaint}/review", [StaffController::class, "review"])->name("staff.complaints.review");
        Route::put("/complaints/{complaint}", [StaffController::class, "update"])->name("staff.complaints.update");
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(["auth", "role:admin"])
    ->prefix("admin")
    ->group(function () {
        Route::get("/dashboard", [
            AdminController::class,
            "dashboard",
        ])->name("admin.dashboard");
        Route::get("/complaints", [AdminController::class, "complaintsIndex"])->name("admin.complaints.index");
        Route::get("/complaints/export", [AdminController::class, "exportComplaints"])->name("admin.complaints.export");
        Route::post("/complaints/bulk-reassign", [AdminController::class, "bulkReassign"])->name("admin.complaints.bulk-reassign");
        Route::get("/complaints/{complaint}", [AdminController::class, "complaintShow"])->name("admin.complaints.show");
        Route::put("/complaints/{complaint}", [AdminController::class, "complaintUpdate"])->name("admin.complaints.update");
        Route::get("/departments", [AdminController::class, "departmentsIndex"])->name("admin.departments.index");
        Route::post("/departments", [AdminController::class, "departmentStore"])->name("admin.departments.store");
        Route::get("/staff", [AdminController::class, "staffIndex"])->name("admin.staff.index");
        Route::get("/staff/create", [AdminController::class, "staffCreate"])->name("admin.staff.create");
        Route::post("/staff", [AdminController::class, "staffStore"])->name("admin.staff.store");
        Route::put("/staff/{user}/department", [AdminController::class, "staffUpdateDepartment"])->name("admin.staff.update-department");
        Route::post("/staff/{user}/toggle", [AdminController::class, "staffToggleStatus"])->name("admin.staff.toggle");
        Route::get("/invitations", [AdminInvitationController::class, "index"])->name("admin.invitations.index");
        Route::post("/invitations", [AdminInvitationController::class, "store"])->name("admin.invitations.store");
        Route::patch("/invitations/{invitation}/revoke", [AdminInvitationController::class, "revoke"])->name("admin.invitations.revoke");
        Route::get("/audit-trail", [AdminController::class, "auditIndex"])->name("admin.audit.index");
    });
