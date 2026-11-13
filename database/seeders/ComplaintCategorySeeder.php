<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;

/**
 * The seven categories the current system supports, with their Filipino
 * labels, so the admin category settings screen has content to manage from a
 * fresh database.
 */
class ComplaintCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding complaint categories...');

        $categories = [
            ['road_damage', 'Road Damage', 'Pinsala sa Daan', 1],
            ['flooding', 'Flooding / Drainage', 'Pagbaha at Drainage', 2],
            ['streetlight', 'Streetlight Issues', 'Sirang Ilaw sa Kalye', 3],
            ['garbage', 'Garbage Collection', 'Koleksyon ng Basura', 4],
            ['sanitation', 'Sanitation', 'Sanitasyon', 5],
            ['plastic_usage', 'Plastic Product Usage', 'Paggamit ng Plastik', 6],
            ['others', 'Other Concerns', 'Iba pang Suliranin', 7],
        ];

        foreach ($categories as [$key, $labelEn, $labelFil, $sortOrder]) {
            ComplaintCategory::updateOrCreate(
                ['key' => $key],
                [
                    'label_en' => $labelEn,
                    'label_fil' => $labelFil,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }

        $this->command->info('Complaint categories seeded.');
    }
}
