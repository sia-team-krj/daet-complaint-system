<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransparencyController;
use App\Http\Controllers\RewardsController;
use App\Http\Controllers\Admin\AdminDashboardController;

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

// TODO: Password reset routes
// Route::get('/forgot-password', ...)->name('password.request');
// Route::post('/forgot-password', ...)->name('password.email');
// Route::get('/reset-password/{token}', ...)->name('password.reset');
// Route::post('/reset-password', ...)->name('password.update');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES — must be logged in + email verified
|--------------------------------------------------------------------------
*/

Route::middleware(["auth", "verified"])->group(function () {
    Route::get("/dashboard", [DashboardController::class, "index"])->name(
        "dashboard",
    );
    Route::get("/profile", [ProfileController::class, "index"])->name(
        "profile",
    );

    // TODO: Complaints
    // Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    // Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    // Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    // Route::get('/track', [ComplaintController::class, 'track'])->name('complaints.track');
});

/*
|--------------------------------------------------------------------------
| STAFF ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(["auth", "verified", "role:staff,admin"])
    ->prefix("staff")
    ->group(function () {
        // TODO: Staff inbox, status updates
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(["auth", "verified", "role:admin"])
    ->prefix("admin")
    ->group(function () {
        Route::get("/dashboard", [
            AdminDashboardController::class,
            "index",
        ])->name("admin.dashboard");
        // TODO: User management, department reassignment
    });
