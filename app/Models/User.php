<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // ─────────────────────────────────────────────
    // BARANGAYS OF DAET, CAMARINES NORTE
    // ─────────────────────────────────────────────
    // Single source of truth for all 25 official barangays.
    // Used in:
    //   - Register search suggestions (register.blade.php)
    //   - RegisteredUserController validation: Rule::in(User::BARANGAYS)
    //   - Profile edit form
    //
    // Keep this list as the single source of truth for registration and profile options.
    // ─────────────────────────────────────────────
    const BARANGAYS = [
        "Alawihao",
        "Awitan",
        "Bagasbas",
        "Barangay I (Hilahod)",
        "Barangay II (Pasig)",
        "Barangay III (Iraya)",
        "Barangay IV (Mantagbac)",
        "Barangay V (Pandan)",
        "Barangay VI (Centro)",
        "Barangay VII (Diego Liñan)",
        "Barangay VIII (Salcedo)",
        "Bibirao",
        "Borabod",
        "Calasgasan",
        "Camambugan",
        "Cobangbang",
        "Dogongan",
        "Gahonon",
        "Gubat (Moreno, Gubat, Mandulongan)",
        "Lag-on",
        "Magang",
        "Mambalite",
        "Mancruz",
        "Pamorangon",
        "San Isidro",
    ];

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "email_verified_at",
        "contact_number",
        "barangay",
        "password",
        "role",
        "department_id",
        "created_by",
        "display_alias",
        "prefers_anonymity",
        "is_active",
        "can_verify",
        "locale",
    ];

    protected $hidden = ["password", "remember_token"];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "prefers_anonymity" => "boolean",
            "is_active" => "boolean",
            "can_verify" => "boolean",
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * May this account settle whether a complaint is genuine?
     *
     * A capability, not a role: the holder stays a department staff member in
     * the same queue as everyone else. Admin is always allowed, which is the
     * fallback that stops a department freezing when its one verifier is away.
     */
    public function canVerifyComplaints(): bool
    {
        return $this->role === 'admin' || (bool) $this->can_verify;
    }

    public function markEmailAsVerified(): void
    {
        $this->forceFill(['email_verified_at' => now()])->save();
    }

    // ─────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────

    /**
     * Full name — used in navbars: auth()->user()->full_name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Public-safe name — respects the anonymity setting.
     * Use on ALL public-facing Blade templates and API resources.
     * NEVER display real name directly when prefers_anonymity is true.
     *
     * Usage: $complaint->user->public_name
     */
    public function getPublicNameAttribute(): string
    {
        if ($this->prefers_anonymity && $this->display_alias) {
            return $this->display_alias;
        }

        return $this->full_name;
    }

    /**
     * Staff-safe resident label. Staff must never receive a resident's real
     * identity through complaint views; administrators use full_name instead.
     */
    public function getStaffSafeNameAttribute(): string
    {
        $alias = trim((string) $this->display_alias);

        return $alias !== ''
            ? $alias
            : 'Resident #' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Avatar initials — used in navbar avatar circles.
     * Usage: auth()->user()->initials → "JD"
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1),
        );
    }

    // ─────────────────────────────────────────────
    // ROLE HELPERS
    // Use these instead of checking role string directly.
    // Replaces the old is_admin boolean completely.
    //
    // Usage in Blade:  @if(auth()->user()->isAdmin())
    // Usage in PHP:    if ($user->isAdmin()) { ... }
    // ─────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === "admin";
    }

    /** Returns true for both staff AND admin */
    public function isStaff(): bool
    {
        return in_array($this->role, ["staff", "admin"]);
    }

    public function isCitizen(): bool
    {
        return $this->role === "citizen";
    }

    // ─────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** All complaints filed by this user as a citizen */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /** All complaints assigned to this user (for staff members) */
    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_staff_id');
    }

    /** All complaint log entries where this user was the actor (staff/admin) */
    public function complaintActions(): HasMany
    {
        return $this->hasMany(ComplaintLog::class, "actor_id");
    }

    public function createdInvitations(): HasMany
    {
        return $this->hasMany(InvitationCode::class, 'created_by');
    }

    public function redeemedInvitation(): HasMany
    {
        return $this->hasMany(InvitationCode::class, 'redeemed_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    public function emailVerificationCodes(): HasMany
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    // ─────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────

    public function scopeCitizens($query)
    {
        return $query->where("role", "citizen");
    }

    public function scopeStaff($query)
    {
        return $query->whereIn("role", ["staff", "admin"]);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where("department_id", $departmentId);
    }

    // ─────────────────────────────────────────────
    // STATIC HELPERS
    // ─────────────────────────────────────────────

    /**
     * Check if a given barangay name is valid.
     * Use in RegisteredUserController validation:
     *   'barangay' => ['nullable', Rule::in(User::BARANGAYS)]
     */
    public static function isValidBarangay(string $barangay): bool
    {
        return in_array($barangay, self::BARANGAYS);
    }
}
