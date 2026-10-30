<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users (admin, staff, and sample citizens).
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
        $users[] = ['Admin', $admin->full_name, $admin->email, '—'];

        // ── STAFF ACCOUNTS ──
        // Staff mapping: slug => [name, dept_code]
        $staffMapping = [
            'engineering' => ['Juan dela Cruz', 'ENG'],
            'gso' => ['Maria Santos', 'GSO'],
            'permits' => ['Roberto Reyes', 'BPLO'],
            'peaceorder' => ['Ana Garcia', 'SWDO'],
            'agriculture' => ['Carlos Mendoza', 'WST'],
        ];

        foreach ($staffMapping as $slug => [$name, $deptCode]) {
            [$firstName, $lastName] = explode(' ', $name, 2);
            $email = $slug . '@daetlistens.gov.ph';

            $department = Department::where('code', $deptCode)->first();

            $staff = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('Staff@1234'),
                    'role' => 'staff',
                    'department_id' => $department?->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    // One verifier per department so the review queue is never
                    // stranded. Verification is a capability, so this person is
                    // still an ordinary staff member who can also be assigned work.
                    'can_verify' => true,
                ]
            );

            $users[] = ['Staff', $staff->full_name, $staff->email, $department?->name ?? 'Unknown'];
        }

        // ── CITIZEN ACCOUNTS ──
        // Each carries a barangay because the complaint form prefills itself
        // from it, and an account with a blank one opens the form looking
        // broken — the field is required, and the resident is shown an empty
        // box for something they have already told us.
        $citizens = [
            ['Pedro', 'Penduko', 'pedro@gmail.com', 'Barangay VI (Centro)'],
            ['Nena', 'Cruz', 'nena@gmail.com', 'Barangay I (Hilahod)'],
            ['Jose', 'Batungbakal', 'jose@gmail.com', 'Bagasbas'],
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

            // firstOrCreate leaves an existing row untouched, so an account
            // seeded before barangays were recorded keeps whatever it had,
            // including nothing at all.
            if (blank($citizen->barangay)) {
                $citizen->forceFill(['barangay' => $barangay])->save();
            }
            
            $users[] = ['Citizen', $citizen->full_name, $citizen->email, '—'];
        }

        // ── PRINT SUMMARY TABLE ──
        $this->command->table(
            ['Role', 'Name', 'Email', 'Department'],
            $users
        );

        $this->command->info('User seeding completed!');
    }
}
