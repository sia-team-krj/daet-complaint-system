<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Feedback for the presentation: published office ratings so the public
 * feedback walls and the admin feedback screen have content, one suggestion
 * the administration is meant to read, one withheld rating and one pending
 * suggestion so the moderation tabs are not empty.
 */
class DemoFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo feedback...');

        $admin = User::where('role', 'admin')->first();
        $eng = Department::where('code', 'ENG')->first();
        $om = Department::where('code', 'OM')->first();
        $menro = Department::where('code', 'MENRO')->first();

        $pedro = User::where('email', 'pedro@gmail.com')->first();
        $nena = User::where('email', 'nena@gmail.com')->first();
        $maria = User::where('email', 'maria.santos@gmail.com')->first();
        $rodel = User::where('email', 'rodel.villanueva@gmail.com')->first();
        $carmen = User::where('email', 'carmen.delarosa@gmail.com')->first();
        $liza = User::where('email', 'liza.ocampo@gmail.com')->first();
        $gabriel = User::where('email', 'gabriel.reyes@gmail.com')->first();

        $published = [
            // Ratings of the offices that handled the showcase tickets.
            [$pedro, $eng, 5, 'Mabilis mag-responde ang Engineering Office. Salamat po!', 25],
            [$nena, $eng, 4, 'Mabilis ang pag-ayos ng baha malapit sa eskwelahan.', 27],
            [$maria, $menro, 5, 'Ang linis na ng canal; salamat sa MENRO at sa barangay.', 19],
            [$rodel, $om, 4, 'Pinakinggan po kami ng Mayor\'s Office; salamat po.', 8],
            [$carmen, $menro, 3, 'Ayos naman po pero sana mas mabilis ang susunod na koleksyon.', 5],
        ];

        foreach ($published as [$user, $department, $rating, $body, $daysAgo]) {
            $feedback = Feedback::create([
                'user_id' => $user->id,
                'kind' => Feedback::KIND_RATING,
                'department_id' => $department?->id,
                'rating' => $rating,
                'body' => $body,
                'status' => 'published',
                'published_at' => now()->subDays($daysAgo),
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDays($daysAgo - 1),
            ]);
            $feedback->forceFill([
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ])->save();
        }

        // A portal-wide suggestion, published and visible.
        $suggestion = Feedback::create([
            'user_id' => $liza->id,
            'kind' => Feedback::KIND_SUGGESTION,
            'department_id' => null,
            'rating' => null,
            'body' => 'Sana magdagdag ng SMS alert kapag may update ang ticket, para hindi kailangan bumalik-balik sa website.',
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now()->subDays(2),
        ]);
        $suggestion->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ])->save();

        // A withheld rating (read and deliberately kept off the register) and
        // a pending suggestion (waiting on the administration to read it).
        $withheld = Feedback::create([
            'user_id' => $gabriel->id,
            'kind' => Feedback::KIND_RATING,
            'department_id' => $menro?->id,
            'rating' => 1,
            'body' => 'This is not feedback, just venting about a personal matter. Please ignore.',
            'status' => 'withheld',
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now()->subDays(1),
        ]);
        $withheld->forceFill([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(1),
        ])->save();

        $pending = Feedback::create([
            'user_id' => $pedro->id,
            'kind' => Feedback::KIND_SUGGESTION,
            'department_id' => null,
            'rating' => null,
            'body' => 'Sana magdagdag ng dark mode para sa mga nag-oobserba sa gabi.',
            'status' => 'pending',
        ]);
        $pending->forceFill([
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ])->save();

        $this->command->info('Demo feedback completed (8 entries).');
    }
}
