<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;

// ── Public ─────────────────────────────────────────────────────────────────
Route::get("/", [HomeController::class, "home"])->name("home");

// ── Guest only ─────────────────────────────────────────────────────────────
Route::middleware("guest")->group(function () {
    Route::get("/login", [AuthController::class, "showLogin"])->name("login");
    Route::get("/register", [AuthController::class, "showRegister"])->name(
        "register",
    );
    Route::post("/login", [AuthController::class, "login"])->name(
        "login.submit",
    );
    Route::post("/register", [AuthController::class, "register"])->name(
        "register.submit",
    );
});

// ── Auth only ──────────────────────────────────────────────────────────────
Route::middleware("auth")->group(function () {
    Route::post("/logout", [AuthController::class, "logout"])->name("logout");
    Route::get("/dashboard", fn() => view("pages.home.dashboard"))->name(
        "dashboard",
    );
    Route::get("/profile", fn() => view("pages.home.dashboard"))->name(
        "profile",
    );
    Route::get(
        "/complaints/create",
        fn() => view("pages.home.dashboard"),
    )->name("complaints.create");
});

// ── Admin only ─────────────────────────────────────────────────────────────
Route::middleware(["auth", "admin"])
    ->prefix("admin")
    ->name("admin.")
    ->group(function () {
        Route::get("/dashboard", fn() => view("pages.home.dashboard"))->name(
            "dashboard",
        );
    });
