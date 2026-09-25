<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReviewStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Services\ActivityLogger;
use App\Services\ComplaintDetectionService;
use App\Services\ComplaintPriorityService;
use App\Services\DepartmentRouter;
use App\Services\PhotoLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ComplaintController extends Controller
{
    use AuthorizesRequests;

    /**
     * Valid complaint categories for routing.
     */
    private const VALID_CATEGORIES = [
        'road_damage',
        'flooding',
        'streetlight',
        'garbage',
        'sanitation',
        'park_maintenance',
        'business_permit',
        'noise_complaint',
        'stray_animals',
        'others',
    ];

    /**
     * List all complaints for the authenticated user.
     * Supports filtering by status and searching by ticket_id.
     */
    public function index(Request $request)
    {
        $query = Complaint::where('user_id', Auth::id())
            ->with(['department', 'logs']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by ticket_id
        if ($request->filled('search')) {
            $query->where('ticket_id', 'ILIKE', '%'.$request->search.'%');
        }

        $complaints = $query->latest()->paginate(12)->withQueryString();

        // Get status options for filter dropdown
        $statuses = ComplaintStatus::cases();

        return view('complaints.index', compact('complaints', 'statuses'));
    }

    /**
     * Display a single complaint with full details and audit timeline.
     */
    public function show(Complaint $complaint)
    {
        $this->authorize('view', $complaint);

        $complaint->load(['department', 'logs.actor']);

        return view('complaints.show', compact('complaint'));
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

        $path = $paths[$index];
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
        $response->headers->set('Content-Disposition', 'inline; filename="complaint-evidence-'.($index + 1).'.'.$extension.'"');

        if (is_int($size) || (is_string($size) && ctype_digit($size))) {
            $response->headers->set('Content-Length', (string) $size);
        }

        return $response;
    }

    /**
     * Show the complaint creation form.
     */
    public function create()
    {
        $categoryOptions = DepartmentRouter::getCategoryOptions();

        return view('complaints.create', compact('categoryOptions'));
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
        ActivityLogger $activityLogger,
        ComplaintDetectionService $detector,
        ComplaintPriorityService $priorityService,
        PhotoLocationService $photoLocationService,
    ) {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', self::VALID_CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            // `images` is the current field. `image` remains accepted for old
            // clients and existing bookmarked forms during the transition.
            'images' => ['nullable', 'array', 'min:1', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'camera_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'terms' => ['accepted'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:500'],
        ]);

        $photos = $this->evidencePhotos($request);

        if ($photos === []) {
            return back()
                ->withInput()
                ->withErrors(['images' => 'Add at least one clear photo of the issue.']);
        }

        if (count($photos) > 5) {
            return back()
                ->withInput()
                ->withErrors(['images' => 'You can attach a maximum of five photos.']);
        }

        // Resolve department by category code
        $department = DepartmentRouter::resolve($validated['category']);

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

        $imagePaths = [];
        try {
            foreach ($photos as $photo) {
                $path = $photo->store('complaints', 'minio');

                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('The evidence photo could not be stored.');
                }

                $imagePaths[] = $path;
            }
        } catch (Throwable $exception) {
            Storage::disk('minio')->delete($imagePaths);
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['images' => 'We could not save the photos. Please try again.']);
        }

        $imagePath = $imagePaths[0];
        $locationSource = $photoLocation
            ? 'photo_gps'
            : (filled($validated['latitude'] ?? null) && filled($validated['longitude'] ?? null)
                ? 'map'
                : (filled($validated['address_text'] ?? null) ? 'address' : 'none'));

        try {
            $complaint = DB::transaction(function () use ($validated, $department, $imagePath, $imagePaths, $photoLocation, $locationSource, $activityLogger, $detector, $priorityService) {
                $complaint = Complaint::create([
                    'user_id' => Auth::id(),
                    'department_id' => $department?->id,
                    'category' => $validated['category'],
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'image_path' => $imagePath,
                    'image_paths' => $imagePaths,
                    'latitude' => $photoLocation['latitude'] ?? $validated['latitude'] ?? null,
                    'longitude' => $photoLocation['longitude'] ?? $validated['longitude'] ?? null,
                    'address_text' => $validated['address_text'] ?? null,
                    'status' => ComplaintStatus::Submitted->value,
                    'is_public' => 0,
                    'review_status' => ComplaintReviewStatus::Pending->value,
                    'suggested_priority' => 'routine',
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
                    'comment' => 'Complaint filed and routed to '.($department?->name ?? 'General Services Office').' for department verification.',
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
                }

                $activityLogger->log(
                    'complaint.filed',
                    "Complaint {$complaint->ticket_id} filed and routed to ".($department?->name ?? 'General Services Office').'.',
                    $complaint,
                    metadata: [
                        'category' => $complaint->category,
                        'suggested_priority' => $suggestion['priority'],
                        'suggestion_reasons' => $suggestion['reasons'],
                        'review_status' => $complaint->review_status,
                        'location_source' => $locationSource,
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
     * Track a complaint by ticket ID.
     */
    public function track(Request $request)
    {
        $complaint = null;
        $notFound = false;

        if ($request->filled('ticket_id')) {
            $complaint = Complaint::where('ticket_id', $request->ticket_id)
                ->with(['department', 'logs.actor'])
                ->first();

            if ($complaint) {
                $this->authorize('view', $complaint);

                return view('complaints.show', compact('complaint'));
            }

            $notFound = true;
        }

        return view('complaints.track', compact('complaint', 'notFound'));
    }
}
