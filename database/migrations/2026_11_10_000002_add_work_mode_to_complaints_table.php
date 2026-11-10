<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freezes the kind of work a complaint is onto the complaint itself.
 *
 * The kind of work decides what counts as proof: a repair is proven by a
 * photograph of it, an enforcement action by a written record. That kind is
 * resolved from the category (config/complaint_work_policy.php) the first
 * time anyone acts on the ticket, and frozen here so a later edit to the
 * policy never changes the rules on a complaint that is already in flight.
 *
 * An office may override it for a single complaint; the override is written
 * to the same column and recorded in the audit trail.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            if (! Schema::hasColumn('complaints', 'work_mode')) {
                $table->string('work_mode', 32)
                    ->nullable()
                    ->after('intake_mode')
                    ->comment('labor_required | report_only | referral');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            if (Schema::hasColumn('complaints', 'work_mode')) {
                $table->dropColumn('work_mode');
            }
        });
    }
};
