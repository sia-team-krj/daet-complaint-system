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
        'Alawihao',
        'Awitan',
        'Bagasbas',
        'Barangay I (Hilahod)',
        'Barangay II (Pasig)',
        'Barangay III (Iraya)',
        'Barangay IV (Mantagbac)',
        'Barangay V (Pandan)',
        'Barangay VI (Centro)',
        'Barangay VII (Diego Liñan)',
        'Barangay VIII (Salcedo)',
        'Bibirao',
        'Borabod',
        'Calasgasan',
        'Camambugan',
        'Cobangbang',
        'Dogongan',
        'Gahonon',
        'Gubat (Moreno, Gubat, Mandulongan)',
        'Lag-on',
        'Magang',
        'Mambalite',
        'Mancruz',
        'Pamorangon',
        'San Isidro',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'contact_number',
        'barangay',
        'password',
        'must_change_password',
        'password_expires_at',
        'role',
        'department_id',
        'created_by',
        'display_alias',
        'prefers_anonymity',
        'is_active',
        'can_verify',
        'locale',
        'text_size',
        'notification_preferences',
        'default_queue_view',
        'daily_digest',
        'identity_status',
        'identity_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_last_used_step',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'prefers_anonymity' => 'boolean',
            'must_change_password' => 'boolean',
            'password_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'can_verify' => 'boolean',
            'notification_preferences' => 'array',
            'daily_digest' => 'boolean',
            'identity_status' => 'string',
            'identity_verified_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'two_factor_last_used_step' => 'integer',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Temporary-password expiry: a temp password that has passed its
     * expiry instant can no longer be used to sign in.
     */
    public function temporaryPasswordExpired(): bool
    {
        return $this->must_change_password
            && $this->password_expires_at !== null
            && $this->password_expires_at->isPast();
    }

    /**
     * Clear the forced-change flag after the user sets their own password.
     */
    public function clearMustChangePassword(): void
    {
        $this->forceFill([
            'must_change_password' => false,
            'password_expires_at' => null,
        ])->save();
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
        // A Department Administrator is the office's standing verifier: the
        // role exists to keep the office moving, so it does not depend on the
        // per-account can_verify flag that ordinary staff need.
        return $this->role === 'admin'
            || $this->role === 'department_admin'
            || (bool) $this->can_verify;
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
            : 'Resident #'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Name shown on staff-facing surfaces. Complaint timelines, activity
     * groups and the live ticket panel are shared by staff and resident
     * actors, so the label must pick the right name per role: a resident is
     * masked by the same staff-safe alias as everywhere else on staff
     * screens, while staff and administrators keep their real names so the
     * office still knows who acted. The resident's real name reaches only
     * the administrator surfaces (see ComplaintPolicy::viewRealIdentity).
     *
     * Usage: $log->actor->staff_actor_name
     */
    public function getStaffActorNameAttribute(): string
    {
        return $this->role === 'citizen'
            ? $this->staff_safe_name
            : $this->full_name;
    }

    /**
     * Avatar initials — used in navbar avatar circles.
     * Usage: auth()->user()->initials → "JD"
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1).substr($this->last_name, 0, 1),
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
        return $this->role === 'admin';
    }

    /**
     * A Department Administrator runs one office: staff management, the
     * office's queue, verification, analytics and its audit trail. They are
     * not a global administrator and never see another office.
     */
    public function isDepartmentAdmin(): bool
    {
        return $this->role === 'department_admin';
    }

    /**
     * An ordinary staff member or the Department Administrator of an office.
     * Use this for office-scoped checks; use isAdmin() for global powers.
     */
    public function isDepartmentStaff(): bool
    {
        return in_array($this->role, ['staff', 'department_admin'], true);
    }

    /** Returns true for staff, Department Administrators and admins. */
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'department_admin', 'admin'], true);
    }

    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    /** @return HasMany<IdentityVerification, $this> */
    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class, 'user_id')->orderByDesc('id');
    }

    public function latestIdentityVerification(): ?IdentityVerification
    {
        return $this->identityVerifications()->first();
    }

    public function isIdentityVerified(): bool
    {
        return $this->identity_status === 'verified' && $this->identity_verified_at !== null;
    }

    /**
     * Whether the resident may file a new complaint: verified, or verification
     * not required by config. Pending, rejected, and unverified cannot file.
     */
    public function canFileComplaints(): bool
    {
        if (! config('identity.verification_required')) {
            return true;
        }

        if (! $this->isCitizen()) {
            return true;
        }

        return $this->isIdentityVerified();
    }

    public function requiresIdentityVerification(): bool
    {
        return config('identity.verification_required') && $this->isCitizen() && ! $this->isIdentityVerified();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null
            && is_string($this->two_factor_secret)
            && $this->two_factor_secret !== '';
    }

    /**
     * Remove the authenticator and recovery codes. Used by the CLI reset and
     * by the re-enrollment flow after the current code has been verified.
     */
    public function clearTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_used_step' => null,
        ])->save();
    }

    /**
     * Where this user's home is. Single source for logo + post-login/
     * post-register/verification + denial redirects.
     * Citizen -> My Complaints (complaints.index); /dashboard now 302s there.
     */
    public function homeRouteName(): string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'staff', 'department_admin' => 'staff.dashboard',
            default => 'complaints.index',
        };
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
        return $this->hasMany(ComplaintLog::class, 'actor_id');
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
        return $query->where('role', 'citizen');
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', ['staff', 'department_admin', 'admin']);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // ─────────────────────────────────────────────
    // RESIDENT TRUST / HEALTH SCORE
    // ─────────────────────────────────────────────

    /**
     * Resident trust score (0-100). Starts at 100.
     * Confirmed misleading/spam: -20. Clean Resolved/Closed: +5.
     */
    public function trustScore(): int
    {
        $bad = (int) $this->complaints()
            ->where(function ($q) {
                $q->whereNotNull('marking_confirmed_at')
                    ->orWhereNotNull('spam_confirmed_at');
            })
            ->count();

        $good = (int) $this->complaints()
            ->whereIn('status', ['Resolved', 'Closed'])
            ->whereNull('marking_confirmed_at')
            ->whereNull('spam_confirmed_at')
            ->count();

        return max(0, min(100, 100 - (20 * $bad) + (5 * $good)));
    }

    public function trustScoreLabel(): string
    {
        $score = $this->trustScore();

        return match (true) {
            $score >= 80 => __('staff.trust.good'),
            $score >= 50 => __('staff.trust.fair'),
            default => __('staff.trust.at_risk'),
        };
    }

    /**
     * @return array{score:int,label:string,bad:int,good:int}
     */
    public function trustScoreSummary(): array
    {
        $bad = (int) $this->complaints()
            ->where(function ($q) {
                $q->whereNotNull('marking_confirmed_at')
                    ->orWhereNotNull('spam_confirmed_at');
            })
            ->count();

        $good = (int) $this->complaints()
            ->whereIn('status', ['Resolved', 'Closed'])
            ->whereNull('marking_confirmed_at')
            ->whereNull('spam_confirmed_at')
            ->count();

        $score = max(0, min(100, 100 - (20 * $bad) + (5 * $good)));
        $label = match (true) {
            $score >= 80 => __('staff.trust.good'),
            $score >= 50 => __('staff.trust.fair'),
            default => __('staff.trust.at_risk'),
        };

        return ['score' => $score, 'label' => $label, 'bad' => $bad, 'good' => $good];
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
