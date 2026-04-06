<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            // Format: COMP-2026-00001 — generated in Complaint::booted(), NEVER Str::random()
            $table->string('ticket_id', 30)->unique()->index();

            // SET NULL — deleting a citizen must NOT destroy complaint records
            // These are permanent government accountability records
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->nullOnDelete();

            $table->string('category', 100);
            $table->string('title', 255);
            $table->text('description');

            // Stored in MinIO (S3-compatible local storage)
            $table->string('image_path', 500)->nullable();

            // Geotag via Leaflet.js on submission form
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('address_text', 500)->nullable();

            $table->enum('urgency', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');

            // Use ComplaintStatus enum in PHP — never raw strings
            $table->string('status', 50)->default('Submitted')->index();

            $table->tinyInteger('is_public')->default(0);

            $table->softDeletes(); // NEVER hard-delete complaints. Ever.
            $table->timestamps();

            // Index for faster queries on department + status
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};