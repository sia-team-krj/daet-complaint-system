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

        // Staff and Department Administrators see their own office's work.
        if ($user->isDepartmentStaff()) {
            return (int) $complaint->department_id === (int) $user->department_id;
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

        // Staff and Department Administrators can only change status for
        // complaints in their own office.
        if ($user->isDepartmentStaff()) {
            return (int) $complaint->department_id === (int) $user->department_id;
        }

        // Citizens cannot change status
        return false;
    }

    /**
     * Office verifiers (Department Administrators and staff with verification
     * rights) may assign their office's work to an active member of that same
     * office. The global administrator remains the cross-office fallback.
     */
    public function assign(User $user, Complaint $complaint): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->isDepartmentStaff()
            && $user->canVerifyComplaints()
            && (int) $complaint->department_id === (int) $user->department_id;
    }

    /**
     * The resident who filed a resolved complaint may close it (confirm the
     * resolution). Nobody else closes on their behalf; staff/admin use the
     * normal status workflow.
     */
    public function close(User $user, Complaint $complaint): bool
    {
        return $user->id === $complaint->user_id
            && $complaint->statusEnum === \App\Enums\ComplaintStatus::Resolved;
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
        return in_array($user->role, ['admin', 'staff', 'department_admin', 'citizen'], true);
    }
}
