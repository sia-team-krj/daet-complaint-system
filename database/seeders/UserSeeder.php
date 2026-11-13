<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users: one administrator, one department
     * verifier, three staff accounts (one per office), and eight residents.
     *
     * Demo credentials (local/presentation only):
     *
     *   admin@daetlistens.gov.ph        Admin@1234    (System Administrator)
     *   verifier@daetlistens.gov.ph     Verifier@1234 (Engineering — can verify)
     *   engineering@daetlistens.gov.ph  Staff@1234    (Engineering field staff)
     *   mayor@daetlistens.gov.ph        Staff@1234    (Mayor's Office staff)
     *   menro@daetlistens.gov.ph        Staff@1234    (MENRO staff)
     *   Residents (8):                  Test@1234
     *   pedro@gmail.com · nena@gmail.com · jose@gmail.com · maria.santos@gmail.com
     *   rodel.villanueva@gmail.com · liza.ocampo@gmail.com · gabriel.reyes@gmail.com · carmen.delarosa@gmail.com
     */
    public function run(): void
    {
        $this->command->info('Seeding users...');

        $users = [];

        // ── ADMIN ACCOUNT ──
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
        $users[] = ['Admin', $admin->full_name, $admin->email, '—', 'Admin@1234'];

        // ── VERIFIER ACCOUNT ──
        // One verifier for the busiest office, Engineering, so the review
        // queue can be demonstrated end-to-end with a department-scoped
        // verifier. Mayor's Office and MENRO tickets rely on the admin's
        // verification powers — deliberately, so both paths are visible.
        $verifierDept = Department::where('code', 'ENG')->firstOrFail();
        $verifier = User::firstOrCreate(
            ['email' => 'verifier@daetlistens.gov.ph'],
            [
                'first_name' => 'Ramon',
                'last_name' => 'Villanueva',
                'password' => Hash::make('Verifier@1234'),
                'role' => 'staff',
                'department_id' => $verifierDept->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'can_verify' => true,
            ]
        );
        $users[] = ['Verifier (Engineering)', $verifier->full_name, $verifier->email, $verifierDept->name, 'Verifier@1234'];

        // ── STAFF ACCOUNTS (one per office) ──
        $staff = [
            ['Engineering Office', 'ENG', 'Juan', 'Dela Cruz', 'engineering@daetlistens.gov.ph'],
            ["Mayor's Office", 'OM', 'Ana', 'Garcia', 'mayor@daetlistens.gov.ph'],
            ['Municipal Environment and Natural Resource Office', 'MENRO', 'Carlos', 'Mendoza', 'menro@daetlistens.gov.ph'],
        ];

        foreach ($staff as [$deptName, $deptCode, $firstName, $lastName, $email]) {
            $department = Department::where('code', $deptCode)->firstOrFail();
            $staffMember = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('Staff@1234'),
                    'role' => 'staff',
                    'department_id' => $department->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    // Ordinary staff: they act on verified work but cannot
                    // verify. The verifier above holds the capability.
                    'can_verify' => false,
                ]
            );
            $users[] = ["Staff ({$deptCode})", $staffMember->full_name, $staffMember->email, $department->name, 'Staff@1234'];
        }

        // ── CITIZEN ACCOUNTS ──
        // Each carries a barangay, which the complaint form prefills.
        // Names are presentation-friendly, not of anyone real.
        $citizens = [
            ['Pedro', 'Penduko', 'pedro@gmail.com', 'Barangay VI (Centro)'],
            ['Nena', 'Cruz', 'nena@gmail.com', 'Barangay VI (Centro)'],
            ['Jose', 'Batungbakal', 'jose@gmail.com', 'Barangay VIII (Salcedo)'],
            ['Maria', 'Santos', 'maria.santos@gmail.com', 'Bagasbas'],
            ['Rodel', 'Villanueva', 'rodel.villanueva@gmail.com', 'Barangay I (Hilahod)'],
            ['Liza', 'Ocampo', 'liza.ocampo@gmail.com', 'Barangay IV (Mantagbac)'],
            ['Gabriel', 'Reyes', 'gabriel.reyes@gmail.com', 'Barangay V (Pandan)'],
            ['Carmen', 'Dela Rosa', 'carmen.delarosa@gmail.com', 'Cobangbang'],
        ];

        foreach ($citizens as [$firstName, $lastName, $email, $barangay]) {
            $citizen = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'barangay' => $barangay,
                    'password' => Hash::make('Test@1234'),
                    'role' => 'citizen',
                    'department_id' => null,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (blank($citizen->barangay)) {
                $citizen->forceFill(['barangay' => $barangay])->save();
            }

            $users[] = ['Resident', $citizen->full_name, $citizen->email, $barangay, 'Test@1234'];
        }

        $this->command->table(
            ['Access', 'Name', 'Email', 'Department / Barangay', 'Password'],
            $users
        );

        $this->command->info('User seeding completed!');
    }
}
