<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            UserSeeder::class,
            DemoComplaintSeeder::class,
            PreGeneratedComplaintSeeder::class,
            // Runs last: it attaches notifications to complaints and staff that
            // the earlier seeders create.
            DemoNotificationSeeder::class,
        ]);
    }
}
