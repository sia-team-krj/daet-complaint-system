<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A complaint that is on hold.
 *
 * Some complaints cannot be finished today for reasons that are not the
 * office's doing: the materials are not in stock, the budget has not been
 * released, the work belongs to another body. The office records that, tells
 * the resident, and carries on — and the deadline it owes pauses while it
 * waits, because a budget gap is not a failure to work.
 *
 * The hold is a marker on the complaint, not a status: the work has not
 * stopped, it is paused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Why the work is paused. Null while the complaint is not on hold.
            $table->string('hold_reason', 40)->nullable()->after('work_mode');

            // What the office tells the resident about it. This is the
            // public explanation, and it is what the resident reads.
            $table->text('hold_note')->nullable()->after('hold_reason');

            // What the office tells itself. Never shown to the resident.
            $table->text('hold_internal_note')->nullable()->after('hold_note');

            // When the office expects to be able to carry on.
            $table->date('hold_expected_resume_at')->nullable()->after('hold_internal_note');

            // When the hold started and who put it there.
            $table->timestamp('hold_started_at')->nullable()->after('hold_expected_resume_at');
            $table->foreignId('hold_started_by')
                ->nullable()->after('hold_started_at')
                ->constrained('users')->nullOnDelete();

            // How long the complaint has been held, in total. The deadline
            // the office owes is pushed out by exactly this much, so the
            // wait does not count against it.
            $table->integer('hold_total_minutes')->default(0)->after('hold_started_by');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['hold_started_by']);
            $table->dropColumn([
                'hold_reason',
                'hold_note',
                'hold_internal_note',
                'hold_expected_resume_at',
                'hold_started_at',
                'hold_started_by',
                'hold_total_minutes',
            ]);
        });
    }
};
