<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Enums\FeedbackStatus;
use App\Enums\InformationRequestStatus;
use App\Models\Complaint;
use App\Models\ComplaintInformationRequest;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Models\User;
use App\Rules\WithinDaet;
use App\Services\ActivityLogger;
use App\Services\BarangayLocator;
use App\Services\ComplaintDetectionService;
use App\Services\ComplaintFollowUpService;
use App\Services\ComplaintPrivacyService;
use App\Services\ComplaintPriorityService;
use App\Services\ComplaintReopenService;
use App\Services\ComplaintVerificationService;
use App\Services\DaetBoundary;
use App\Services\DepartmentRouter;
use App\Services\NotificationService;
use App\Services\PendingLocationUpload;
use App\Services\PhotoLocationService;
use App\Services\TransparencyFeed;
use App\Support\ComplaintProgress;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ComplaintController extends Controller
{
    use AuthorizesRequests;

    /**
     * Visibility of a complaint on the public transparency page.
     *
     * These are the values the filing form submits. They are the resident's
     * choice alone — staff and admins have no route that writes them, only
     * moderation, which is a separate decision with its own reason trail.
     */
    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_PUBLIC = 'public';

    /**
     * Which location a complaint is filed at when a photo's own GPS and the pin
     * the resident placed disagree.
     */
    public const LOCATION_CHOICE_PHOTO = 'photo';

    public const LOCATION_CHOICE_MAP = 'map';

    /**
     * The tracker never holds still: the ticket is the only secret on the page,
     * so neither the document nor its fragment may be cached, linked or
     * indexed. Shared by the page and by `trackBrowser`.
     *
     * @var array<string, string>
     */
    private const TRACK_HEADERS = [
        'Cache-Control' => 'no-store, private',
        'Referrer-Policy' => 'no-referrer',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    /**
     * List all complaints for the authenticated user.
     * Supports filtering by status and searching by ticket_id.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $base = Complaint::where('user_id', $userId)->with(['department', 'logs', 'informationRequests', 'user']);

        $byStatus = (clone $base)
            ->select('status')
            ->selectRaw('count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $counts = [
            'all' => $byStatus->sum(),
            'submitted' => (int) ($byStatus[ComplaintStatus::Submitted->value] ?? 0),
            'under_review' => (int) ($byStatus[ComplaintStatus::UnderReview->value] ?? 0),
            'in_progress' => (int) ($byStatus[ComplaintStatus::InProgress->value] ?? 0),
            'resolved' => (int) ($byStatus[ComplaintStatus::Resolved->value] ?? 0) + (int) ($byStatus[ComplaintStatus::Closed->value] ?? 0),
            'open' => (int) ($byStatus[ComplaintStatus::Submitted->value] ?? 0)
                + (int) ($byStatus[ComplaintStatus::UnderReview->value] ?? 0)
                + (int) ($byStatus[ComplaintStatus::InProgress->value] ?? 0),
        ];

        // Attention strip: reports with a question currently waiting on the
        // resident (an information request in `open` state), on open reports.
        // Keyed off the ask itself, not the review axis, so the banner always
        // lands on a reply form — never on an empty page.
        $attentionBase = fn () => Complaint::where('user_id', $userId)
            ->whereHas('informationRequests', fn ($q) => $q->open())
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::UnderReview->value,
                ComplaintStatus::InProgress->value,
            ]);

        $attentionCount = $attentionBase()->count();
        $attentionComplaint = $attentionCount > 0
            ? $attentionBase()->orderByDesc('updated_at')->first()
            : null;

        $query = clone $base;

        $status = (string) $request->query('status', 'all');

        if ($status === 'open') {
            $query->whereIn('status', [ComplaintStatus::Submitted->value, ComplaintStatus::UnderReview->value, ComplaintStatus::InProgress->value]);
        } elseif ($status === 'resolved') {
            $query->whereIn('status', [ComplaintStatus::Resolved->value, ComplaintStatus::Closed->value]);
        } elseif (in_array($status, ['submitted', 'under_review', 'in_progress', 'rejected', 'closed'], true)) {
            $statusValue = match ($status) {
                'submitted' => ComplaintStatus::Submitted->value,
                'under_review' => ComplaintStatus::UnderReview->value,
                'in_progress' => ComplaintStatus::InProgress->value,
                'rejected' => ComplaintStatus::Rejected->value,
                'closed' => ComplaintStatus::Closed->value,
            };

            $query->where('status', $statusValue);
        }

        if ($request->filled('q')) {
            $q = '%'.$request->query('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('ticket_id', 'ILIKE', $q)->orWhere('title', 'ILIKE', $q);
            });
        } elseif ($request->filled('search')) {
            $query->where('ticket_id', 'ILIKE', '%'.$request->search.'%');
        }

        if ($request->filled('department')) {
            $query->whereHas('department', fn ($d) => $d->where('name', $request->query('department')));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $sort = (string) $request->query('sort', 'reported_desc');
        $sort = match ($sort) {
            'newest' => 'reported_desc',
            'oldest' => 'reported_asc',
            default => $sort,
        };

        match ($sort) {
            'reported_asc' => $query->oldest(),
            'status_asc' => $query->orderBy('status')->orderBy('ticket_id'),
            default => $query->latest(),
        };

        $complaints = $query->paginate($this->complaintsPerPage($request))->withQueryString();
        $statuses = ComplaintStatus::cases();

        $departmentOptions = (clone $base)
            ->join('departments', 'departments.id', '=', 'complaints.department_id')
            ->select('departments.name')
            ->distinct()
            ->orderBy('departments.name')
            ->pluck('name');

        $categoryLabels = collect(
            \App\Services\DepartmentRouter::categoryLabels()
        );

        $categoryOptions = (clone $base)
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->mapWithKeys(fn (string $category): array => [$category => $categoryLabels->get($category, $category)])
            ->sort()
            ->all();

        return view('complaints.index', compact(
            'complaints',
            'statuses',
            'counts',
            'departmentOptions',
            'categoryOptions',
            'attentionCount',
            'attentionComplaint',
        ));
    }

    /**
     * Display a single complaint with full details and audit timeline.
     */
    public function show(Complaint $complaint)
    {
        $this->authorize('view', $complaint);

        $complaint->load(['department', 'assignedStaff', 'logs.actor', 'informationRequests.requester', 'feedback.reviewer', 'parent', 'messages.user']);

        return view('complaints.show', compact('complaint'));
    }


    /**
     * The resident rates how this complaint was handled, once it is finished.
     *
     * A rating with no written comment is published immediately. A written
     * comment is read by the administration before it appears on the public
     * transparency page, so the resident's words go out reviewed.
     */
    public function storeFeedback(Request $request, Complaint $complaint): RedirectResponse
    {
        $user = $request->user();

        abort_unless($complaint->visibilityIsOwnedBy($user), 403);
        abort_unless($complaint->canReceiveFeedback(), 403);

        if ($complaint->feedback()->exists()) {
            return redirect()
                ->route('complaints.show', $complaint)
                ->with('error', __('complaint_show.feedback.already_submitted'));
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => __('complaint_show.feedback.errors.rating'),
            'rating.between' => __('complaint_show.feedback.errors.rating'),
        ]);

        $comment = trim((string) ($validated['comment'] ?? ''));
        $hasComment = $comment !== '';

        $complaint->feedback()->create([
            'user_id' => $user->id,
            'rating' => (int) $validated['rating'],
            'comment' => $hasComment ? $comment : null,
            'status' => $hasComment ? FeedbackStatus::Pending->value : FeedbackStatus::Published->value,
            'published_at' => $hasComment ? null : now(),
            'reviewed_at' => $hasComment ? null : now(),
        ]);

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('status', $hasComment
                ? __('complaint_show.feedback.sent_pending')
                : __('complaint_show.feedback.sent_published'));
    }

    /**
     * Post a follow-up message on the original complaint conversation.
     *
     * Follow-ups do not create a new complaint/ticket. They add a message to the
     * same complaint thread. A resident follow-up on Resolved/Closed moves the
     * complaint back to Under Review.
     */
    public function storeFollowUp(
        Request $request,
        Complaint $complaint,
        ComplaintFollowUpService $followUps,
        NotificationService $notifications,
    ): RedirectResponse {
        $this->authorize('view', $complaint);
        $user = $request->user();
        abort_unless($followUps->canPostMessage($complaint, $user), 403);

        $validated = $request->validate([
            'body' => ['required_without:description', 'nullable', 'string', 'min:10', 'max:2000'],
            'description' => ['required_without:body', 'nullable', 'string', 'min:10', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'body.required_without' => __('complaint_show.follow_up.errors.description'),
            'body.min' => __('complaint_show.follow_up.errors.description'),
            'description.required_without' => __('complaint_show.follow_up.errors.description'),
            'description.min' => __('complaint_show.follow_up.errors.description'),
        ]);

        $body = trim((string) ($validated['body'] ?? $validated['description'] ?? ''));

        $photos = $request->file('images', []);
        if (! is_array($photos)) {
            $photos = $photos ? [$photos] : [];
        }
        $storedPaths = [];

        try {
            foreach ($photos as $photo) {
                if (! $photo instanceof UploadedFile || ! $photo->isValid()) {
                    continue;
                }
                $path = $photo->store('complaints/messages', 'minio');
                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('The follow-up photo could not be stored.');
                }
                $storedPaths[] = $path;
            }
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($storedPaths);
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['images' => __('complaint_show.action.photos_invalid')]);
        }

        try {
            $followUps->createMessage($complaint, $user, $body, $storedPaths);
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($storedPaths);
            report($exception);

            return back()->withInput()->with('error', 'Unable to post follow-up message.');
        }

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('status', __('complaint_show.follow_up.message_posted'));
    }

    /**
     * The resident changes whether their own complaint is public.
     *
     * Ownership is checked explicitly rather than inferred from a policy so
     * that the reason this is resident-only is legible here. Staff and admins
     * can moderate content, but consent is not theirs to give or withdraw.
     */
    public function updateVisibility(Request $request, Complaint $complaint, ActivityLogger $activityLogger)
    {
        $user = $request->user();

        abort_unless(
            $complaint->visibilityIsOwnedBy($user),
            403,
            'Only the resident who filed this complaint can change whether it is public.',
        );

        $validated = $request->validate([
            'visibility' => ['required', Rule::in([self::VISIBILITY_PRIVATE, self::VISIBILITY_PUBLIC])],
        ]);

        $wasPublic = $complaint->is_public;
        $isPublic = $validated['visibility'] === self::VISIBILITY_PUBLIC;

        // A complaint in a category that names somebody is not published on the
        // resident's say-so. It goes out only when the office has settled it,
        // the information has been verified, the sensitive parts have been
        // redacted, and the Mayor's office has approved it — so the resident
        // cannot publish it before then, and cannot unpublish it afterwards
        // either: the office's decision stands.
        if ($complaint->isPrivacyGated()) {
            return redirect()
                ->route('complaints.show', $complaint)
                ->with('status', $complaint->isPubliclyVisible()
                    ? 'This report is published on the transparency page. Its publication is decided by the office handling it and the Mayor\'s office, because it names someone.'
                    : 'This report names someone, so it stays off the transparency page. It is published only after the office settles it, the information is verified, the sensitive parts are redacted, and the Mayor\'s office approves it.');
        }

        if ($wasPublic === $isPublic) {
            return redirect()
                ->route('complaints.show', $complaint)
                ->with('status', $isPublic
                    ? 'This complaint is already published on the transparency page.'
                    : 'This complaint is already private.');
        }

        $complaint->update(['is_public' => $isPublic]);

        $activityLogger->log(
            'complaint.visibility_changed',
            $isPublic
                ? "{$user->full_name} published complaint {$complaint->ticket_id} on the transparency page."
                : "{$user->full_name} made complaint {$complaint->ticket_id} private.",
            $complaint,
            metadata: [
                'from' => $wasPublic ? self::VISIBILITY_PUBLIC : self::VISIBILITY_PRIVATE,
                'to' => $isPublic ? self::VISIBILITY_PUBLIC : self::VISIBILITY_PRIVATE,
            ],
        );

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('status', $isPublic
                ? 'Your complaint is now published on the public transparency page.'
                : 'Your complaint is now private and has been removed from the transparency page.');
    }

    /**
     * The resident confirms the resolution and closes their own report.
     *
     * Only meaningful once the department has marked it Resolved. If they do not
     * act, the scheduled auto-close closes it 7 days after it became Resolved.
     */
    public function close(
        Request $request,
        Complaint $complaint,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $this->authorize('close', $complaint);

        $oldStatus = $complaint->statusEnum;
        $newStatus = ComplaintStatus::Closed;
        $user = $request->user();

        DB::transaction(function () use ($complaint, $oldStatus, $newStatus, $user, $activityLogger): void {
            $complaint->update([
                'status' => $newStatus->value,
                'closed_at' => now(),
                'closed_by' => 'resident',
            ]);

            ComplaintLog::create([
                'complaint_id' => $complaint->id,
                'actor_id' => $user->id,
                'previous_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'comment' => __('complaint_show.close.log_comment'),
            ]);

            $activityLogger->log(
                'complaint.closed_by_resident',
                "Resident closed complaint {$complaint->ticket_id} after resolution.",
                $complaint,
                metadata: [
                    'previous_status' => $oldStatus->value,
                    'new_status' => $newStatus->value,
                ],
            );
        });

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('status', __('complaint_show.close.success'));
    }

    /**
     * Record a resident's reopen of a resolved complaint.
     *
     * The complaint itself is not recreated — the same row returns to
     * Under Review, and the reopen is recorded in complaint_reopens and
     * visible in the Updates timeline.
     */
    public function storeReopen(
        Request $request,
        Complaint $complaint,
        ComplaintReopenService $reopens,
        NotificationService $notifications,
    ): RedirectResponse {
        $this->authorize('reopen', $complaint);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'reason.required' => __('complaint_show.reopen.errors.reason'),
            'reason.min' => __('complaint_show.reopen.errors.reason'),
        ]);

        try {
            $reopens->reopen($complaint, $request->user(), trim((string) $validated['reason']), $notifications);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', __('complaint_show.reopen.not_available'));
        }

        return redirect()
            ->route('complaints.show', $complaint)
            ->with('status', __('complaint_show.reopen.success'));
    }

    /**
     * The resident answers a verifier's request for better evidence.
     *
     * This is the only write path a resident has on an existing complaint, and
     * it exists solely to close the verification loop. Without it the "needs
     * information" state is a dead end: the dashboard can announce it, but the
     * resident can do nothing about it.
     */
    public function provideInformation(
        Request $request,
        Complaint $complaint,
        ComplaintVerificationService $verification,
        PhotoLocationService $photoLocationService,
        ActivityLogger $activityLogger,
        NotificationService $notifications,
    ): RedirectResponse {
        $this->authorize('view', $complaint);

        $validated = $request->validate([
            'response' => ['required', 'string', 'min:10', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'response.min' => 'Please write at least a sentence so the department has something to work with.',
        ]);

        $photos = $request->file('images', []);
        $storedPaths = [];

        try {
            foreach ($photos as $photo) {
                $path = $photo->store('complaints/information', 'minio');

                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('The additional photo could not be stored.');
                }

                $storedPaths[] = $path;
            }
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($storedPaths);
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['images' => 'We could not save your photos. Please try again.']);
        }

        // Re-read the photo GPS so a verifier can compare it against the
        // original report, exactly as they can for a first submission.
        $photoLocation = ($photos[0] ?? null) !== null
            ? $photoLocationService->extract($photos[0])
            : null;

        try {
            $informationRequest = $verification->recordResidentReply(
                $complaint,
                Auth::user(),
                $validated['response'],
                $storedPaths,
            );
        } catch (ValidationException $exception) {
            // Never leave orphaned uploads behind when the reply is refused.
            Storage::disk('minio')->delete($storedPaths);

            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', $exception->getMessage());
        }

        $activityLogger->log(
            'complaint.information_provided',
            "Resident provided more evidence on {$complaint->ticket_id} (round {$informationRequest->round}"
                .($photoLocation ? ', photo GPS available' : '')
                .').',
            $complaint,
        );

        $notifications->informationProvided($complaint, $informationRequest);

        return back()->with(
            'status',
            'Thank you. The department has your additional details and will continue the review.',
        );
    }

    /**
     * Stream private evidence media through the application after authorization.
     */
    public function evidence(Complaint $complaint, int $index): StreamedResponse
    {
        $this->authorize('view', $complaint);

        $paths = $complaint->evidence_images;

        if (! array_key_exists($index, $paths)) {
            abort(404);
        }

        return $this->streamPrivateImage($paths[$index], 'complaint-evidence', $index);
    }

    /**
     * Stream documentation attached to an immutable complaint update.
     */
    public function updateEvidence(Complaint $complaint, ComplaintLog $log, int $index): StreamedResponse
    {
        if ((int) $log->complaint_id !== (int) $complaint->id) {
            abort(404);
        }

        $this->authorize('view', $complaint);

        if ($log->is_internal && ! in_array(Auth::user()->role, ['staff', 'department_admin', 'admin'], true)) {
            abort(403);
        }

        $paths = $log->documentation_images;

        if (! array_key_exists($index, $paths)) {
            abort(404);
        }

        return $this->streamPrivateImage($paths[$index], 'complaint-update', $index);
    }

    public function messageEvidence(Complaint $complaint, \App\Models\ComplaintMessage $message, int $index): StreamedResponse
    {
        if ((int) $message->complaint_id !== (int) $complaint->id) {
            abort(404);
        }

        $this->authorize('view', $complaint);

        $paths = $message->images;
        if (! array_key_exists($index, $paths)) {
            abort(404);
        }

        return $this->streamPrivateImage($paths[$index], 'complaint-message', $index);
    }

    /**
     * Stream a photo attached to a resident's verification reply.
     *
     * Photos the resident sends in answer to a question are not public; the
     * verifier needs them, the public does not. Same ownership rule as the
     * report's other private files: the owner, the department, an admin.
     */
    public function informationEvidence(Complaint $complaint, ComplaintInformationRequest $informationRequest, int $index): StreamedResponse
    {
        if ((int) $informationRequest->complaint_id !== (int) $complaint->id) {
            abort(404);
        }

        $this->authorize('view', $complaint);

        $paths = $informationRequest->response_image_paths;

        if (! is_array($paths) || ! array_key_exists($index, $paths)) {
            abort(404);
        }

        return $this->streamPrivateImage($paths[$index], 'information-response', $index);
    }

    /**
     * Stream public complaint evidence without exposing MinIO directly.
     */
    public function publicEvidence(Complaint $complaint, int $index): StreamedResponse
    {
        $this->ensurePublicTransparencyComplaint($complaint);

        // Indexed against the PUBLIC path list, not the full one. Serving from
        // the full list would let anyone holding a URL for photo 1 fetch a
        // photo that has since been withheld, which is precisely what
        // withholding a photo is supposed to prevent.
        $paths = $complaint->publicImagePaths();

        if (! array_key_exists($index, $paths)) {
            abort(404);
        }

        return $this->streamPrivateImage($paths[$index], 'public-complaint-evidence', $index);
    }

    /**
     * Stream documentation from a public status update.
     */
    public function publicUpdateEvidence(Complaint $complaint, ComplaintLog $log, int $index): StreamedResponse
    {
        if ((int) $log->complaint_id !== (int) $complaint->id || $log->is_internal) {
            abort(404);
        }

        $this->ensurePublicTransparencyComplaint($complaint);

        $paths = $log->documentation_images;

        if (! array_key_exists($index, $paths)) {
            abort(404);
        }

        return $this->streamPrivateImage($paths[$index], 'public-update-evidence', $index);
    }

    private function ensurePublicTransparencyComplaint(Complaint $complaint): void
    {
        if (! $complaint->isPubliclyVisible() || $complaint->spam_status === 'spam') {
            abort(404);
        }
    }

    /**
     * Report-rows per page: 3 on a phone, 5 on a larger screen.
     *
     * Detected from the User-Agent so the resident gets fewer, taller cards on
     * a small screen and more per page on desktop — without any client-side
     * re-layout fighting the server's page count.
     */
    private function complaintsPerPage(Request $request): int
    {
        $ua = (string) $request->userAgent();

        return preg_match('/(mobile|android|iphone|ipad|iemobile)/i', $ua) ? 3 : 5;
    }

    /**
     * Stream an authorized image from private MinIO storage.
     */
    private function streamPrivateImage(string $path, string $prefix, int $index): StreamedResponse
    {
        $disk = Storage::disk('minio');

        try {
            if (! $disk->exists($path)) {
                abort(404);
            }

            $stream = $disk->readStream($path);
            $size = $disk->size($path);
        } catch (Throwable) {
            abort(404);
        }

        if (! is_resource($stream)) {
            abort(404);
        }

        $response = response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Cache-Control', 'private, max-age=3600');
        $response->headers->set('Content-Disposition', 'inline; filename="'.$prefix.'-'.($index + 1).'.'.$extension.'"');

        if (is_int($size) || (is_string($size) && ctype_digit($size))) {
            $response->headers->set('Content-Length', (string) $size);
        }

        return $response;
    }

    /**
     * Show the complaint creation form.
     */
    public function create(PendingLocationUpload $pendingUpload, DaetBoundary $boundary)
    {
        $categoryOptions = DepartmentRouter::getCategoryOptions();

        // A resident sent back to the form to answer the location question must
        // find that question still there, with their photos still attached.
        // Reading it from the staged upload rather than the session flash means a
        // refresh, a back button or a second tab does not lose the decision.
        $pending = $pendingUpload->pending();

        // The picker is whatever the active taxonomy says it is. Groups are
        // headings; their children carry the office and intake behaviour.
        $categoryGroups = DepartmentRouter::residentGroups();
        $activeCategoryKeys = DepartmentRouter::activeCategoryKeys();

        $intakePoliciesByCategory = [];
        foreach ($activeCategoryKeys as $categoryKey) {
            $intakePoliciesByCategory[$categoryKey] = Complaint::intakePolicyFor($categoryKey);
        }

        // The categories that name somebody. The form uses this to ask for the
        // name of the party and to take the publication choice away.
        $privacyPoliciesByCategory = [];
        foreach ($activeCategoryKeys as $categoryKey) {
            if (! ComplaintPrivacyService::isGated($categoryKey)) {
                continue;
            }

            $privacyPoliciesByCategory[$categoryKey] = [
                'named_party' => ComplaintPrivacyService::namedPartyKind($categoryKey),
                'named_party_position' => ComplaintPrivacyService::asksForPosition($categoryKey),
            ];
        }

        return view('complaints.create', [
            'categoryOptions' => $categoryOptions,
            'categoryGroups' => $categoryGroups,
            'locationConflict' => $pending['context'] ?? null,
            'daetBoundary' => $boundary->geojson(),
            'daetBoundaryTolerance' => $boundary->toleranceMetres(),
            'intakePoliciesByCategory' => $intakePoliciesByCategory,
            'privacyPoliciesByCategory' => $privacyPoliciesByCategory,
        ]);
    }

    /**
     * Store a new complaint with automatic department routing.
     * - Maps category to department automatically.
     * - Stores up to five required evidence photos and uses GPS metadata when present.
     * - Creates the complaint record.
     * - Creates the first ComplaintLog entry (status = Submitted).
     * - All in a single DB transaction.
     */
    public function store(
        Request $request,
        NotificationService $notifications,
        ActivityLogger $activityLogger,
        ComplaintDetectionService $detector,
        ComplaintPriorityService $priorityService,
        PhotoLocationService $photoLocationService,
        PendingLocationUpload $pendingUpload,
        BarangayLocator $barangays,
    ) {
        $categoryInput = (string) $request->input('category', '');
        $activeCategoryKeys = DepartmentRouter::activeCategoryKeys();
        $intakePolicy = Complaint::intakePolicyFor($categoryInput !== '' ? $categoryInput : null);
        $intakeMode = $intakePolicy['mode'];
        // A category that names somebody is governed by the privacy policy:
        // filed private, and published only after the office has settled it,
        // the information has been verified, the sensitive parts have been
        // redacted, and the Mayor's office has approved it.
        $isPrivacyGated = ComplaintPrivacyService::isGated($categoryInput !== '' ? $categoryInput : null);
        $photosRequired = (bool) ($intakePolicy['photos_required'] ?? true);
        $photosAllowed = (bool) ($intakePolicy['photos_allowed'] ?? true);
        $barangayRequired = (bool) ($intakePolicy['barangay_required'] ?? true);
        $addressTextRequired = (bool) ($intakePolicy['address_text_required'] ?? false);
        $locationStepsEnabled = (bool) ($intakePolicy['show_location_steps'] ?? true);
        // Backward compatibility: physical_required keeps nullable barangay with
        // profile fallback (old/bookmarked forms). proof_optional (1a) requires it.
        $barangayServerRequired = $intakeMode === 'proof_optional';

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in($activeCategoryKeys)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            // `images` is the current field. `image` remains accepted for old
            // clients and existing bookmarked forms during the transition.
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'camera_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'terms' => ['accepted'],
            // A category that names somebody may only be filed after the
            // resident acknowledges the publication gate — the box is hidden
            // in the form for every other category and shown for those.
            'privacy_ack' => [$isPrivacyGated ? 'accepted' : 'nullable'],
            // Jurisdiction, not just a coordinate range. `between:-90,90` accepted
            // a pin anywhere on the planet; a complaint outside Daet cannot be
            // labelled with a barangay from the app's own table, and it has no
            // place on the city's service map. The boundary is simplified, so a
            // deliberate tolerance at the edge keeps genuine border-adjacent
            // reports from being rejected over map data.
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
                new WithinDaet($request->input('longitude')),
            ],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // The resident's own decision on whether this may appear on the
            // public transparency page. `private` is the default, both in the
            // validation below and in the schema, so a form that predates this
            // field — a bookmarked page, an old client — files privately
            // rather than publishing by accident.
            'visibility' => ['nullable', Rule::in([self::VISIBILITY_PRIVATE, self::VISIBILITY_PUBLIC])],
            // The name of the party a gated complaint is about — a member of
            // personnel, or a business. It is stored in a field that never
            // leaves the office.
            'named_party' => ['nullable', 'string', 'max:150'],
            'named_party_position' => ['nullable', 'string', 'max:150'],
            'address_text' => [$addressTextRequired ? 'required' : 'nullable', 'string', 'max:500'],
            // The resident's own answer to "which barangay is this?", which they
            // are simply asked rather than guessed for. Guessing from a single
            // reference point per barangay gets it wrong across most of a large
            // barangay, and Daet is only 10 km across, so the guess was showing
            // residents the wrong area for their own home.
            //
            // Nullable rather than required so that a form predating this field
            // — a bookmarked page, an old client — still files. It falls back to
            // the barangay the resident gave at registration, which is their own
            // statement rather than our inference, so the column is never
            // populated from a guess.
            'barangay' => [
                $barangayServerRequired ? 'required' : 'nullable',
                'string',
                Rule::in(User::BARANGAYS),
            ],
            // Which of two disagreeing locations the resident means. Only
            // present when the form asked; absent on a first, unambiguous submit.
            'location_choice' => ['nullable', Rule::in([
                self::LOCATION_CHOICE_PHOTO,
                self::LOCATION_CHOICE_MAP,
            ])],
        ]);

        $photos = $photosAllowed ? $this->evidencePhotos($request) : [];

        // Evidence is required, but on the second pass of a location decision the
        // photos are already held rather than re-uploaded — asking for them again
        // would make the resident re-attach three photos from a phone to answer a
        // yes/no question, so the holding area counts as having them.
        $hasHeldPhotos = $pendingUpload->hasStagedFiles();

        if ($photosRequired && $photos === [] && ! $hasHeldPhotos) {
            return back()
                ->withInput()
                ->withErrors(['images' => 'Add at least one clear photo of the issue.']);
        }

        if ($photos !== [] && count($photos) > 5) {
            return back()
                ->withInput()
                ->withErrors(['images' => 'You can attach a maximum of five photos.']);
        }

        // Resolve department by category key. The taxonomy chooses a live
        // office; the category itself already had to be active to validate.
        $department = DepartmentRouter::resolve($validated['category']);

        if ($department === null) {
            return back()
                ->withInput()
                ->withErrors(['category' => __('complaint_create.errors.no_office')]);
        }

        // Read GPS metadata before storing the evidence photos. The first photo
        // with valid GPS wins; address/map remains the fallback for images
        // without location metadata.
        $photoLocation = null;
        foreach ($photos as $photo) {
            $photoLocation = $photoLocationService->extract($photo);

            if ($photoLocation) {
                break;
            }
        }

        // On the second pass there is no file to read, so the photo's coordinates
        // come from what was recorded when the question was first asked.
        if ($photoLocation === null && $hasHeldPhotos) {
            $photoLocation = $pendingUpload->pending()['context']['photo'] ?? null;
        }

        /*
         * Which of the two locations wins.
         *
         * The photo's own GPS used to be applied silently and unconditionally, so
         * a photo taken at home filed the complaint at home while the resident
         * stood at the pothole and pointed at the map. That is a trust problem
         * more than a data problem: the system was choosing on their behalf and
         * saying nothing. When the two disagree by more than a rounding error,
         * the resident is asked, and the uploads are parked so they do not have
         * to attach the same photos twice.
         */
        $choice = $validated['location_choice'] ?? null;

        if ($locationStepsEnabled && $choice === null
            && $pendingUpload->isConflict(
                $photoLocation,
                $validated['latitude'] ?? null,
                $validated['longitude'] ?? null,
            )) {
            $imagePaths = [];
            try {
                foreach ($photos as $photo) {
                    $path = $photo->store('complaints', 'minio');

                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('The evidence photo could not be stored.');
                    }

                    $imagePaths[] = $path;
                }

                $mapLocation = [
                    'latitude' => (float) $validated['latitude'],
                    'longitude' => (float) $validated['longitude'],
                ];

                // Residents are shown a barangay, never a coordinate. Four
                // decimal places of latitude is not a place anyone recognises,
                // and it is the same information we have decided not to publish.
                $pendingUpload->stage($imagePaths, [
                    'photo' => $photoLocation + [
                        'barangay' => $barangays->describe($photoLocation['latitude'], $photoLocation['longitude']),
                    ],
                    'map' => $mapLocation + [
                        'barangay' => $barangays->describe($mapLocation['latitude'], $mapLocation['longitude']),
                    ],
                    'distance_metres' => (int) round($pendingUpload->metresBetween(
                        (float) $photoLocation['latitude'],
                        (float) $photoLocation['longitude'],
                        (float) $validated['latitude'],
                        (float) $validated['longitude'],
                    )),
                ]);
            } catch (Throwable $exception) {
                Storage::disk('minio')->delete($imagePaths);
                report($exception);

                return back()
                    ->withInput()
                    ->withErrors(['images' => 'We could not save the photos. Please try again.']);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'location_choice' => 'One of your photos was taken somewhere else. '
                        .'Tell us which location the problem is at.',
                ]);
        }

        $imagePaths = [];
        try {
            if ($choice !== null && $pendingUpload->pending() !== null) {
                // Second pass: the photos are already stored from the first one.
                $imagePaths = $pendingUpload->release();

                if ($imagePaths === []) {
                    throw new \RuntimeException('The held evidence photos could not be recovered.');
                }
            } else {
                foreach ($photos as $photo) {
                    $path = $photo->store('complaints', 'minio');

                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('The evidence photo could not be stored.');
                    }

                    $imagePaths[] = $path;
                }
            }
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($imagePaths);
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['images' => 'We could not save the photos. Please try again.']);
        }

        // Complaints filed under a no-photo intake policy (sensitive_intake /
        // follow_up_optional, e.g. business_permit / others) legitimately have
        // no evidence photos at all — the photo step is hidden and optional.
        // The first image, if any, is still the headline image.
        $imagePath = $imagePaths[0] ?? null;
        /*
         * Resolve the location that will actually be stored, and say plainly in
         * the audit trail which of the possible sources it came from. A department
         * reading "photo_gps" needs it to be true, because that is what tells them
         * whether the pin was placed by hand or inherited from a file.
         */
        $pinLatitude = $validated['latitude'] ?? null;
        $pinLongitude = $validated['longitude'] ?? null;
        $hasPin = $pinLatitude !== null && $pinLatitude !== ''
            && $pinLongitude !== null && $pinLongitude !== '';
        $hasPhoto = $photoLocation !== null;

        if (! $locationStepsEnabled) {
            $latitude = null;
            $longitude = null;
            $locationSource = 'not_applicable';
        } elseif ($choice === self::LOCATION_CHOICE_MAP) {
            $latitude = $hasPin ? (float) $pinLatitude : null;
            $longitude = $hasPin ? (float) $pinLongitude : null;
            $locationSource = $hasPin ? 'map' : 'none';
        } elseif ($choice === self::LOCATION_CHOICE_PHOTO) {
            $latitude = $hasPhoto ? $photoLocation['latitude'] : null;
            $longitude = $hasPhoto ? $photoLocation['longitude'] : null;
            $locationSource = $hasPhoto ? 'photo_gps' : 'none';
        } else {
            // No question was asked, so the existing preference stands: a photo
            // that knows where it was taken beats a pin, and a pin beats prose.
            $latitude = $hasPhoto ? $photoLocation['latitude'] : ($hasPin ? (float) $pinLatitude : null);
            $longitude = $hasPhoto ? $photoLocation['longitude'] : ($hasPin ? (float) $pinLongitude : null);
            $locationSource = $hasPhoto
                ? 'photo_gps'
                : ($hasPin ? 'map' : (filled($validated['address_text'] ?? null) ? 'address' : 'none'));
        }

        try {
            $complaint = DB::transaction(function () use ($validated, $department, $imagePath, $imagePaths, $latitude, $longitude, $locationSource, $locationStepsEnabled, $intakeMode, $activityLogger, $detector, $priorityService, $isPrivacyGated, $notifications) {
                // The resident's stated barangay: what they chose on the form, or
                // failing that what they gave at registration. Never a guess — a
                // guess from a reference point is wrong across most of a large
                // barangay, and a wrong barangay on a public page is worse than
                // no barangay at all.
                $barangay = $validated['barangay'] ?? null;

                // For modes where location is not applicable (hidden steps),
                // never infer incident location from the resident's profile.
                if (($barangay === null || $barangay === '') && $locationStepsEnabled && $intakeMode !== 'proof_optional') {
                    $barangay = Auth::user()?->barangay ?: null;
                } elseif (! $locationStepsEnabled) {
                    $barangay = null;
                }

                $complaint = Complaint::create([
                    'user_id' => Auth::id(),
                    'department_id' => $department?->id,
                    'category' => $validated['category'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'image_path' => $imagePath,
                    'image_paths' => $imagePaths,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address_text' => $locationStepsEnabled ? ($validated['address_text'] ?? null) : null,
                    'barangay' => $barangay,
                    'intake_mode' => $intakeMode,
                    'status' => ComplaintStatus::Submitted->value,
                    // Private unless the resident explicitly asked otherwise.
                    // A complaint in a category that names somebody is always
                    // private: the resident was never offered the choice, and
                    // publication is decided by the office and the Mayor's
                    // office, not by the person who filed it.
                    'is_public' => ! $isPrivacyGated
                        && ($validated['visibility'] ?? self::VISIBILITY_PRIVATE)
                            === self::VISIBILITY_PUBLIC,
                    'review_status' => ComplaintReviewStatus::Pending->value,
                    'suggested_priority' => 'routine',
                    // The name of the party, when the category names one.
                    'named_party' => $isPrivacyGated ? ($validated['named_party'] ?? null) : null,
                    'named_party_position' => $isPrivacyGated
                        ? ($validated['named_party_position'] ?? null)
                        : null,
                ]);

                $suggestion = $priorityService->suggest($complaint);
                $complaint->update([
                    'suggested_priority' => $suggestion['priority'],
                    'suggestion_reasons' => $suggestion['reasons'],
                ]);

                // First log entry — previous_status is null (brand new complaint)
                // NEVER update this log. It is an immutable record.
                ComplaintLog::create([
                    'complaint_id' => $complaint->id,
                    'actor_id' => Auth::id(),
                    'previous_status' => null,
                    'new_status' => ComplaintStatus::Submitted->value,
                    'comment' => 'Complaint filed and routed to '.($department?->name ?? "Mayor's Office").' for department verification.',
                ]);

                $moderation = $detector->evaluate($complaint);
                if ($complaint->isModerationFlagged()) {
                    $activityLogger->log(
                        'complaint.moderation_flagged',
                        "Complaint {$complaint->ticket_id} was flagged for staff review.",
                        $complaint,
                        metadata: [
                            'spam_status' => $moderation['spam_status'],
                            'spam_score' => $moderation['spam_score'],
                            'reasons' => $moderation['reasons'],
                            'duplicate_of_id' => $moderation['duplicate_of_id'],
                            'similarity_score' => $moderation['similarity_score'],
                        ],
                    );

                    // The resident is told their report was flagged for review, so
                    // they are not left waiting without knowing why it is held.
                    // The actor is left unset here: the filer is the owner, and an
                    // owner must not be notified about their own filing.
                    $notifications->moderationFlagged($complaint);
                }

                $activityLogger->log(
                    'complaint.filed',
                    "Complaint {$complaint->ticket_id} filed and routed to ".($department?->name ?? "Mayor's Office").'.',
                    $complaint,
                    metadata: [
                        'category' => $complaint->category,
                        'suggested_priority' => $suggestion['priority'],
                        'suggestion_reasons' => $suggestion['reasons'],
                        'review_status' => $complaint->review_status,
                        'location_source' => $locationSource,
                        'intake_mode' => $intakeMode,
                        'evidence_count' => count($imagePaths),
                        'department' => $department?->name,
                    ],
                );

                return $complaint;
            });
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($imagePaths);
            throw $exception;
        }

        // Store ticket_id for redirect
        session([
            'new_ticket' => $complaint->ticket_id,
            'complaint_location_source' => $locationSource,
        ]);

        // Tell the department somebody can settle it. Without this a new report
        // is invisible until a verifier happens to open the queue. Notification
        // failure must never cost the resident their complaint, so it is
        // reported rather than allowed to abort the request.
        try {
            $notifications->complaintNeedsVerification($complaint, Auth::user());
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('complaints.success', $complaint);
    }

    /**
     * Normalize the current and legacy upload fields into one bounded list.
     *
     * @return array<int, UploadedFile>
     */
    private function evidencePhotos(Request $request): array
    {
        $photos = $request->file('images', []);

        if ($photos instanceof UploadedFile) {
            $photos = [$photos];
        } elseif (! is_array($photos)) {
            $photos = [];
        }

        $cameraPhoto = $request->file('camera_photo');
        $legacyPhoto = $request->file('image');

        if ($cameraPhoto) {
            $photos[] = $cameraPhoto;
        }

        if ($legacyPhoto) {
            $photos[] = $legacyPhoto;
        }

        return array_values(array_filter(
            $photos,
            fn (mixed $photo): bool => $photo instanceof UploadedFile && $photo->isValid(),
        ));
    }

    /**
     * Show success/confirmation page after submission.
     */
    public function success(Complaint $complaint)
    {
        $this->authorize('view', $complaint);
        $complaint->load('department');

        return view('complaints.success', compact('complaint'));
    }

    /**
     * Track a complaint by ticket number.
     *
     * Public on purpose: the legal documents promise a resident can check
     * progress without an account. The ticket is the only secret, so the page
     * shows progress and nothing else unless the viewer can already open the
     * full record.
     */
    public function track(Request $request, TransparencyFeed $feed)
    {
        $context = $this->trackContext($request, $feed);

        if (isset($context['redirect'])) {
            return redirect()->to($context['redirect']);
        }

        return $this->trackResponse($context['data']);
    }

    /**
     * The fragment behind the same lookup: the browser swaps the one region a
     * full load would have redrawn, rendered by the same code, so the two can
     * never disagree (docs/soft-navigation.md).
     */
    public function trackBrowser(Request $request, TransparencyFeed $feed): JsonResponse
    {
        $context = $this->trackContext($request, $feed);

        if (isset($context['redirect'])) {
            return response()
                ->json(['redirect' => $context['redirect']])
                ->withHeaders(self::TRACK_HEADERS);
        }

        $data = $context['data'];
        $found = $data['complaint'] !== null;

        return response()
            ->json([
                'html' => view(
                    $found ? 'complaints.partials.track-result' : 'complaints.partials.track-lookup',
                    $data,
                )->render(),
                'mode' => $found ? 'result' : 'lookup',
                'notFound' => (bool) ($data['notFound'] ?? false),
                'title' => $found
                    ? __('complaint_track.result.page_title', ['ticket' => $data['complaint']->ticket_id])
                    : __('complaint_track.lookup.page_title'),
            ])
            ->withHeaders(self::TRACK_HEADERS);
    }

    /**
     * The one lookup the page and the fragment endpoint share: either a
     * redirect to the full ticket this viewer can reach, or the data both
     * render.
     *
     * @return array{redirect?: string, data?: array<string, mixed>}
     */
    private function trackContext(Request $request, TransparencyFeed $feed): array
    {
        $ticketInput = trim((string) $request->query('ticket_id', ''));
        $complaint = $ticketInput === '' ? null : Complaint::findByTicketId($ticketInput);

        if ($complaint !== null) {
            $fullTicketUrl = $this->fullTicketUrl($complaint, $request->user());

            if ($fullTicketUrl !== null) {
                return ['redirect' => $fullTicketUrl];
            }
        }

        if ($complaint === null) {
            return ['data' => [
                'complaint' => null,
                'notFound' => $ticketInput !== '',
                'ticketInput' => $ticketInput,
            ]];
        }

        $isPublicRecord = $complaint->isPubliclyVisible() && $complaint->spam_status !== 'spam';

        $complaint->loadMissing('department');
        if ($isPublicRecord) {
            $complaint->loadMissing(['user', 'assignedStaff']);
            $complaint->setRelation('feedback', $complaint->feedback()->published()->first());
        }

        // Public logs only. Actor names and internal notes never reach this
        // query. Documentation images are rendered only when the complaint is
        // publicly visible on the Transparency Register.
        $updates = $complaint->logs()
            ->public()
            ->reorder()
            ->latest('created_at')
            ->latest('id')
            ->get();

        $data = [
            'complaint' => $complaint,
            'notFound' => false,
            'ticketInput' => $ticketInput,
            'updates' => $updates,
            'progressSteps' => ComplaintProgress::steps($complaint, $updates),
            'whatMeans' => ComplaintProgress::whatMeans($complaint),
            'hasOpenInformationRequest' => $complaint->informationRequests()
                ->where('status', InformationRequestStatus::Open->value)
                ->exists(),
            'lastUpdated' => $updates->max('created_at'),
            'office' => $complaint->department?->name ?? __('transparency.show.department_pending'),
            'isPublicRecord' => $isPublicRecord,
        ];

        if ($isPublicRecord) {
            $data['publicTitle'] = $complaint->publicTitle();
            $data['publicDescription'] = $this->publicTrackDescription($complaint);
            $data['categoryLabel'] = $feed->categoryLabel($complaint->category);
            $data['location'] = $feed->publicLocation($complaint);
            $data['publicPhotoCount'] = count($complaint->publicImagePaths());
        }

        return ['data' => $data];
    }

    /**
     * The description a public /track visitor may read, mirroring the
     * Transparency Register: a staff redaction replaces the text; otherwise the
     * exact written address is stripped.
     */
    private function publicTrackDescription(Complaint $complaint): string
    {
        $description = trim((string) $complaint->publicDescription());

        if ($description === '') {
            return __('transparency.show.no_description');
        }

        if ($complaint->redacted_description !== null) {
            return $description;
        }

        $address = trim((string) $complaint->address_text);

        if ($address === '') {
            return $description;
        }

        $redacted = preg_replace(
            '/'.preg_quote($address, '/').'/iu',
            __('transparency.show.location_withheld'),
            $description,
        );

        return is_string($redacted) ? $redacted : $description;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function trackResponse(array $data)
    {
        return response()
            ->view('complaints.track', $data)
            ->withHeaders(self::TRACK_HEADERS);
    }

    /**
     * The full ticket URL, but only when this viewer can actually reach it.
     *
     * `complaints.show`, the staff ticket and the admin ticket all sit behind
     * `email.verified` and `force.password.change`. Sending an unverified owner
     * there would bounce them away from the progress page they are allowed to
     * read, so the reachability checks happen here, not in the view.
     */
    private function fullTicketUrl(Complaint $complaint, ?User $user): ?string
    {
        if ($user === null || $user->must_change_password || ! $user->hasVerifiedEmail()) {
            return null;
        }

        if ($user->role === 'admin') {
            return route('admin.complaints.show', $complaint);
        }

        if (Complaint::isVisibleToStaff($complaint, $user)) {
            return route('staff.complaints.show', $complaint);
        }

        if ((int) $complaint->user_id === (int) $user->id) {
            return route('complaints.show', $complaint);
        }

        return null;
    }
}
