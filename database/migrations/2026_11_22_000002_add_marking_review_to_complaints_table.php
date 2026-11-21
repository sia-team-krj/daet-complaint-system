<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // A misleading/spam marking is reviewed by administration before it
            // counts against the resident's trust score. Confirmed = marking upheld;
            // dismissed = marking cleared as a false positive.
            $table->timestamp('marking_confirmed_at')->nullable()->after('marked_at');
            $table->foreignId('marking_confirmed_by')->nullable()->after('marking_confirmed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('marking_dismissed_at')->nullable()->after('marking_confirmed_by');
            $table->foreignId('marking_dismissed_by')->nullable()->after('marking_dismissed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marking_dismissed_by');
            $table->dropColumn('marking_dismissed_at');
            $table->dropConstrainedForeignId('marking_confirmed_by');
            $table->dropColumn('marking_confirmed_at');
        });
    }
};
