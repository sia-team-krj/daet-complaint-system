<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Citizen sees own complaints, staff sees dept complaints, admin sees all.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        // Admin can view all complaints
        if ($user->role === 'admin') {
            return true;
        }

        // Staff can view complaints in their department
        if ($user->role === 'staff') {
            return $complaint->department_id === $user->department_id;
        }

        // Citizen can only view their own complaints
        return $complaint->user_id === $user->id;
    }

    /**
     * Any authenticated user can create a complaint.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only admin can update complaint details.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Only admin can delete (soft delete) complaints.
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Staff of same dept OR admin can change status.
     */
    public function changeStatus(User $user, Complaint $complaint): bool
    {
        // Admin can change status on any complaint
        if ($user->role === 'admin') {
            return true;
        }

        // Staff can only change status for complaints in their department
        if ($user->role === 'staff') {
            return $complaint->department_id === $user->department_id;
        }

        // Citizens cannot change status
        return false;
    }

    /**
     * Only admin can view real identity of anonymous citizens.
     */
    public function viewRealIdentity(User $user, Complaint $complaint): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can view any complaints at all (for index/list views).
     * Admin sees all, staff sees dept, citizen sees own.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'citizen']);
    }
}
