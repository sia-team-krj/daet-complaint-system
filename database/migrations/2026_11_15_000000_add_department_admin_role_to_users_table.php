<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the office-scoped Department Administrator role.
     *
     * The original column is a Postgres enum, which Laravel implements as a
     * named CHECK constraint. Adding a value means replacing that constraint;
     * there is no ALTER TYPE here because the column is not a native enum.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_role_check "
            ."CHECK (role IN ('citizen', 'staff', 'department_admin', 'admin'))"
        );
    }

    public function down(): void
    {
        // A Department Administrator is a staff member with extra office
        // powers. On rollback the safe state is the staff workspace, never a
        // citizen account and never an administrator.
        DB::table('users')
            ->where('role', 'department_admin')
            ->update(['role' => 'staff']);

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_role_check "
            ."CHECK (role IN ('citizen', 'staff', 'admin'))"
        );
    }
};
