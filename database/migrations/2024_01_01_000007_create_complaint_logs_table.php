<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('complaint_logs', function (Blueprint $table) {
            $table->id();

            // CASCADE — if a complaint is hard-deleted (which should NEVER happen),
            // its logs go with it. Complaints use softDeletes so this is a safety net only.
            $table->foreignId('complaint_id')
                  ->constrained('complaints')
                  ->cascadeOnDelete();

            // SET NULL — actor leaving the org must not destroy the log record
            $table->foreignId('actor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('previous_status', 50)->nullable(); // null on first 'Submitted' log
            $table->string('new_status', 50);
            $table->text('comment')->nullable(); // REQUIRED when new_status = 'Rejected'

            // Only created_at — NO updated_at. Logs are IMMUTABLE records.
            // NEVER call update() or delete() on ComplaintLog rows.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_logs');
    }
};