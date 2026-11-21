<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Curated demo mode: the presentation dataset is intentionally small and
 * story-driven, so the large pre-generated volume seeder is a no-op.
 */
class PreGeneratedComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Pre-generated volume complaints skipped (curated demo dataset).');
    }
}
