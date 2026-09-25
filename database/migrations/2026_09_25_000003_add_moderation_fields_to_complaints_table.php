<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('content_fingerprint', 64)->nullable()->index();
            $table->string('spam_status', 20)->default('clear')->index();
            $table->unsignedSmallInteger('spam_score')->default(0);
            $table->json('spam_reasons')->nullable();
            $table->decimal('similarity_score', 5, 2)->default(0);
            $table->foreignId('duplicate_of_id')
                ->nullable()
                ->constrained('complaints')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropColumn([
                'content_fingerprint',
                'spam_status',
                'spam_score',
                'spam_reasons',
                'similarity_score',
            ]);
        });
    }
};
