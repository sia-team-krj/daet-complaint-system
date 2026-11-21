<?php

namespace App\Models;

use App\Services\ComplaintTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ["name", "code", "description", "is_active"];

    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
        ];
    }

    protected static function booted(): void
    {
        // The taxonomy caches office names and active state with the
        // categories. A renamed or archived office must reach the wizard
        // immediately, not after the cache forgets.
        static::saved(fn () => ComplaintTaxonomy::flush());
        static::deleted(fn () => ComplaintTaxonomy::flush());
    }

    // ─────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────

    /**
     * All complaints assigned to this department.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Everyone who works in this office's staff workspace.
     *
     * Department Administrators are included because they are part of the
     * office, but a manager must never be able to act on a peer manager. The
     * management controller still restricts its actions to role = staff.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', ['staff', 'department_admin']);
    }

    /**
     * The office's Department Administrators. Used by the archive guard: an
     * office with a manager attached cannot be archived until the global
     * administrator revokes the role.
     */
    public function departmentAdmins(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'department_admin');
    }

    public function invitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * The complaint categories this office receives.
     */
    public function complaintCategories(): HasMany
    {
        return $this->hasMany(ComplaintCategory::class);
    }

    // ─────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────

    /**
     * Count of pending complaints for this department.
     * Used on the staff dashboard badge/counter.
     */
    public function pendingCount(): int
    {
        return $this->complaints()
            ->whereIn("status", ["Submitted", "Under Review", "In Progress"])
            ->count();
    }
}
