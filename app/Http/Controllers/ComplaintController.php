<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintLog;
use App\Models\Department;
use App\Services\DepartmentRouter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            $query->where('ticket_id', 'ILIKE', '%' . $request->search . '%');
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
     * - Creates the complaint record.
     * - Creates the first ComplaintLog entry (status = Submitted).
     * - All in a single DB transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category'    => ['required', 'string', 'in:' . implode(',', self::VALID_CATEGORIES)],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            'urgency'     => ['required', 'in:Low,Medium,High,Urgent'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB max
            'terms'       => ['accepted'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
            'address_text'=> ['nullable', 'string', 'max:500'],
        ]);

        // Resolve department by category code
        $department = DepartmentRouter::resolve($validated['category']);
        $deptName = $department?->name ?? 'General Services Office';

        // Handle optional image upload to MinIO
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('complaints', 'minio');
        }

        $complaint = DB::transaction(function () use ($validated, $department, $imagePath, $request) {
            $complaint = Complaint::create([
                'user_id'       => Auth::id(),
                'department_id' => $department?->id,
                'category'      => $validated['category'],
                'title'         => $validated['title'],
                'description'   => $validated['description'],
                'urgency'       => $validated['urgency'],
                'image_path'    => $imagePath,
                'latitude'      => $validated['latitude'] ?? null,
                'longitude'     => $validated['longitude'] ?? null,
                'address_text'  => $validated['address_text'] ?? null,
                'status'        => ComplaintStatus::Submitted->value,
                'is_public'     => 0,
            ]);

            // First log entry — previous_status is null (brand new complaint)
            // NEVER update this log. It is an immutable record.
            ComplaintLog::create([
                'complaint_id'    => $complaint->id,
                'actor_id'        => Auth::id(),
                'previous_status' => null,
                'new_status'      => ComplaintStatus::Submitted->value,
                'comment'         => "Complaint filed and routed to " . ($department?->name ?? 'General Services Office') . ".",
            ]);

            return $complaint;
        });

        // Store ticket_id for redirect
        session(['new_ticket' => $complaint->ticket_id]);

        return redirect()->route('complaints.success', $complaint);
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
}