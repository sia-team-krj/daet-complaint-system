<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reduce the department directory from eight offices to five.
 *
 * The municipality reorganised its offices. The directory that ships with the
 * app is now:
 *
 *   ENG    Engineering Office
 *   HRMO   Human Resource Management Office
 *   OM     Mayor's Office (Municipal Information Office)
 *   BPLO   Business and Licensing Office
 *   MENRO  Municipal Environment and Natural Resource Office
 *
 * Health (HLTH), General Services (GSO), Municipal Planning and Development
 * (MPDO) and Social Welfare and Development (SWDO) are gone, together with
 * the complaints that were routed to them. Waste Management (WST) is not
 * gone — it is the same office under a new name and code.
 *
 * The query builder is used throughout instead of the model: a data migration
 * must keep working even if the Department model changes shape later.
 */
return new class extends Migration
{
    /**
     * Offices removed outright, with the complaints that went with them.
     *
     * @var list<string>
     */
    private const REMOVED_CODES = ['HLTH', 'GSO', 'MPDO', 'SWDO'];

    /**
     * Staff accounts whose office was folded into another one, and the office
     * that took over the work.
     *
     * @var array<string, string>
     */
    private const STAFF_SUCCESSORS = [
        // Peace and order is now the Mayor's Office.
        'peaceorder@daetlistens.gov.ph' => 'OM',
        // General services work — cleanliness and waste — is now the
        // Municipal Environment and Natural Resource Office.
        'gso@daetlistens.gov.ph' => 'MENRO',
    ];

    public function up(): void
    {
        $this->removeOffices();
        $this->renameOffices();
        $this->addHumanResourceManagementOffice();
        $this->attachOrphanedStaff();
    }

    public function down(): void
    {
        $this->removeOffice('HRMO');
        $this->detachMovedStaff();

        // The renamed offices go back to the names and codes they had. The
        // complaints deleted by this migration are NOT restored — they are
        // gone for good.
        $this->rename('MENRO', 'WST', 'Waste Management Office', 'Garbage collection, waste disposal, and environmental cleanliness.');
        $this->rename('OM', 'OM', 'Office of the Mayor', 'Executive concerns escalated to the Mayor\'s office.');
        $this->rename('BPLO', 'BPLO', 'Business Permit and Licensing Office', 'Business permits, licensing, and regulatory compliance.');

        $this->restoreOffices();
    }

    /**
     * Delete the four closed offices and every complaint routed to them.
     */
    private function removeOffices(): void
    {
        $departmentIds = DB::table('departments')
            ->whereIn('code', self::REMOVED_CODES)
            ->pluck('id');

        if ($departmentIds->isEmpty()) {
            return;
        }

        $complaintIds = DB::table('complaints')
            ->whereIn('department_id', $departmentIds)
            ->pluck('id');

        if ($complaintIds->isNotEmpty()) {
            // These rows point AT a complaint. A notification is the "your
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

        // invitation_codes.department_id is RESTRICT: an invitation cannot keep
        // pointing at an office that no longer exists.
        DB::table('invitation_codes')
            ->whereIn('department_id', $departmentIds)
            ->delete();

        DB::table('complaints')
            ->whereIn('department_id', $departmentIds)
            ->delete();

        // users.department_id and activity_logs.department_id are SET NULL, so
        // the staff accounts and the audit rows survive — the accounts fall
        // back to "no department" for an administrator to reassign, and the
        // trail keeps the office name in its own text.
        DB::table('departments')
            ->whereIn('id', $departmentIds)
            ->delete();
    }

    /**
     * Attach staff accounts left without an office to the office that took
     * over their work.
     *
     * Deleting an office leaves its staff with no department — the foreign key
     * sets it to null. Such an account can log in but can never receive work,
     * so it is parked at the office that inherited its portfolio. Anything
     * without a named successor goes to the Mayor's Office, the general desk.
     */
    private function attachOrphanedStaff(): void
    {
        foreach (self::STAFF_SUCCESSORS as $email => $code) {
            $departmentId = DB::table('departments')->where('code', $code)->value('id');

            if ($departmentId === null) {
                continue;
            }

            DB::table('users')
                ->where('email', $email)
                ->whereNull('department_id')
                ->update(['department_id' => $departmentId, 'updated_at' => now()]);
        }

        $fallbackId = DB::table('departments')->where('code', 'OM')->value('id');

        if ($fallbackId === null) {
            return;
        }

        DB::table('users')
            ->where('role', 'staff')
            ->whereNull('department_id')
            ->update(['department_id' => $fallbackId, 'updated_at' => now()]);
    }

    /**
     * Put the moved staff accounts back on no office (the down() path only).
     */
    private function detachMovedStaff(): void
    {
        DB::table('users')
            ->whereIn('email', array_keys(self::STAFF_SUCCESSORS))
            ->update(['department_id' => null, 'updated_at' => now()]);
    }

    /**
     * Recreate the closed offices (the down() path only).
     */
    private function restoreOffices(): void
    {
        $offices = [
            ['name' => 'Health Office', 'code' => 'HLTH', 'description' => 'Public health concerns, sanitation, and medical services.'],
            ['name' => 'General Services Office', 'code' => 'GSO', 'description' => 'Government facilities, equipment, and general public services.'],
            ['name' => 'Municipal Planning and Development Office', 'code' => 'MPDO', 'description' => 'Land use, urban planning, and development projects.'],
            ['name' => 'Social Welfare and Development Office', 'code' => 'SWDO', 'description' => 'Social services, livelihood assistance, and welfare programs.'],
        ];

        foreach ($offices as $office) {
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

    /**
     * Put the surviving offices under their new names and codes.
     */
    private function renameOffices(): void
    {
        $this->rename(
            'WST',
            'MENRO',
            'Municipal Environment and Natural Resource Office',
            'Garbage collection, waste disposal, environmental cleanliness, and plastic policy enforcement.',
        );

        $this->rename(
            'OM',
            'OM',
            "Mayor's Office",
            'Municipal Information Office — public information, announcements, and community concerns escalated to the Mayor.',
        );

        $this->rename(
            'BPLO',
            'BPLO',
            'Business and Licensing Office',
            'Business permits, licensing, and regulatory compliance.',
        );
    }

    /**
     * Rename one office in place, keeping its id so every complaint, user,
     * invitation and audit row stays attached to it.
     */
    private function rename(string $fromCode, string $toCode, string $name, string $description): void
    {
        $department = DB::table('departments')->where('code', $fromCode)->first();

        if ($department === null) {
            return;
        }

        DB::table('departments')
            ->where('id', $department->id)
            ->update([
                'code' => $toCode,
                'name' => $name,
                'description' => $description,
                'updated_at' => now(),
            ]);
    }

    /**
     * Add the Human Resource Management Office.
     */
    private function addHumanResourceManagementOffice(): void
    {
        $exists = DB::table('departments')->where('code', 'HRMO')->exists();

        if ($exists) {
            return;
        }

        DB::table('departments')->insert([
            'name' => 'Human Resource Management Office',
            'code' => 'HRMO',
            'description' => 'Personnel services, human resource management, employee matters, and staff administration.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Delete a single office and its complaints (the down() path only).
     */
    private function removeOffice(string $code): void
    {
        $departmentId = DB::table('departments')->where('code', $code)->value('id');

        if ($departmentId === null) {
            return;
        }

        $complaintIds = DB::table('complaints')->where('department_id', $departmentId)->pluck('id');

        if ($complaintIds->isNotEmpty()) {
            DB::table('notifications')
                ->where('subject_type', 'App\\Models\\Complaint')
                ->whereIn('subject_id', $complaintIds)
                ->delete();

            DB::table('activity_logs')
                ->where('subject_type', 'App\\Models\\Complaint')
                ->whereIn('subject_id', $complaintIds)
                ->delete();
        }

        DB::table('invitation_codes')->where('department_id', $departmentId)->delete();
        DB::table('complaints')->where('department_id', $departmentId)->delete();
        DB::table('departments')->where('id', $departmentId)->delete();
    }
};
