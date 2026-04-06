<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    /**
     * Show profile page for all user roles (citizen, staff, admin).
     */
    public function index(): View
    {
        $user = Auth::user()->load('department');
        
        // Role-specific data
        $stats = match($user->role) {
            'citizen' => $this->getCitizenStats($user),
            'staff' => $this->getStaffStats($user),
            'admin' => $this->getAdminStats(),
            default => [],
        };

        return view('profile.index', compact('user', 'stats'));
    }

    private function getCitizenStats(User $user): array
    {
        return [
            'total_filed' => Complaint::where('user_id', $user->id)->count(),
            'resolved' => Complaint::where('user_id', $user->id)
                ->where('status', 'Resolved')
                ->count(),
            'pending' => Complaint::where('user_id', $user->id)
                ->whereNotIn('status', ['Resolved', 'Rejected', 'Closed'])
                ->count(),
        ];
    }

    private function getStaffStats(User $user): array
    {
        $handledCount = ComplaintLog::where('changed_by', $user->id)
            ->distinct('complaint_id')
            ->count('complaint_id');

        $resolvedThisMonth = ComplaintLog::where('changed_by', $user->id)
            ->where('new_status', 'Resolved')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Average days to resolve for complaints this staff worked on
        $avgDays = ComplaintLog::where('changed_by', $user->id)
            ->where('new_status', 'Resolved')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (created_at - complaint_logs.created_at)) / 86400) as avg_days')
            ->join('complaints', 'complaint_logs.complaint_id', '=', 'complaints.id')
            ->value('avg_days');

        return [
            'handled' => $handledCount,
            'resolved_this_month' => $resolvedThisMonth,
            'avg_days' => $avgDays ? round($avgDays, 1) : null,
        ];
    }

    private function getAdminStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_complaints' => Complaint::count(),
            'total_departments' => Department::count(),
        ];
    }

    /**
     * Update profile information (name and email only).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user()->fresh();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return redirect()->route('profile')
            ->with('status', 'Profile updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user()->fresh();

        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('profile')
                ->with('error', 'Current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return redirect()->route('profile')
            ->with('status', 'Password updated successfully.');
    }
}
