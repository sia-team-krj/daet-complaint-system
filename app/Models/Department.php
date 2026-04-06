<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Staff members assigned to this department
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'staff');
    }

    /**
     * Users assigned to this department (staff and admins)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Complaints routed to this department
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Scope for active departments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
