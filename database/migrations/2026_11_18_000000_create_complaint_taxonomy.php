<?php

use Database\Seeders\Support\ComplaintTaxonomyDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the complaint taxonomy editable.
 *
 * Until now the resident wizard was driven by config files while
 * `complaint_categories` only fed the settings screen. This migration gives
 * the table the rest of the story — a group, a receiving office, localized
 * helper text and the presentation/intake choices — then backfills the
 * taxonomy residents already see so the wizard does not change under them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('complaint_category_groups')) {
            Schema::create('complaint_category_groups', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label_en');
                $table->string('label_fil')->nullable();
                $table->string('helper_en')->nullable();
                $table->string('helper_fil')->nullable();
                $table->string('icon')->default('circle-help');
                $table->string('color')->default('other');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        Schema::table('complaint_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('complaint_categories', 'group_id')) {
                $table->foreignId('group_id')
                    ->nullable()
                    ->after('key')
                    ->constrained('complaint_category_groups')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('complaint_categories', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('group_id')
                    ->constrained('departments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('complaint_categories', 'helper_en')) {
                $table->string('helper_en')->nullable()->after('label_fil');
            }

            if (! Schema::hasColumn('complaint_categories', 'helper_fil')) {
                $table->string('helper_fil')->nullable()->after('helper_en');
            }

            if (! Schema::hasColumn('complaint_categories', 'icon')) {
                $table->string('icon')->default('circle-help')->after('helper_fil');
            }

            if (! Schema::hasColumn('complaint_categories', 'color')) {
                $table->string('color')->default('other')->after('icon');
            }

            if (! Schema::hasColumn('complaint_categories', 'intake_mode')) {
                $table->string('intake_mode')->default('physical_required')->after('color');
            }

            $table->index(['group_id', 'sort_order']);
            $table->index('department_id');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('complaint_categories', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'group_id',
                'department_id',
                'helper_en',
                'helper_fil',
                'icon',
                'color',
                'intake_mode',
            ]);
        });

        Schema::dropIfExists('complaint_category_groups');
    }

    /**
     * Seed the taxonomy residents already know, preserving any category the
     * municipality created through the old settings screen. A category that
     * does not match a default is attached to "Other Concerns" and the
     * fallback office, so it remains valid and visible instead of silently
     * disappearing from the wizard.
     */
    private function backfill(): void
    {
        $now = now();

        foreach (ComplaintTaxonomyDefaults::groups() as $key => $group) {
            if (DB::table('complaint_category_groups')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('complaint_category_groups')->insert([
                'key' => $key,
                ...$group,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $groupIds = DB::table('complaint_category_groups')->pluck('id', 'key');
        $departmentIds = DB::table('departments')->pluck('id', 'code');
        $fallbackDepartmentId = $departmentIds[ComplaintTaxonomyDefaults::fallbackDepartmentCode()]
            ?? DB::table('departments')->where('is_active', true)->value('id');
        $otherGroupId = $groupIds['other'] ?? DB::table('complaint_category_groups')->value('id');

        $existing = DB::table('complaint_categories')->get()->keyBy('key');
        $defaults = ComplaintTaxonomyDefaults::categories();
        $fallback = ComplaintTaxonomyDefaults::fallbackCategory();

        foreach ($defaults as $key => $category) {
            $groupId = $groupIds[$category['group']] ?? $otherGroupId;
            $departmentId = $departmentIds[$category['department_code']] ?? $fallbackDepartmentId;

            if ($existing->has($key)) {
                // Existing rows keep their labels, order and active flag: an
                // administrator may already have edited them. Only the new
                // columns are filled in.
                DB::table('complaint_categories')
                    ->where('key', $key)
                    ->update([
                        'group_id' => $groupId,
                        'department_id' => $departmentId,
                        'helper_en' => $category['helper_en'],
                        'helper_fil' => $category['helper_fil'],
                        'icon' => $category['icon'],
                        'color' => $category['color'],
                        'intake_mode' => $category['intake_mode'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('complaint_categories')->insert([
                'key' => $key,
                'group_id' => $groupId,
                'department_id' => $departmentId,
                'label_en' => $category['label_en'],
                'label_fil' => $category['label_fil'],
                'helper_en' => $category['helper_en'],
                'helper_fil' => $category['helper_fil'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'intake_mode' => $category['intake_mode'],
                'is_active' => $category['is_active'],
                'sort_order' => $category['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Anything the old settings screen created, or a row imported from
        // elsewhere, joins the fallback group and office. It stays active
        // exactly as it was.
        DB::table('complaint_categories')
            ->whereNull('group_id')
            ->update(['group_id' => $otherGroupId, 'updated_at' => $now]);

        DB::table('complaint_categories')
            ->whereNull('department_id')
            ->update(['department_id' => $fallbackDepartmentId, 'updated_at' => $now]);

        DB::table('complaint_categories')
            ->whereNull('icon')
            ->update(['icon' => $fallback['icon'], 'updated_at' => $now]);

        DB::table('complaint_categories')
            ->whereNull('color')
            ->update(['color' => $fallback['color'], 'updated_at' => $now]);

        DB::table('complaint_categories')
            ->whereNull('intake_mode')
            ->update(['intake_mode' => $fallback['intake_mode'], 'updated_at' => $now]);
    }
};
