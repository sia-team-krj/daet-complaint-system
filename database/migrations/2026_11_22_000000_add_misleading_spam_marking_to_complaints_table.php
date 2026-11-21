<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Office staff mark a report as misleading or spam. This is the
            // office's own rejection of the report: the status moves to
            // ComplaintStatus::Rejected and the marking — plus the reason and
            // the office's comment — is recorded for review and management.
            $table->string('marking')->nullable();
            $table->text('mark_description')->nullable();
            $table->text('mark_comment')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_by');
            $table->dropColumn(['marking', 'mark_description', 'mark_comment', 'marked_at']);
        });
    }
};
