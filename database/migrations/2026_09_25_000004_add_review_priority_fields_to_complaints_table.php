<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            $table->string('suggested_priority', 20)->default('routine')->index();
            $table->string('confirmed_priority', 20)->nullable()->index();
            $table->string('review_status', 30)->default('pending')->index();
            $table->json('suggestion_reasons')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
        });

        // Existing records predate the review workflow. Treat them as
        // historical/verified records so current public data is not lost;
        // every new complaint created by the intake flow starts pending.
        $legacyPriorities = [
            'Low' => 'routine',
            'Medium' => 'elevated',
            'High' => 'urgent',
            'Urgent' => 'critical',
        ];

        DB::table('complaints')
            ->select(['id', 'urgency'])
            ->orderBy('id')
            ->get()
            ->each(function (object $complaint) use ($legacyPriorities): void {
                $priority = $legacyPriorities[$complaint->urgency] ?? 'routine';

                DB::table('complaints')
                    ->where('id', $complaint->id)
                    ->update([
                        'suggested_priority' => $priority,
                        'confirmed_priority' => $priority,
                        'review_status' => 'verified',
                        'reviewed_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'suggested_priority',
                'confirmed_priority',
                'review_status',
                'suggestion_reasons',
                'reviewed_at',
                'review_notes',
            ]);
        });
    }
};
