<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The directory is down to three offices.
 *
 * The Business and Licensing Office and the Human Resource Management Office
 * are closed. Their complaints go with them, exactly as the four offices
 * closed before them did, and their staff accounts are removed — new ones
 * can be made when the municipality is ready.
 *
 * The categories those offices received — business permits and personnel
 * reports — are withdrawn for now: they stay in the system so anything
 * already filed under them still reads properly, but they are no longer
 * offered to residents, and the Mayor's office receives them in the
 * meantime.
 */
return new class extends Migration
{
    /**
     * The offices being closed.
     *
     * @var list<string>
     */
    private const CLOSED_CODES = ['BPLO', 'HRMO'];

    public function up(): void
    {
        $departmentIds = DB::table('departments')
            ->whereIn('code', self::CLOSED_CODES)
            ->pluck('id');

        if ($departmentIds->isEmpty()) {
            return;
        }

        $complaintIds = DB::table('complaints')
            ->whereIn('department_id', $departmentIds)
            ->pluck('id');

        if ($complaintIds->isNotEmpty()) {
            // Rows that point AT a complaint. A notification is the "your
            // ticket moved" message and an activity row is the trail of what
            // happened to that ticket; both are meaningless once the ticket
            // itself is gone.
            DB::table('notifications')
                ->where('subject_type', 'App\\Models\\Complaint')
                ->whereIn('subject_id', $complaintIds)
                ->delete();

            DB::table('activity_logs')
                ->where('subject_type', 'App\\Models\\Complaint')
                ->whereIn('subject_id', $complaintIds)
                ->delete();
        }

        // complaint_logs, complaint_evidences and complaint_information_requests
        // cascade on complaint_id, so the complaints below take them with it.

        DB::table('invitation_codes')
            ->whereIn('department_id', $departmentIds)
            ->delete();

        DB::table('complaints')
            ->whereIn('department_id', $departmentIds)
            ->delete();

        // The staff of a closed office are removed with it. The accounts are
        // soft-deleted, so the audit trail still says who did what.
        DB::table('users')
            ->whereIn('department_id', $departmentIds)
            ->update([
                'department_id' => null,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('departments')
            ->whereIn('id', $departmentIds)
            ->delete();
    }

    public function down(): void
    {
        // The offices and their complaints are gone for good: a hard delete
        // cannot be undone. Only the directory is restored, so the system is
        // left consistent rather than half-reverted.
        foreach ([
            ['name' => 'Business and Licensing Office', 'code' => 'BPLO', 'description' => 'Business permits, licensing, and regulatory compliance.'],
            ['name' => 'Human Resource Management Office', 'code' => 'HRMO', 'description' => 'Personnel services, human resource management, employee matters, and staff administration.'],
        ] as $office) {
            $exists = DB::table('departments')->where('code', $office['code'])->exists();

            if ($exists) {
                continue;
            }

            DB::table('departments')->insert($office + [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
