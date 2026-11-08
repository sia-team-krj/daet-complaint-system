<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('complaint_categories')) {
            Schema::create('complaint_categories', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label_en');
                $table->string('label_fil')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'text_size')) {
                $table->string('text_size', 10)->default('normal')->after('locale');
            }
            if (!Schema::hasColumn('users', 'high_contrast')) {
                $table->boolean('high_contrast')->default(false)->after('text_size');
            }
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('high_contrast');
            }
            if (!Schema::hasColumn('users', 'default_queue_view')) {
                $table->string('default_queue_view', 50)->nullable()->after('notification_preferences');
            }
            if (!Schema::hasColumn('users', 'daily_digest')) {
                $table->boolean('daily_digest')->default(false)->after('default_queue_view');
            }
        });

        // Make locale nullable so "no personal choice" can fall back to the
        // system default (admin/staff requirement).
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('locale', 8)->nullable()->default(null)->change();
            });
        } catch (\Throwable $e) {
            // Column change may fail on some drivers; nullability is handled
            // in the middleware by treating the DB default as unset only
            // for new rows. Non-fatal.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_categories');
        Schema::dropIfExists('system_settings');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['text_size', 'high_contrast', 'notification_preferences', 'default_queue_view', 'daily_digest']);
        });
    }
};
