<?php

namespace App\Models;

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
     * All staff members (users) belonging to this department.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
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
