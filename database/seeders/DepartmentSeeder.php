<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seeds the real LGU department offices of Daet, Camarines Norte.
     * Run after migrations: php artisan db:seed --class=DepartmentSeeder
     *
     * Three offices. The municipality closed the Business and Licensing
     * Office and the Human Resource Management Office, and folded Health,
     * General Services, Municipal Planning and Development, and Social
     * Welfare and Development into the offices below.
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
                "name" => "Mayor's Office",
                "code" => "OM",
                "description" =>
                    "Municipal Information Office — public information, announcements, and community concerns escalated to the Mayor.",
            ],
            [
                "name" => "Municipal Environment and Natural Resource Office",
                "code" => "MENRO",
                "description" =>
                    "Garbage collection, waste disposal, environmental cleanliness, and plastic policy enforcement.",
            ],
        ];

        foreach ($departments as $dept) {
            // updateOrCreate, not firstOrCreate: the office list is the
            // canonical directory, so re-seeding has to bring a renamed office
            // back to its canonical name instead of silently keeping the old
            // one.
            Department::updateOrCreate(
                ["code" => $dept["code"]],
                array_merge($dept, ["is_active" => true]),
            );
        }
    }
}
