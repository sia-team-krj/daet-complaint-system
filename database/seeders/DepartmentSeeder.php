<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * LGU Daet departments to seed.
     * Using firstOrCreate so this seeder is safe to re-run.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'GSO', 'name' => 'General Services Office', 'description' => 'Handles general municipal services and facility management'],
            ['code' => 'MPDO', 'name' => 'Municipal Planning & Dev. Office', 'description' => 'Urban planning and development coordination'],
            ['code' => 'MEO', 'name' => 'Municipal Engineering Office', 'description' => 'Roads, infrastructure, and public works'],
            ['code' => 'MHO', 'name' => 'Municipal Health Office', 'description' => 'Public health services and medical programs'],
            ['code' => 'SWMO', 'name' => 'Solid Waste Management Office', 'description' => 'Waste collection, disposal, and sanitation'],
            ['code' => 'MSWDO', 'name' => 'Municipal Social Welfare & Dev.', 'description' => 'Social services and welfare programs'],
            ['code' => 'BFP', 'name' => 'Bureau of Fire Protection', 'description' => 'Fire safety and emergency response'],
            ['code' => 'PNP', 'name' => 'Philippine National Police', 'description' => 'Public safety and law enforcement'],
            ['code' => 'MAO', 'name' => 'Municipal Agriculture Office', 'description' => 'Agricultural support and rural development'],
            ['code' => 'MENRO', 'name' => 'Municipal Environment & Natural Resources', 'description' => 'Environmental protection and natural resource management'],
            ['code' => 'OMR', 'name' => 'Office of the Mayor', 'description' => 'Executive office and general administration'],
            ['code' => 'MCR', 'name' => 'Municipal Civil Registrar', 'description' => 'Civil registry and vital statistics'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'is_active' => 1,
                ]
            );
        }
    }
}
