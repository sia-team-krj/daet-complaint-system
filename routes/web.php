<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminInvitationController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationRedemptionController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ComplaintVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\TransparencyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES — no login required
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// The language switch is public on purpose: a resident must be able to read
// the portal before they have an account. It is a POST so crawlers and link
// prefetchers cannot silently change the language of the site.
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// ── STANDING LEGAL DOCUMENTS ───────────────────────
// Public and guest-accessible on purpose: a privacy policy a resident cannot
// reach before registering is not notice. The registration consent checkbox
// links here, and so do both navbars and the home page footer.
Route::get('/privacy', [LegalController::class, 'privacyPolicy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'termsAndConditions'])->name('terms');

Route::get('/transparency', [TransparencyController::class, 'index'])->name(
    'transparency',
);

// Living design reference. NOT public: "admin or local environment only".
// It renders every token and component on real classes so the guide never
// goes stale.
Route::get('/design-system', function () {
    abort_unless(
        app()->environment('local', 'testing')
            || (auth()->check() && auth()->user()->role === 'admin'),
        403,
    );

    return view('pages.design-system');
})->name('design-system');
Route::get('/transparency/complaints', [TransparencyController::class, 'complaints'])
    ->name('transparency.complaints')
    ->middleware('throttle:60,1');
// Must be declared before /transparency/complaints/{complaint}, or "browser"
// would be read as a complaint id.
Route::get('/transparency/complaints/browser', [TransparencyController::class, 'complaintsBrowser'])
    ->name('transparency.complaints.browser')
    ->middleware('throttle:60,1');
Route::get('/transparency/complaints/{complaint}', [TransparencyController::class, 'show'])
    ->name('transparency.complaints.show');
Route::get('/transparency/complaints/{complaint}/evidence/{index}', [ComplaintController::class, 'publicEvidence'])
    ->whereNumber('index')
    ->name('transparency.complaints.evidence');
Route::get('/transparency/complaints/{complaint}/updates/{log}/evidence/{index}', [ComplaintController::class, 'publicUpdateEvidence'])
    ->whereNumber('index')
    ->name('transparency.complaints.updates.evidence');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES — login, register, logout
|--------------------------------------------------------------------------
*/

// Register
Route::get('/register', [AuthController::class, 'showRegister'])
    ->middleware('guest')
    ->name('register');

Route::post('/register', [AuthController::class, 'register'])->middleware(
    'guest',
);

// Login
Route::get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'force.password.change'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'show'])->name('email.verify');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->name('email.verify.submit');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('email.verify.resend');

    // Forced password change for accounts created with a temporary password.
    // Deliberately outside email.verified so a new staff member can set their
    // own password before verifying their email.
    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change.show');
    Route::post('/password/change', [PasswordChangeController::class, 'store'])->name('password.change.store');
});

// ── PASSWORD RESET ───────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [AuthController::class, 'showResetPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('guest')->group(function () {
    Route::get('/invitations/{code}', [InvitationRedemptionController::class, 'show'])
        ->name('invitations.show');
    Route::post('/invitations/{code}', [InvitationRedemptionController::class, 'redeem'])
        ->name('invitations.redeem');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES — must be logged in + email verified
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'email.verified', 'force.password.change'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name(
        'dashboard',
    );
    // My Profile is archived — everything is customizable in Settings.
    // Kept as a redirect so old bookmarks/bookmarks in emails still work.
    Route::get('/profile', fn () => redirect()->route('settings', ['tab' => 'profile']))->name(
        'profile',
    );
    Route::post('/profile', [ProfileController::class, 'update'])->name(
        'profile.update',
    );
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name(
        'profile.password',
    );

    // ── SETTINGS (all roles) ──
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/logout-other-devices', [SettingsController::class, 'logoutOtherDevices'])->name('settings.logout-other-devices');
    Route::post('/settings/sessions/{sessionId}/logout', [SettingsController::class, 'logoutSession'])->name('settings.sessions.logout');
    Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::get('/settings/download-data', [SettingsController::class, 'downloadData'])->name('settings.download-data');
    Route::post('/settings/delete-account', [SettingsController::class, 'deleteAccount'])->name('settings.delete-account');

    // ── NOTIFICATIONS ──
    // The in-app bell. Read state lives in the database, so the badge, the
    // dropdown and any later email digest can never disagree.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ── COMPLAINTS ──────────────────────────
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}/evidence/{index}', [ComplaintController::class, 'evidence'])
        ->whereNumber('index')
        ->name('complaints.evidence');
    Route::get('/complaints/{complaint}/updates/{log}/evidence/{index}', [ComplaintController::class, 'updateEvidence'])
        ->whereNumber('index')
        ->name('complaints.updates.evidence');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    // The resident's half of the verification loop: answering a verifier's
    // request for better evidence. Every other complaint route is read-only.
    Route::post('/complaints/{complaint}/information', [ComplaintController::class, 'provideInformation'])
        ->name('complaints.information.store');
    // Whether a complaint appears on the transparency page is the resident's
    // own decision, so it gets its own route rather than riding along on any
    // staff or admin action. Nothing else in the system writes is_public.
    Route::put('/complaints/{complaint}/visibility', [ComplaintController::class, 'updateVisibility'])
        ->name('complaints.visibility.update');
    // The resident confirms the resolution of their own report. Only allowed
    // once the department has marked it Resolved; auto-closes after 7 days if
    // they do not act.
    Route::post('/complaints/{complaint}/close', [ComplaintController::class, 'close'])
        ->name('complaints.close');
    Route::get('/complaints/{complaint}/success', [ComplaintController::class, 'success'])->name('complaints.success');
    Route::get('/track', [ComplaintController::class, 'track'])->name('complaints.track');
});

/*
|--------------------------------------------------------------------------
| STAFF ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'email.verified', 'role:staff,admin', 'force.password.change'])
    ->prefix('staff')
    ->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
        Route::get('/activity', [StaffController::class, 'activityIndex'])->name('staff.activity.index');
        Route::get('/complaints', [StaffController::class, 'complaintsIndex'])->name('staff.complaints.index');
        Route::get('/complaints/browser', [StaffController::class, 'complaintsBrowser'])->name('staff.complaints.browser');
        // The live ticket panel's machine endpoint. It answers a fingerprint,
        // never a page: the page's form must not be replaced under the typist.
        Route::get('/complaints/{complaint}/state', [StaffController::class, 'complaintState'])->name('staff.complaints.state');
        Route::get('/complaints/{complaint}', [StaffController::class, 'show'])->name('staff.complaints.show');
        Route::put('/complaints/{complaint}/review', [StaffController::class, 'review'])->name('staff.complaints.review');
        Route::put('/complaints/{complaint}', [StaffController::class, 'update'])->name('staff.complaints.update');
        // Moderation is a content decision, separate from the resident's
        // visibility choice and from the review decision. It is the only staff
        // or admin action that can act on a public listing.
        Route::post('/complaints/{complaint}/moderation', [StaffController::class, 'moderate'])
            ->name('staff.complaints.moderate');

        // Department-led verification. Verify/reject stays on the established
        // staff.complaints.review endpoint; these two actions are new.
        Route::post('/complaints/{complaint}/verification/request-information', [ComplaintVerificationController::class, 'requestInformation'])
            ->name('staff.complaints.verification.request-information');
        Route::post('/complaints/{complaint}/verification/delegate', [ComplaintVerificationController::class, 'delegate'])
            ->name('staff.complaints.verification.delegate');
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'email.verified', 'role:admin', 'force.password.change'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [
            AdminController::class,
            'dashboard',
        ])->name('admin.dashboard');
        Route::get('/complaints', [AdminController::class, 'complaintsIndex'])->name('admin.complaints.index');
        Route::get('/complaints/browser', [AdminController::class, 'complaintsBrowser'])->name('admin.complaints.browser');
        Route::get('/complaints/export', [AdminController::class, 'exportComplaints'])->name('admin.complaints.export');
        Route::post('/complaints/bulk-reassign', [AdminController::class, 'bulkReassign'])->name('admin.complaints.bulk-reassign');
        Route::get('/complaints/{complaint}', [AdminController::class, 'complaintShow'])->name('admin.complaints.show');
        Route::get('/complaints/{complaint}/state', [AdminController::class, 'complaintState'])->name('admin.complaints.state');
        Route::put('/complaints/{complaint}', [AdminController::class, 'complaintUpdate'])->name('admin.complaints.update');
        Route::post('/complaints/{complaint}/verification/request-information', [ComplaintVerificationController::class, 'requestInformation'])
            ->name('admin.complaints.verification.request-information');
        Route::post('/complaints/{complaint}/moderation', [AdminController::class, 'moderateComplaint'])
            ->name('admin.complaints.moderate');
        Route::get('/departments', [AdminController::class, 'departmentsIndex'])->name('admin.departments.index');
        // Creation has its own page rather than a panel beside the list. The
        // directory is for scanning offices; a form wedged under it meant the
        // only way to add one was to scroll past every office, and on a phone
        // that was several screens of nothing.
        Route::get('/departments/create', [AdminController::class, 'departmentCreate'])->name('admin.departments.create');
        Route::post('/departments', [AdminController::class, 'departmentStore'])->name('admin.departments.store');

        // Archive and restore rather than delete. Every complaint, staff
        // account, invitation, and audit record points at a department, so
        // deleting one would null those references and erase the routing
        // history behind every case that office ever handled. Archiving stops
        // new work, keeps the history readable, and can be undone.
        Route::put('/departments/{department}', [AdminController::class, 'departmentUpdate'])->name('admin.departments.update');
        Route::post('/departments/{department}/archive', [AdminController::class, 'departmentArchive'])->name('admin.departments.archive');
        Route::post('/departments/{department}/restore', [AdminController::class, 'departmentRestore'])->name('admin.departments.restore');
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->name('admin.users.toggle');
        Route::get('/staff', [AdminController::class, 'staffIndex'])->name('admin.staff.index');
        Route::get('/staff/create', [AdminController::class, 'staffCreate'])->name('admin.staff.create');
        Route::get('/staff/created', [AdminController::class, 'staffCreated'])->name('admin.staff.created');
        Route::post('/staff', [AdminController::class, 'staffStore'])->name('admin.staff.store');
        Route::put('/staff/{user}/department', [AdminController::class, 'staffUpdateDepartment'])->name('admin.staff.update-department');
        Route::post('/staff/{user}/toggle', [AdminController::class, 'staffToggleStatus'])->name('admin.staff.toggle');
        Route::post('/staff/{user}/toggle-verification', [AdminController::class, 'staffToggleVerification'])->name('admin.staff.toggle-verification');
        Route::get('/invitations', [AdminInvitationController::class, 'index'])->name('admin.invitations.index');
        Route::post('/invitations/preview', [AdminInvitationController::class, 'preview'])->name('admin.invitations.preview');
        Route::post('/invitations/send', [AdminInvitationController::class, 'send'])->name('admin.invitations.send');
        Route::post('/invitations', [AdminInvitationController::class, 'store'])->name('admin.invitations.store');
        Route::patch('/invitations/{invitation}/revoke', [AdminInvitationController::class, 'revoke'])->name('admin.invitations.revoke');
        Route::get('/audit-trail', [AdminController::class, 'auditIndex'])->name('admin.audit.index');
        Route::get('/audit-trail/people', [AdminController::class, 'auditPeople'])->name('admin.audit.people');
        Route::get('/audit-trail/browser', [AdminController::class, 'auditBrowser'])->name('admin.audit.browser');
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
        Route::put('/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('/settings/categories', [AdminSettingsController::class, 'storeCategory'])->name('admin.settings.categories.store');
        Route::put('/settings/categories/{category}', [AdminSettingsController::class, 'updateCategory'])->name('admin.settings.categories.update');
        Route::post('/settings/categories/{category}/toggle', [AdminSettingsController::class, 'toggleCategory'])->name('admin.settings.categories.toggle');
    });
