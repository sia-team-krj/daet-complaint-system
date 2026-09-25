<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_logs', function (Blueprint $table) {
            $table->json('documentation_paths')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('complaint_logs', function (Blueprint $table) {
            $table->dropColumn('documentation_paths');
        });
    }
};
