<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users: one super admin, department administrators,
     * field staff, verifiers, and residents.
     *
     * Demo credentials (local/presentation only):
     *
     *   admin@daetlistens.gov.ph         Admin@1234      (System Administrator)
     *   deptadmin.eng@daetlistens.gov.ph DeptAdmin@1234 (Engineering — Dept Admin)
     *   deptadmin.om@daetlistens.gov.ph  DeptAdmin@1234 (Mayor's Office — Dept Admin)
     *   deptadmin.menro@daetlistens.gov.ph DeptAdmin@1234 (MENRO — Dept Admin)
     *   verifier.eng@daetlistens.gov.ph  Verifier@1234  (Engineering — Verifier)
     *   verifier.om@daetlistens.gov.ph   Verifier@1234  (Mayor's Office — Verifier)
     *   verifier.menro@daetlistens.gov.ph Verifier@1234 (MENRO — Verifier)
     *   staff.eng.1@daetlistens.gov.ph   Staff@1234     (Engineering Field Staff 1)
     *   staff.eng.2@daetlistens.gov.ph   Staff@1234     (Engineering Field Staff 2)
     *   staff.om.1@daetlistens.gov.ph    Staff@1234     (Mayor's Office Field Staff 1)
     *   staff.om.2@daetlistens.gov.ph    Staff@1234     (Mayor's Office Field Staff 2)
     *   staff.menro.1@daetlistens.gov.ph Staff@1234    (MENRO Field Staff 1)
     *   staff.menro.2@daetlistens.gov.ph Staff@1234    (MENRO Field Staff 2)
     *   Residents (5): Test@1234
     *   aling@gmail.com · bela@gmail.com · carlo@gmail.com · dana@gmail.com · eduardo@gmail.com
     */
    public function run(): void
    {
        $this->command->info('Seeding users...');

        $users = [];

        // ── SUPER ADMIN ACCOUNT ──
        $admin = User::firstOrCreate(
            ['email' => 'admin@daetlistens.gov.ph'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'password' => Hash::make('Admin@1234'),
                'role' => 'admin',
                'department_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $users[] = ['Super Admin', $admin->full_name, $admin->email, '—', 'Admin@1234'];

        // ── DEPARTMENT ADMINISTRATOR ACCOUNTS (one per department) ──
        $deptAdminEmails = [
            'ENG' => 'deptadmin.eng@daetlistens.gov.ph',
            'OM'  => 'deptadmin.om@daetlistens.gov.ph',
            'MENRO' => 'deptadmin.menro@daetlistens.gov.ph',
        ];

        foreach ($deptAdminEmails as $code => $email) {
            $dept = Department::where('code', $code)->firstOrFail();
            $deptAdmin = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'DeptAdmin',
                    'last_name' => ucfirst(strtolower($code)),
                    'password' => Hash::make('DeptAdmin@1234'),
                    'role' => 'department_admin',
                    'department_id' => $dept->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'can_verify' => true,
                ]
            );
            $users[] = ['Dept. Admin (' . ucfirst($code) . ')', $deptAdmin->full_name, $deptAdmin->email, $dept->name, 'DeptAdmin@1234'];
        }

        // ── VERIFIER ACCOUNTS (one per department) ──
        $verifierEmails = [
            'ENG' => 'verifier.eng@daetlistens.gov.ph',
            'OM'  => 'verifier.om@daetlistens.gov.ph',
            'MENRO' => 'verifier.menro@daetlistens.gov.ph',
        ];

        foreach ($verifierEmails as $code => $email) {
            $dept = Department::where('code', $code)->firstOrFail();
            $verifier = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'Verifier',
                    'last_name' => ucfirst($code),
                    'password' => Hash::make('Verifier@1234'),
                    'role' => 'staff',
                    'department_id' => $dept->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'can_verify' => true,
                ]
            );
            $users[] = ['Verifier (' . ucfirst($code) . ')', $verifier->full_name, $verifier->email, $dept->name, 'Verifier@1234'];
        }

        // ── FIELD STAFF ACCOUNTS (two per department) ──
        $staffMembers = [
            'ENG' => [
                ['firstName' => 'Juan', 'lastName' => 'Dela Cruz', 'email' => 'staff.eng.1@daetlistens.gov.ph'],
                ['firstName' => 'Maria', 'lastName' => 'Santos', 'email' => 'staff.eng.2@daetlistens.gov.ph'],
            ],
            'OM' => [
                ['firstName' => 'Ana', 'lastName' => 'Garcia', 'email' => 'staff.om.1@daetlistens.gov.ph'],
                ['firstName' => 'Carlos', 'lastName' => 'Reyes', 'email' => 'staff.om.2@daetlistens.gov.ph'],
            ],
            'MENRO' => [
                ['firstName' => 'Luis', 'lastName' => 'Vargas', 'email' => 'staff.menro.1@daetlistens.gov.ph'],
                ['firstName' => 'Patricia', 'lastName' => 'Cruz', 'email' => 'staff.menro.2@daetlistens.gov.ph'],
            ],
        ];

        foreach ($staffMembers as $code => $staffList) {
            $dept = Department::where('code', $code)->firstOrFail();
            foreach ($staffList as $staff) {
                $staffMember = User::firstOrCreate(
                    ['email' => $staff['email']],
                    [
                        'first_name' => $staff['firstName'],
                        'last_name' => $staff['lastName'],
                        'password' => Hash::make('Staff@1234'),
                        'role' => 'staff',
                        'department_id' => $dept->id,
                        'is_active' => true,
                        'email_verified_at' => now(),
                        'can_verify' => false,
                    ]
                );
                $users[] = ['Staff (' . $code . ')', $staffMember->full_name, $staffMember->email, $dept->name, 'Staff@1234'];
            }
        }

        // ── RESIDENT ACCOUNTS (5) ──
        // Each carries a barangay, which the complaint form prefills.
        // Names are presentation-friendly, not of anyone real.
        // Three of them are ID-verified; two are not, so the identity-gated
        // filing flow can be demonstrated from both sides.
        $residents = [
            ['firstName' => 'Aling', 'lastName' => 'Gomez', 'email' => 'aling@gmail.com', 'barangay' => 'Barangay VI (Centro)', 'identityStatus' => 'verified'],
            ['firstName' => 'Bela', 'lastName' => 'Cruz', 'email' => 'bela@gmail.com', 'barangay' => 'Barangay I (Hilahod)', 'identityStatus' => 'verified'],
            ['firstName' => 'Carlo', 'lastName' => 'Dela Cruz', 'email' => 'carlo@gmail.com', 'barangay' => 'Barangay IV (Mantagbac)', 'identityStatus' => 'verified'],
            ['firstName' => 'Dana', 'lastName' => 'Santos', 'email' => 'dana@gmail.com', 'barangay' => 'Barangay VIII (Salcedo)', 'identityStatus' => 'unverified'],
            ['firstName' => 'Eduardo', 'lastName' => 'Reyes', 'email' => 'eduardo@gmail.com', 'barangay' => 'Barangay V (Pandan)', 'identityStatus' => 'unverified'],
        ];

        foreach ($residents as $resident) {
            $citizen = User::firstOrCreate(
                ['email' => $resident['email']],
                [
                    'first_name' => $resident['firstName'],
                    'last_name' => $resident['lastName'],
                    'barangay' => $resident['barangay'],
                    'password' => Hash::make('Test@1234'),
                    'role' => 'citizen',
                    'department_id' => null,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'identity_status' => $resident['identityStatus'],
                    'identity_verified_at' => $resident['identityStatus'] === 'verified' ? now() : null,
                ]
            );

            if (blank($citizen->barangay)) {
                $citizen->forceFill(['barangay' => $resident['barangay']])->save();
            }

            if ($resident['identityStatus'] === 'verified') {
                $citizen->identityVerifications()->updateOrCreate(
                    ['user_id' => $citizen->id],
                    [
                        'status' => 'approved',
                        'id_type' => 'PhilID',
                        'id_number_hash' => hash('sha256', 'demo-id-'.$citizen->id),
                        'submitted_at' => now(),
                        'reviewed_at' => now(),
                        'reviewed_by' => $admin->id,
                        'reviewer_note' => 'Demo verification approved.',
                    ]
                );
            }

            $users[] = ['Resident', $citizen->full_name, $citizen->email, $resident['barangay'], 'Test@1234'];
        }

        $this->command->table(
            ['Access', 'Name', 'Email', 'Department / Barangay', 'Password'],
            $users
        );

        $this->command->info('User seeding completed!');
    }
}