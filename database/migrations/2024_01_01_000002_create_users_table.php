<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();

            // PH mobile numbers: 09171234567 = 11 digits. Old migration had varchar(10) — WRONG.
            $table->string('contact_number', 11)->nullable();

            $table->string('barangay', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Role-based access. 'citizen' | 'staff' | 'admin'
            // The old add_is_admin boolean migration is RETIRED. Do not use is_admin anywhere.
            $table->enum('role', ['citizen', 'staff', 'admin'])->default('citizen');

            // Staff and admin belong to a department. Citizens are NULL.
            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->nullOnDelete();

            // Anonymity system — display alias shown publicly instead of real name
            $table->string('display_alias', 50)->nullable();
            $table->tinyInteger('prefers_anonymity')->default(0);

            $table->rememberToken();
            $table->softDeletes(); // NEVER hard-delete users
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};