<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per piece of proof.
 *
 * A complaint log carries a list of photo paths. That list cannot say where a
 * photo was taken, when it was taken, who took it, or what it shows — and the
 * location is the whole point of a photograph of the work: it is what proves
 * the crew was at the reported site.
 *
 * This table is the satellite of complaint_logs. One row per photograph, each
 * with its own location, capture time and caption, so "where was this taken"
 * has an answer. The path list on the log stays where it is, so nothing that
 * already reads a log's photographs has to change.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('complaint_evidences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();

            // The status update this photograph belongs to.
            $table->foreignId('log_id')->constrained('complaint_logs')->cascadeOnDelete();

            // verification · work_start · completion · supporting
            $table->string('stage', 24)->default('supporting')->index();

            // The MinIO key of the file itself.
            $table->string('path');

            // "Crew patching the shoulder at KM 2"
            $table->string('caption', 200)->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // exif · manual · none
            $table->string('location_source', 16)->default('none');

            // When the photograph was taken — not when it was uploaded.
            $table->timestamp('captured_at')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // The same file cannot be attached twice to the same update.
            $table->unique(['log_id', 'path']);
        });

        $this->backfill();
    }

    /**
     * Give every photograph that is already on a log a row of its own, so no
     * proof is invisible to the new table.
     */
    private function backfill(): void
    {
        DB::table('complaint_logs')
            ->whereNotNull('documentation_paths')
            ->orderBy('id')
            ->each(function (object $log): void {
                $paths = json_decode((string) $log->documentation_paths, true);

                if (! is_array($paths)) {
                    return;
                }

                $stage = match ($log->new_status) {
                    'In Progress' => 'work_start',
                    'Resolved' => 'completion',
                    default => 'supporting',
                };

                $rows = [];

                foreach ($paths as $path) {
                    if (! is_string($path) || trim($path) === '') {
                        continue;
                    }

                    $rows[] = [
                        'complaint_id' => $log->complaint_id,
                        'log_id' => $log->id,
                        'stage' => $stage,
                        'path' => $path,
                        'location_source' => 'none',
                        'uploaded_by' => $log->actor_id,
                        'created_at' => $log->created_at ?? now(),
                    ];
                }

                if ($rows !== []) {
                    DB::table('complaint_evidences')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_evidences');
    }
};
