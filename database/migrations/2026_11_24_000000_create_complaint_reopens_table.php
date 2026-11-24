<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resident reopen events on an existing complaint.
     *
     * A reopen is the resident rejecting the office's resolution after it
     * has been submitted. The complaint row itself is unchanged (no new
     * ticket), so this table is the history of reopen events — one row per
     * reopening — while ComplaintLog keeps the audit trail visible in the
     * Updates timeline.
     */
    public function up(): void
    {
        Schema::create('complaint_reopens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamps();

            $table->index(['complaint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_reopens');
    }
};
