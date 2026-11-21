<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Resident trust/health score. Starts at 100; confirmed misleading/spam
            // reduces it, cleanly resolved/closed complaints increase it. Bounded 0-100.
            $table->unsignedSmallInteger('trust_score')->default(100)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trust_score');
        });
    }
};
