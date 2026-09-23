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
            ]
        );
        $users[] = ['Admin', $admin->full_name, $admin->email, '—'];

        // ── STAFF ACCOUNTS ──
        // Staff mapping: slug => [name, dept_code]
        $staffMapping = [
            'engineering' => ['Juan dela Cruz', 'ENGR'],
            'gso' => ['Maria Santos', 'GSO'],
            'permits' => ['Roberto Reyes', 'BPLS'],
            'peaceorder' => ['Ana Garcia', 'PNP'],
            'agriculture' => ['Carlos Mendoza', 'MAO'],
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
                ]
            );
            
            $users[] = ['Staff', $staff->full_name, $staff->email, $department?->name ?? 'Unknown'];
        }

        // ── CITIZEN ACCOUNTS ──
        $citizens = [
            ['Pedro', 'Penduko', 'pedro@gmail.com'],
            ['Nena', 'Cruz', 'nena@gmail.com'],
            ['Jose', 'Batungbakal', 'jose@gmail.com'],
        ];

        foreach ($citizens as [$firstName, $lastName, $email]) {
            $citizen = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make('Test@1234'),
                    'role' => 'citizen',
                    'department_id' => null,
                ]
            );
            
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
