<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The audit trail's residents scope filters on `actor_role`, and the trail is
 * always read newest-first. Without this index that scope scans the whole log
 * on every request; with it the scope reads the index and stops at 30 rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->index(['actor_role', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex(['actor_role', 'created_at']);
        });
    }
};
