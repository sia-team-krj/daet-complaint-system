<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seeds the real LGU department offices of Daet, Camarines Norte.
     * Run after migrations: php artisan db:seed --class=DepartmentSeeder
     */
    public function run(): void
    {
        $departments = [
            [
                "name" => "Engineering Office",
                "code" => "ENG",
                "description" =>
                    "Roads, drainage, bridges, streetlights, and public infrastructure.",
            ],
            [
                "name" => "Health Office",
                "code" => "HLTH",
                "description" =>
                    "Public health concerns, sanitation, and medical services.",
            ],
            [
                "name" => "General Services Office",
                "code" => "GSO",
                "description" =>
                    "Government facilities, equipment, and general public services.",
            ],
            [
                "name" => "Municipal Planning and Development Office",
                "code" => "MPDO",
                "description" =>
                    "Land use, urban planning, and development projects.",
            ],
            [
                "name" => "Waste Management Office",
                "code" => "WST",
                "description" =>
                    "Garbage collection, waste disposal, and environmental cleanliness.",
            ],
            [
                "name" => "Social Welfare and Development Office",
                "code" => "SWDO",
                "description" =>
                    "Social services, livelihood assistance, and welfare programs.",
            ],
            [
                "name" => "Business Permit and Licensing Office",
                "code" => "BPLO",
                "description" =>
                    "Business permits, licensing, and regulatory compliance.",
            ],
            [
                "name" => "Office of the Mayor",
                "code" => "OM",
                "description" =>
                    'Executive concerns escalated to the Mayor\'s office.',
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ["code" => $dept["code"]],
                array_merge($dept, ["is_active" => true]),
            );
        }
    }
}
