<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the office first accepted a report.
 *
 * `reviewed_at` moves every time a review decision is made, so it cannot say
 * when the office took ownership. This is set once, on the first time the
 * report is verified, and it is what the work clock is measured from: from
 * the moment a report is accepted, the office owes the resident work.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            if (! Schema::hasColumn('complaints', 'verified_at')) {
                $table->timestamp('verified_at')
                    ->nullable()
                    ->after('reviewed_at')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            if (Schema::hasColumn('complaints', 'verified_at')) {
                $table->dropIndex(['verified_at']);
                $table->dropColumn('verified_at');
            }
        });
    }
};
