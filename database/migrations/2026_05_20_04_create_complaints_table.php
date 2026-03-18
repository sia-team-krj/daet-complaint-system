<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * RUN FOURTH — references both users and departments.
     */
    public function up(): void
    {
        Schema::create("complaints", function (Blueprint $table) {
            $table->id();

            // Public-facing ticket ID e.g. COMP-2026-00001
            // Indexed because the public tracking page queries by this column constantly.
            // Generated in the Complaint model's booted() hook — NOT by the DB.
            $table->string("ticket_id", 30)->unique()->index();

            // The citizen who filed this complaint.
            // IMPORTANT: onDelete SET NULL, not CASCADE.
            // If a user deletes their account, the complaint record MUST survive —
            // it is a government accountability record. Cascading would destroy it.
            $table
                ->foreignId("user_id")
                ->nullable()
                ->constrained()
                ->onDelete("set null");

            // The department responsible for resolving this complaint.
            // Nullable because auto-routing assigns it after submission.
            // A complaint can briefly be unassigned (NULL) before routing runs.
            $table
                ->foreignId("department_id")
                ->nullable()
                ->constrained()
                ->onDelete("set null");

            // Complaint content
            $table->string("category", 100); // e.g., Infrastructure, Health, Waste
            $table->string("title", 255);
            $table->text("description");

            // Photo evidence — stored in MinIO via Storage::disk('s3')
            // Stores the full path e.g. complaints/2026/01/photo.jpg
            $table->string("image_path", 500)->nullable();

            // Geotagging — captured from browser Geolocation API or map picker
            $table->decimal("latitude", 10, 8)->nullable();
            $table->decimal("longitude", 11, 8)->nullable();
            $table->string("address_text", 500)->nullable(); // human-readable address

            // Classification
            $table
                ->enum("urgency", ["Low", "Medium", "High", "Urgent"])
                ->default("Medium");

            // Status — using varchar, not enum, so we can add stages without a migration.
            // Enforced at the application layer via App\Enums\ComplaintStatus.
            // Valid values: Submitted, Under Review, In Progress, Resolved, Rejected, Closed
            $table->string("status", 50)->default("Submitted")->index();

            // If true, this complaint appears on the public transparency dashboard.
            // Staff or admin sets this after reviewing the complaint.
            $table->boolean("is_public")->default(false);

            $table->timestamps();

            // Soft deletes — complaints are government records.
            // They must NEVER be permanently deleted. Soft delete only.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("complaints");
    }
};
