<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * RUN — a finished complaint may accept a follow-up sub-ticket from its
     * owner. A sub-ticket is a child of the complaint it follows, so the
     * linkage is a self-foreign key on complaints, exactly like duplicate_of_id.
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('duplicate_of_id')
                ->constrained('complaints')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
