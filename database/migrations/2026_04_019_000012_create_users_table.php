<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * THE ONE AND ONLY users migration.
     * Replaces all old user migrations including any add_is_admin file.
     *
     * PostgreSQL note: enum() works fine on PostgreSQL via Laravel's
     * Blueprint — it creates a CHECK constraint instead of a MySQL ENUM type.
     * No changes needed between MySQL and PostgreSQL for this syntax.
     */
    public function up(): void
    {
        Schema::create("users", function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string("first_name", 100);
            $table->string("last_name", 100);
            $table->string("email")->unique();

            // PH mobile numbers are 11 digits (e.g., 09171234567)
            // Register form field is named 'phone' — map it to contact_number in the controller
            $table->string("contact_number", 11)->nullable();

            // Validated against User::BARANGAYS constant in the model
            $table->string("barangay", 100)->nullable();

            // Auth
            $table->timestamp("email_verified_at")->nullable();
            $table->string("password");
            $table->rememberToken();

            // Role — replaces the old is_admin boolean
            // citizen → regular Daet resident
            // staff   → LGU department employee, linked to a department
            // admin   → master admin, full system access
            $table
                ->enum("role", ["citizen", "staff", "admin"])
                ->default("citizen");

            // NULL for citizens, set for staff/admin
            $table
                ->foreignId("department_id")
                ->nullable()
                ->constrained() // references departments(id) — departments must exist first
                ->onDelete("set null");

            // Anonymity system
            $table->string("display_alias", 50)->nullable(); // e.g. Citizen-Daet-402
            $table->boolean("prefers_anonymity")->default(false);

            $table->timestamps();

            // Soft deletes — users must NEVER be hard-deleted.
            // Complaints reference user_id for accountability records.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("users");
    }
};
