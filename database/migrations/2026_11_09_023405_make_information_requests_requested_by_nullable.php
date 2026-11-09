<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy orphan asks (needs_information without a question) may be
     * materialised by the resident's reply without a known asker; the
     * blade falls back to "Asked by the department" for those.
     */
    public function up(): void
    {
        Schema::table('complaint_information_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->unsignedBigInteger('requested_by')->nullable()->change();
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaint_information_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->unsignedBigInteger('requested_by')->nullable(false)->change();
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
