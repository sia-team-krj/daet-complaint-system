<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    private const ROLES = ['citizen', 'staff', 'admin'];

    private const STATUSES = ['active', 'inactive'];

    /**
     * Display the searchable, filterable account directory.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $role = (string) $request->input('role', '');
        $status = (string) $request->input('status', '');
        $department = (string) $request->input('department', '');

        $query = User::query()
            ->with('department')
            ->withCount(['complaints', 'assignedComplaints']);

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_number', 'ILIKE', "%{$search}%")
                    ->orWhere('barangay', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($role, self::ROLES, true)) {
            $query->where('role', $role);
        }

        if (in_array($status, self::STATUSES, true)) {
            $query->where('is_active', $status === 'active');
        }

        if ($department === 'unassigned') {
            $query->whereNull('department_id');
        } elseif ($department !== '' && ctype_digit($department)) {
            $query->where('department_id', (int) $department);
        }

        $users = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
            'citizens' => User::query()->where('role', 'citizen')->count(),
            'staff' => User::query()->where('role', 'staff')->count(),
        ];

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return view('admin.users.index', compact(
            'users',
            'departments',
            'stats',
            'search',
            'role',
            'status',
            'department',
        ));
    }

    /**
     * Display one account with its access details and audit history.
     */
    public function show(User $user): View
    {
        $user->load('department')->loadCount(['complaints', 'assignedComplaints']);

        $activity = ActivityLog::with('actor')
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->getKey())
            ->latest('created_at')
            ->limit(15)
            ->get();

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        $activeAdminCount = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->count();

        return view('admin.users.show', compact(
            'user',
            'activity',
            'departments',
            'activeAdminCount',
        ));
    }

    /**
     * Update a user's profile, role, and department assignment.
     */
    public function update(
        Request $request,
        User $user,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\pL\s\-\.]+$/u',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\pL\s\-\.]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'contact_number' => [
                'nullable',
                'string',
                'regex:/^09\d{9}$/',
            ],
            'barangay' => [
                'nullable',
                'string',
                Rule::in(User::BARANGAYS),
            ],
            'role' => ['required', Rule::in(self::ROLES)],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id'),
            ],
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and dots.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and dots.',
            'contact_number.regex' => 'Enter a valid 11-digit PH mobile number starting with 09.',
            'barangay.in' => 'The selected barangay is not valid.',
        ]);

        $role = $validated['role'];
        $departmentId = $role === 'staff' ? ($validated['department_id'] ?? null) : null;
        $isCurrentUser = $user->is(Auth::user());

        if ($role === 'staff' && ! $departmentId) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'Staff accounts must be assigned to a department.']);
        }

        if ($role === 'staff' && ! Department::query()
            ->whereKey($departmentId)
            ->where('is_active', true)
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'Select an active department.']);
        }

        if ($isCurrentUser && $role !== 'admin') {
            return back()
                ->withInput()
                ->withErrors(['role' => 'You cannot change your own administrator role.']);
        }

        if ($user->role === 'staff' && $role !== 'staff' && $user->assignedComplaints()->exists()) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Reassign this staff member\'s complaints before changing their role.']);
        }

        if ($role === 'staff' && $user->assignedComplaints()
            ->where('department_id', '!=', $departmentId)
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'Reassign complaints outside the selected department first.']);
        }

        $oldValues = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'barangay' => $user->barangay,
            'role' => $user->role,
            'department_id' => $user->department_id,
        ];

        if (
            $user->role === 'admin'
            && $user->is_active
            && $role !== 'admin'
            && User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count() <= 1
        ) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Keep at least one active administrator on the system.']);
        }

        $updates = [
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'email' => strtolower(trim($validated['email'])),
            'contact_number' => $validated['contact_number'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'role' => $role,
            'department_id' => $departmentId,
        ];

        $changedFields = collect($updates)
            ->filter(fn (mixed $newValue, string $field): bool => (string) ($oldValues[$field] ?? '') !== (string) ($newValue ?? ''))
            ->keys()
            ->all();

        if ($changedFields === []) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('status', 'No changes were needed.');
        }

        DB::transaction(function () use ($user, $updates, $oldValues, $changedFields, $activityLogger): void {
            $user->update($updates);
            $user->refresh()->load('department');

            $profileFields = array_values(array_intersect(
                ['first_name', 'last_name', 'email', 'contact_number', 'barangay'],
                $changedFields,
            ));

            if ($profileFields !== []) {
                $activityLogger->log(
                    'user.profile_updated',
                    "{$user->full_name}'s account details were updated by an administrator.",
                    $user,
                    metadata: [
                        'changed_fields' => $profileFields,
                        'previous_values' => collect($profileFields)
                            ->mapWithKeys(fn (string $field): array => [$field => $oldValues[$field]])
                            ->all(),
                    ],
                );
            }

            if (in_array('role', $changedFields, true)) {
                $activityLogger->log(
                    'user.role_updated',
                    "{$user->full_name}'s role changed from {$oldValues['role']} to {$updates['role']}.",
                    $user,
                    departmentId: $updates['department_id'] ? (int) $updates['department_id'] : null,
                    metadata: [
                        'previous_role' => $oldValues['role'],
                        'role' => $updates['role'],
                    ],
                );
            }

            if (in_array('department_id', $changedFields, true)) {
                $oldDepartment = $oldValues['department_id']
                    ? Department::find($oldValues['department_id'])?->name
                    : 'Unassigned';
                $newDepartment = $updates['department_id']
                    ? Department::find($updates['department_id'])?->name
                    : 'Unassigned';

                $activityLogger->log(
                    'user.department_updated',
                    "{$user->full_name} moved from {$oldDepartment} to {$newDepartment}.",
                    $user,
                    departmentId: $updates['department_id'] ? (int) $updates['department_id'] : null,
                    metadata: [
                        'previous_department' => $oldDepartment,
                        'new_department' => $newDepartment,
                    ],
                );
            }
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User account updated successfully.');
    }

    /**
     * Toggle an account's access without deleting its history.
     */
    public function toggle(User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $wasActive = (bool) $user->is_active;

        if (
            $wasActive
            && $user->role === 'admin'
            && User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count() <= 1
        ) {
            return back()->with('error', 'Keep at least one active administrator on the system.');
        }

        DB::transaction(function () use ($user, $wasActive, $activityLogger): void {
            $user->update(['is_active' => ! $wasActive]);

            $activityLogger->log(
                $wasActive ? 'user.deactivated' : 'user.activated',
                "{$user->full_name} was ".($wasActive ? 'deactivated' : 'activated').' by an administrator.',
                $user,
                departmentId: $user->department_id ? (int) $user->department_id : null,
                metadata: [
                    'previous_is_active' => $wasActive,
                    'is_active' => ! $wasActive,
                ],
            );
        });

        $status = $wasActive ? 'deactivated' : 'activated';

        return back()->with('status', "User account {$status}.");
    }
}
