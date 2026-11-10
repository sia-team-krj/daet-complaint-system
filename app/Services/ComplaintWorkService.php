<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\WorkExecutionMode;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\ComplaintLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The one place the rules about doing the work live.
 *
 * Two questions this service answers, for every path that can act on a
 * complaint:
 *
 * 1. **What kind of work is this?** A repair, an enforcement action, or a
 *    referral to another body. That decides what counts as proof.
 * 2. **What does this action need before it is allowed?** A repair needs a
 *    photograph of the work; everything needs a written record; a free
 *    message is never a substitute for evidence.
 *
 * Both the staff path (StaffController@update) and the admin path
 * (AdminController@complaintUpdate) call in here, so the two cannot drift —
 * an administrator cannot settle a complaint the way a department could not.
 */
class ComplaintWorkService
{
    public function __construct(
        private readonly PhotoLocationService $photoLocation,
    ) {}

    /**
     * The mode a complaint's category implies.
     */
    public function policyModeFor(?string $category): WorkExecutionMode
    {
        $configured = config('complaint_work_policy.categories.'.($category ?? ''))
            ?? config('complaint_work_policy.default');

        return WorkExecutionMode::tryFrom((string) $configured) ?? WorkExecutionMode::ReportOnly;
    }

    /**
     * The mode this complaint is governed by.
     *
     * Reads the frozen value when there is one, and otherwise the policy's
     * answer for the category. This never writes: a page that is merely
     * looked at must not change a record.
     */
    public function modeFor(Complaint $complaint): WorkExecutionMode
    {
        if ($complaint->work_mode !== null) {
            $frozen = WorkExecutionMode::tryFrom((string) $complaint->work_mode);

            if ($frozen !== null) {
                return $frozen;
            }
        }

        return $this->policyModeFor($complaint->category);
    }

    /**
     * Settle the mode this complaint will be judged by, and keep it settled.
     *
     * Called when an action is recorded, so a later edit to the policy file
     * cannot change the rules on a ticket that is already in flight. A
     * complaint whose mode was overridden keeps the override.
     */
    public function freezeMode(Complaint $complaint): WorkExecutionMode
    {
        if ($complaint->work_mode !== null && WorkExecutionMode::tryFrom((string) $complaint->work_mode) !== null) {
            return $this->modeFor($complaint);
        }

        $mode = $this->policyModeFor($complaint->category);

        // No events: this is bookkeeping, not an action.
        $complaint->forceFill(['work_mode' => $mode->value])->saveQuietly();

        return $mode;
    }

    /**
     * Let the office decide that a ticket is a different kind of work than
     * its category suggests — a streetlight that turns out to need a crew, a
     * report that turns out to need a repair.
     */
    public function overrideMode(
        Complaint $complaint,
        WorkExecutionMode $mode,
        User $actor,
        ActivityLogger $activityLogger,
    ): void {
        $complaint->forceFill(['work_mode' => $mode->value])->save();

        $activityLogger->log(
            'complaint.work_mode_changed',
            "Work type for {$complaint->ticket_id} set to {$mode->label()}.",
            $complaint,
            metadata: [
                'previous_work_mode' => null,
                'work_mode' => $mode->value,
            ],
            actor: $actor,
        );
    }

    /**
     * Whether moving to this status demands a photograph of the work.
     */
    public function requiresDocumentation(Complaint $complaint, ComplaintStatus $to): bool
    {
        if (! $this->modeFor($complaint)->requiresSitePhoto()) {
            return false;
        }

        return in_array($to, [
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
        ], true);
    }

    /**
     * The statuses this complaint's work demands a photograph for. The staff
     * form reads this so it can state the rule before the staff member
     * chooses, and the controller enforces it after.
     *
     * @return ComplaintStatus[]
     */
    public function documentationRequiredStatuses(Complaint $complaint): array
    {
        if (! $this->modeFor($complaint)->requiresSitePhoto()) {
            return [];
        }

        return [
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
        ];
    }

    /**
     * Refuse an action that does not carry its proof.
     *
     * This is the rule the office asked for: a repair cannot be reported by
     * message alone. A written record of what was done is required on every
     * action already — the staff form validates it, and it is a separate
     * concern from the evidence.
     *
     * @param  int  $photoCount  how many photographs were attached
     *
     * @throws ValidationException
     */
    public function assertActionAllowed(
        Complaint $complaint,
        ComplaintStatus $to,
        int $photoCount,
        string $photoField = 'documentation_images',
    ): void {
        if (! $this->requiresDocumentation($complaint, $to)) {
            return;
        }

        if ($photoCount > 0) {
            return;
        }

        throw ValidationException::withMessages([
            $photoField => $this->modeFor($complaint) === WorkExecutionMode::LaborRequired
                ? "This is labor work. Attach at least one photo of the work before marking this complaint as ".strtolower($to->label()).'.'
                : "Attach at least one photo before marking this complaint as ".strtolower($to->label()).'.',
        ]);
    }

    /**
     * Normalize the proof photos a request carries.
     *
     * The gallery and the camera are separate inputs because a phone can only
     * do one at a time; both are read here and treated as one selection.
     *
     * @return array<int, UploadedFile>
     */
    public function collectPhotos(Request $request): array
    {
        $photos = $this->filesFrom($request, 'documentation_images');

        $photos = array_merge($photos, $this->filesFrom($request, 'documentation_camera_photos'));

        // An older form is still in circulation in some offices.
        $legacy = $request->file('documentation_camera_photo');
        if ($legacy instanceof UploadedFile) {
            $photos[] = $legacy;
        }

        return array_values(array_filter(
            $photos,
            fn (mixed $photo): bool => $photo instanceof UploadedFile && $photo->isValid(),
        ));
    }

    /**
     * What part of the work this batch of photographs proves.
     */
    public function stageFor(ComplaintStatus $to): string
    {
        return match ($to) {
            ComplaintStatus::InProgress => 'work_start',
            ComplaintStatus::Resolved => 'completion',
            default => 'supporting',
        };
    }

    /**
     * Store the photographs and read what the camera recorded about them.
     *
     * The files are written first and the rows are attached afterwards, once
     * the status update they belong to exists. If anything goes wrong on the
     * way, the files that were already written are removed — a half-finished
     * update must not leave orphaned photographs behind.
     *
     * @param  array<int, UploadedFile>  $photos
     * @return array{paths: array<int, string>, rows: array<int, array<string, mixed>>}
     *
     * @throws \Throwable
     */
    public function storeEvidence(array $photos, Complaint $complaint, ComplaintStatus $to): array
    {
        $paths = [];
        $rows = [];

        try {
            foreach ($photos as $photo) {
                $path = $photo->store("complaint-updates/{$complaint->id}", 'minio');

                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('The documentation photo could not be stored.');
                }

                $paths[] = $path;

                // What the camera knows about where and when it was taken.
                // A photograph of a repair is only proof if the place it was
                // taken is on the record.
                $inspection = $this->photoLocation->inspect($photo);

                $rows[] = [
                    'complaint_id' => $complaint->id,
                    'stage' => $this->stageFor($to),
                    'path' => $path,
                    'latitude' => $inspection['latitude'],
                    'longitude' => $inspection['longitude'],
                    'location_source' => $inspection['location_source'],
                    'captured_at' => $inspection['captured_at'],
                    'created_at' => now(),
                ];
            }
        } catch (\Throwable $exception) {
            Storage::disk('minio')->delete($paths);

            throw $exception;
        }

        return ['paths' => $paths, 'rows' => $rows];
    }

    /**
     * Write the evidence rows once the update they belong to exists.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function attachEvidence(ComplaintLog $log, array $rows, User $actor): void
    {
        foreach ($rows as $row) {
            ComplaintEvidence::create([
                ...$row,
                'log_id' => $log->id,
                'uploaded_by' => $actor->id,
            ]);
        }
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function filesFrom(Request $request, string $key): array
    {
        $files = $request->file($key, []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }
}
