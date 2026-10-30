<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records which barangay a complaint is about, as the resident stated it.
 *
 * Until now a complaint carried only a coordinate, and anything that needed to
 * name the place had to guess from a single reference point per barangay. That
 * guess is wrong whenever a point sits further from its own barangay's reference
 * point than from a neighbour's — which is most of the area of a large barangay,
 * and Daet is only about 10 km across. Residents were shown the wrong barangay
 * for where they lived.
 *
 * The resident knows which barangay they are in, and already told us at
 * registration. So the form now asks, and this column is the answer. The
 * coordinate is untouched and remains the exact position the department is given;
 * this is the human-scale name for the same place, and it is the resident's
 * rather than a guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Nullable so an in-flight row is not lost, and so a complaint filed
            // before this column existed still reads back.
            $table->string('barangay')->nullable()->after('address_text');
        });

        // Backfill from the resident's own registration. A guess would be
        // better than nothing for a historical row, but their own stated
        // barangay is both available and strictly better than our guess.
        DB::table('complaints')
            ->whereNull('barangay')
            ->whereNotNull('user_id')
            ->update([
                'barangay' => DB::table('users')
                    ->select('barangay')
                    ->whereColumn('users.id', 'complaints.user_id')
                    ->limit(1),
            ]);
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('barangay');
        });
    }
};
