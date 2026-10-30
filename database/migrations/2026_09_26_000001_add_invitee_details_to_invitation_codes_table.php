<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->string('invitee_name')->nullable()->after('code');
            $table->string('invitee_email')->nullable()->after('invitee_name');
        });

        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('expires_at');
        });

        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->index('invitee_email');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->dropIndex(['invitee_email']);
        });

        Schema::table('invitation_codes', function (Blueprint $table) {
            $table->dropColumn(['invitee_name', 'invitee_email', 'emailed_at']);
        });
    }
};
