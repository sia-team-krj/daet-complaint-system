<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cross-complaint attention feed. Deliberately not derived from
        // complaint_logs: that is the durable per-complaint record of what
        // happened, while this is transient and about "look at this".
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Event key, e.g. complaint.needs_verification. The sentence shown
            // to the reader is built at read time rather than snapshotted, so a
            // later status change cannot leave a stale claim on screen.
            $table->string('type', 48);
            $table->string('title');
            $table->string('body')->nullable();

            $table->string('subject_type', 48)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // The unread badge and the feed both read through this.
            $table->index(['user_id', 'read_at', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
