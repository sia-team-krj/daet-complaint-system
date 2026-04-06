<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ComplaintController;

use App\Http\Controllers\StaffController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\StaffMiddleware;
use App\Http\Middleware\AdminMiddleware;

// ── Public ─────────────────────────────
Route::get("/", [HomeController::class, "home"])->name("home");

Route::view("/transparency", "pages.transparency.index")->name("transparency");
Route::view("/rewards", "pages.rewards.index")->name("rewards");

// ── Guest only ─────────────────────────
Route::middleware("guest")->group(function () {
    Route::get("/login", [AuthController::class, "showLogin"])->name("login");
    Route::post("/login", [AuthController::class, "login"])->name("login.submit");

    Route::get("/register", [AuthController::class, "showRegister"])->name("register");
    Route::post("/register", [AuthController::class, "register"])->name("register.submit");

    // Password reset routes
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->name('password.email');

    Route::get('/reset-password/{token}', function (string $token) {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email', '')
        ]);
    })->name('password.reset');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');
});

// ── Auth only ──────────────────────────
Route::middleware("auth")->group(function () {

    Route::post("/logout", [AuthController::class, "logout"])->name("logout");

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::get('/complaints/success/{complaint}', [ComplaintController::class, 'success'])->name('complaints.success')->can('view', 'complaint');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show')->can('view', 'complaint');
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ── Admin ──────────────────────────────
Route::middleware(["auth", AdminMiddleware::class])
    ->prefix("admin")
    ->name("admin.")
    ->group(function () {
        Route::get("/dashboard", [AdminController::class, "dashboard"])->name("dashboard");
        Route::get("/complaints", [AdminController::class, "complaintsIndex"])->name("complaints.index");
        Route::get("/complaints/export", [AdminController::class, "exportComplaints"])->name("complaints.export");
        Route::post("/complaints/bulk-reassign", [AdminController::class, "bulkReassign"])->name("complaints.bulk-reassign");
        Route::get("/complaints/{complaint}", [AdminController::class, "complaintShow"])->name("complaints.show");
        Route::put("/complaints/{complaint}", [AdminController::class, "complaintUpdate"])->name("complaints.update");
        Route::get("/staff", [AdminController::class, "staffIndex"])->name("staff.index");
        Route::get("/staff/create", [AdminController::class, "staffCreate"])->name("staff.create");
        Route::post("/staff", [AdminController::class, "staffStore"])->name("staff.store");
        Route::patch("/staff/{user}/department", [AdminController::class, "staffUpdateDepartment"])->name("staff.department");
    });

// ── Staff ───────────────────────────────
Route::middleware(["auth", StaffMiddleware::class])
    ->prefix("staff")
    ->name("staff.")
    ->group(function () {
        Route::get("/dashboard", [StaffController::class, "dashboard"])->name("dashboard");
        Route::get("/complaints/{complaint}", [StaffController::class, "show"])->name("complaints.show");
        Route::put("/complaints/{complaint}", [StaffController::class, "update"])->name("complaints.update");
    });