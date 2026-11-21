<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->timestamp('spam_confirmed_at')->nullable()->after('spam_reasons');
            $table->foreignId('spam_confirmed_by')->nullable()->after('spam_confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spam_confirmed_by');
            $table->dropColumn('spam_confirmed_at');
        });
    }
};
