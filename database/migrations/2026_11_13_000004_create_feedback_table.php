<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resident feedback.
 *
 * Two things in one table, because they are two different things with two
 * different lives: a rating, which counts from the moment it is given, and a
 * suggestion, which is read by the administration before anybody else sees it.
 *
 * The scope of a rating is the portal itself (department_id null) or one
 * office. A resident rates the offices they have actually dealt with, so the
 * numbers mean something.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // rating | suggestion
            $table->string('kind', 20);

            // Null for the portal itself; set when an office is rated.
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            // 1 to 5. Null on a suggestion.
            $table->unsignedTinyInteger('rating')->nullable();

            // The suggestion itself. Null on a rating.
            $table->text('body')->nullable();

            // published | pending | withheld
            $table->string('status', 20)->default('published');

            $table->timestamp('published_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['kind', 'status']);
            $table->index(['department_id', 'kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
