<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('id_type', 40);
            $table->string('id_number_hash', 64)->nullable()->index();
            $table->string('id_front_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->timestamp('files_purged_at')->nullable();
            $table->foreignId('possible_duplicate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('reviewer_note')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['id_type', 'id_number_hash']);
            $table->index('possible_duplicate_user_id');
            $table->index('reviewed_by');
            $table->index('revoked_by');
        });

        // One pending submission per resident (partial unique index).
        \Illuminate\Support\Facades\DB::statement(
            "CREATE UNIQUE INDEX identity_verifications_one_pending_per_user
             ON identity_verifications (user_id)
             WHERE status = 'pending'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};
