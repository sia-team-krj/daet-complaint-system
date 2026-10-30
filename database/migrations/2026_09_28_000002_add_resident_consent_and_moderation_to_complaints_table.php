<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Splits "may this appear on the transparency page?" into two independent
 * decisions that two different people own.
 *
 * Before this, `is_public` was a single flag that staff and admins set during
 * review, so the visibility of a citizen's complaint was decided by the office
 * handling it — the opposite of what a transparency commitment requires.
 *
 * Now:
 *   - `is_public` is the resident's consent and belongs to the resident alone.
 *     It already existed with a false default; nothing about it moves except who
 *     is allowed to write it.
 *   - the columns below are moderation, and belong to staff and admins. They
 *     exist for content that is foul, fraudulent, or sensitive — a distinct
 *     concern from whether someone consented to publication.
 *
 * Redaction is stored as a replacement rather than a flag so "not redacted" is
 * unambiguously NULL, and so a partial redaction cannot be confused with a
 * wholesale one. Withheld photos are tracked by path because a complaint can
 * carry up to five and only some of them may be sensitive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->boolean('hidden_from_public')->default(false)->after('is_public');

            $table->text('redacted_title')->nullable()->after('hidden_from_public');
            $table->text('redacted_description')->nullable()->after('redacted_title');
            $table->json('withheld_image_paths')->nullable()->after('redacted_description');

            // Recorded so a moderation decision is explainable and reversible
            // rather than a silent deletion from the public record.
            $table->text('moderation_reason')->nullable()->after('withheld_image_paths');
            $table->foreignId('moderated_by')->nullable()->after('moderation_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropColumn([
                'hidden_from_public',
                'redacted_title',
                'redacted_description',
                'withheld_image_paths',
                'moderation_reason',
                'moderated_by',
                'moderated_at',
            ]);
        });
    }
};
