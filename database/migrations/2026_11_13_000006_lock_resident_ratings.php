<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A resident's rating is final.
 *
 * The page no longer offers a second rating for the same scope, and the service
 * refuses to overwrite the first. The database says the same thing with two
 * partial unique indexes — one for the portal (department_id null) and one for
 * each office — so two simultaneous submits cannot leave two ratings behind.
 *
 * Existing duplicate rows (only possible through an old race) keep the earliest
 * rating, because that is the one the resident gave first.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE FROM feedback
            WHERE kind = 'rating'
              AND id NOT IN (
                  SELECT MIN(id) FROM feedback
                  WHERE kind = 'rating'
                  GROUP BY user_id, department_id
              )
        SQL);

        DB::statement(
            "CREATE UNIQUE INDEX feedback_rating_portal_unique
             ON feedback (user_id)
             WHERE kind = 'rating' AND department_id IS NULL",
        );

        DB::statement(
            "CREATE UNIQUE INDEX feedback_rating_office_unique
             ON feedback (user_id, department_id)
             WHERE kind = 'rating' AND department_id IS NOT NULL",
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS feedback_rating_portal_unique');
        DB::statement('DROP INDEX IF EXISTS feedback_rating_office_unique');
    }
};
