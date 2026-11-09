<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks when a report reached "Resolved" and when it was closed, so the
     * resident can confirm closure and the system can auto-close after 7 days
     * of inaction. `closed_by` records who/what closed it (resident, staff,
     * admin, system) for the audit trail without inventing a new status.
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            $table->timestamp('resolved_at')->nullable()->after('status')->index();
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->string('closed_by', 20)->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            $table->dropIndex(['resolved_at']);
            $table->dropColumn(['resolved_at', 'closed_at', 'closed_by']);
        });
    }
};
