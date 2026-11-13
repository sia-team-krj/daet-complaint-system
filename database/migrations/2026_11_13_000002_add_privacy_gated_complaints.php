<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Privacy-gated complaints: the categories that name somebody.
 *
 * Two complaint categories name a party — a person (how you were treated at
 * the LGU) or a business (the plastic policy). For those, the name of the
 * party is captured in a field that never leaves the office, and the
 * complaint stays off the public transparency register until the office has
 * settled it, the information has been verified as true, the sensitive parts
 * have been redacted, and the Mayor's office has approved the publication.
 *
 * This migration adds the columns that carry that, and retires the noise
 * category — with the complaints filed under it — into the catch-all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // The named party. Never rendered publicly, never indexed, never
            // part of the public search text.
            $table->string('named_party', 150)->nullable()->after('department_id');
            $table->string('named_party_position', 150)->nullable()->after('named_party');

            // Publication workflow for the gated categories.
            $table->string('publication_status', 20)->nullable()->after('named_party_position');
            $table->timestamp('publication_requested_at')->nullable()->after('publication_status');
            $table->foreignId('publication_requested_by')
                ->nullable()->after('publication_requested_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('publication_decided_at')->nullable()->after('publication_requested_by');
            $table->foreignId('publication_decided_by')
                ->nullable()->after('publication_decided_at')
                ->constrained('users')->nullOnDelete();
            $table->text('publication_note')->nullable()->after('publication_decided_by');
        });

        $this->retireNoiseCategory();
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['publication_requested_by']);
            $table->dropForeign(['publication_decided_by']);
            $table->dropColumn([
                'named_party',
                'named_party_position',
                'publication_status',
                'publication_requested_at',
                'publication_requested_by',
                'publication_decided_at',
                'publication_decided_by',
                'publication_note',
            ]);
        });

        // The complaints moved out of the noise category cannot be put back:
        // which of them were noise is no longer recorded anywhere. They stay
        // as the catch-all, which is where they were routed anyway.
    }

    /**
     * The noise category is no longer offered, so the complaints filed under
     * it become the catch-all. The complaint log is immutable and keeps the
     * original wording; only the complaint's own category moves.
     */
    private function retireNoiseCategory(): void
    {
        DB::table('complaints')
            ->where('category', 'noise_complaint')
            ->update([
                'category' => 'others',
                'updated_at' => now(),
            ]);
    }
};
