<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A note that changes nothing.
 *
 * A complaint log records what happened to a complaint. Most of the time
 * that is a status change, and the status after it is what the column holds.
 * But an office can also say something without changing anything — "the
 * asphalt is not in stock, the schedule is for next quarter" — and that note
 * changes no status at all.
 *
 * Writing the status into the row anyway would put a false claim in the
 * immutable record: it would read as a transition that never happened. So
 * the column becomes nullable, and a note-only entry leaves it empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_logs', function (Blueprint $table) {
            $table->string('new_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        // A note-only entry has no status to restore, so this only works when
        // there are none. Any that exist are given the status of the complaint
        // they belong to, which is the closest true thing.
        DB::table('complaint_logs')
            ->whereNull('new_status')
            ->update([
                'new_status' => DB::table('complaints')
                    ->select('status')
                    ->whereColumn('complaints.id', 'complaint_logs.complaint_id')
                    ->limit(1),
            ]);

        Schema::table('complaint_logs', function (Blueprint $table) {
            $table->string('new_status')->nullable(false)->change();
        });
    }
};
