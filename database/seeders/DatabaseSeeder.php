<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            ComplaintCategorySeeder::class,
            UserSeeder::class,
            DemoComplaintSeeder::class,
            PreGeneratedComplaintSeeder::class,
            DemoNotificationSeeder::class,
            DemoFeedbackSeeder::class,
        ]);
    }
}
