<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?int $departmentId = null,
        array $metadata = [],
        ?User $actor = null,
    ): ActivityLog {
        $actor ??= Auth::user();
        $departmentId ??= $this->departmentFor($subject, $actor);
        $request = request();

        return ActivityLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->full_name,
            'actor_role' => $actor?->role,
            'department_id' => $departmentId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function departmentFor(?Model $subject, ?User $actor): ?int
    {
        return match (true) {
            $subject instanceof Complaint => $subject->department_id,
            $subject instanceof Department => $subject->id,
            $subject instanceof InvitationCode => $subject->department_id,
            $subject instanceof User => $subject->department_id,
            default => $actor?->department_id,
        };
    }
}
