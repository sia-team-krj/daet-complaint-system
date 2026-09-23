<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * RUN FIFTH — references both complaints and users.
     *
     * This table is the backbone of the Transparency feature.
     * Every single status change on a complaint creates one immutable row here.
     * It is the audit trail that citizens see as a timeline on their tracking page.
     *
     * IMMUTABILITY RULE: These rows are NEVER updated or deleted
     * (except via cascade when their parent complaint is soft-deleted).
     * Never call update() on ComplaintLog anywhere in the codebase.
     */
    public function up(): void
    {
        Schema::create("complaint_logs", function (Blueprint $table) {
            $table->id();

            // The complaint this log entry belongs to.
            // CASCADE: when a complaint is hard-deleted (which should never happen
            // in normal flow), its logs go with it. Soft deletes on complaints
            // protect logs from accidental removal.
            $table
                ->foreignId("complaint_id")
                ->constrained()
                ->onDelete("cascade");

            // The staff member or admin who made the status change.
            // SET NULL so the log survives even if the actor's account is removed.
            $table
                ->foreignId("actor_id")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");

            $table->string("previous_status", 50)->nullable(); // NULL only on the very first log entry
            $table->string("new_status", 50);
            $table->text("comment")->nullable(); // e.g., "Contractor dispatched to Brgy. Lag-on"

            // Only created_at — logs are immutable, they have no updated_at
            $table->timestamp("created_at")->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("complaint_logs");
    }
};
