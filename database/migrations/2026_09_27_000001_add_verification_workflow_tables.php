<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A verifier is a capability on a staff account, not a new global role.
        // The flag keeps them inside the department queue while letting them
        // write review_status, which only a global admin can do today.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_verify')->default(false)->after('is_active');
        });

        Schema::table('complaints', function (Blueprint $table) {
            // Who is checking the report. Deliberately distinct from
            // assigned_staff_id, which means who does the physical work: if one
            // column served both, the verifier would always be the field worker
            // and the separation of duties would be void while looking intact.
            $table->foreignId('verifying_staff_id')
                ->nullable()
                ->after('assigned_staff_id')
                ->constrained('users')
                ->nullOnDelete();

            // How the department settled it. Published on the public register so
            // "reviewed" and "attended" are never conflated.
            $table->string('verification_method', 24)
                ->nullable()
                ->after('verifying_staff_id');
        });

        Schema::create('complaint_information_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->text('question');

            $table->string('status', 16)->default('open');
            $table->unsignedTinyInteger('round')->default(1);

            $table->text('response_text')->nullable();
            $table->json('response_image_paths')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['complaint_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_information_requests');

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verifying_staff_id');
            $table->dropColumn('verification_method');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_verify');
        });
    }
};
