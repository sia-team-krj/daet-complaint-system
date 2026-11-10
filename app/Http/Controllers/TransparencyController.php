<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Services\BarangayLocator;
use App\Services\DepartmentRouter;
use App\Services\TransparencyFeed;
use App\Http\Controllers\Concerns\SoftNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Serves the two public transparency pages.
 *
 * The index renders no data of its own. It hands back the page shell, and the
 * register inside that shell is a Livewire component which re-reads the database
 * on a timer — that is why the query that used to live in index() now lives in
 * TransparencyFeed, shared with the component.
 *
 * The detail page is still a plain, one-shot render, and deliberately so: it is
 * a single complaint's published record. Nothing about it is shared with the
 * register, so there is nothing for a poll to keep in step.
 */
class TransparencyController extends Controller
{
    use SoftNavigation;

    public const PUBLIC_COMPLAINTS_PER_PAGE = 5;
    /**
     * The public register. The component does the reading; see TransparencyFeed
     * for the query and for why that logic is shared rather than duplicated.
     */
    public function index()
    {
        return view('pages.transparency.index');
    }

    public function complaints(Request $request, TransparencyFeed $feed, BarangayLocator $barangays): View
    {
        $data = $this->transparencyComplaints($request, $feed, $barangays);

        // A page beyond the last one lands on the last one.
        if (isset($data['redirect'])) {
            return redirect()->to($data['redirect']);
        }

        return view('pages.transparency.complaints', $data);
    }

    /**
     * The public complaint feed.
     *
     * One method builds it for both the page and the soft-navigation endpoint,
     * so a swapped-in fragment can never disagree with the page.
     *
     * @return array<string, mixed>
     */
    private function transparencyComplaints(Request $request, TransparencyFeed $feed, BarangayLocator $barangays): array
    {
        $validStatuses = collect(ComplaintStatus::cases())
            ->mapWithKeys(fn (ComplaintStatus $s) => [\Illuminate\Support\Str::slug($s->value, '_') => $s->value])
            ->all();

        $categoryKeys = collect(DepartmentRouter::getCategoryOptions())
            ->flatten(1)
            ->pluck('value')
            ->all();

        $validDepartments = Department::query()->pluck('name')->all();

        $validSorts = ['reported_desc', 'reported_asc'];

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) > 60) {
            $q = mb_substr($q, 0, 60);
        }

        $department = (string) $request->query('department', '');
        if (! in_array($department, $validDepartments, true)) {
            $department = '';
        }

        $category = (string) $request->query('category', '');
        if (! in_array($category, $categoryKeys, true)) {
            $category = '';
        }

        $statusSlug = (string) $request->query('status', '');
        $status = $validStatuses[$statusSlug] ?? '';
        if ($statusSlug !== '' && $status === '') {
            $statusSlug = '';
        }

        $sort = (string) $request->query('sort', 'reported_desc');
        if (! in_array($sort, $validSorts, true)) {
            $sort = 'reported_desc';
        }

        $page = max(1, (int) $request->query('page', 1));

        $query = Complaint::query()
            ->public()
            ->where(function ($qb): void {
                $qb->whereNull('spam_status')->orWhere('spam_status', '!=', 'spam');
            })
            ->with(['department:id,name,code']);

        if (mb_strlen($q) >= 2) {
            $escaped = str_replace("\\", "\\\\", $q);
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $escaped);
            $like = '%'.$escaped.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('ticket_id', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', $like));
            });
        }

        if ($department !== '') {
            $query->whereHas('department', fn ($d) => $d->where('name', $department));
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        // Status counts reflect the other active filters (q, department, category)
        // but NOT the status filter itself, so chips can show per-status totals.
        $statusCounts = (clone $query)
            ->select('status')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($sort === 'reported_asc') {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        }

        $paginator = $query->paginate(self::PUBLIC_COMPLAINTS_PER_PAGE, ['id', 'ticket_id', 'title', 'category', 'department_id', 'status', 'barangay', 'created_at', 'verification_method'], 'page', $page)
            ->withQueryString();

        if ($paginator->lastPage() > 0 && $page > $paginator->lastPage()) {
            return ['redirect' => $request->fullUrlWithQuery(['page' => $paginator->lastPage()])];
        }

        $reports = $paginator->getCollection()
            ->map(fn (Complaint $complaint): array => $feed->publicReport($complaint, $barangays))
            ->values();
        $paginator->setCollection($reports);

        $anyFilter = $q !== '' || $department !== '' || $category !== '' || $statusSlug !== '' || $sort !== 'reported_desc';

        return [
            'paginator' => $paginator,
            'reports' => $reports,
            'departmentOptions' => collect($validDepartments)->sort()->values(),
            'categoryOptions' => collect(DepartmentRouter::getCategoryOptions())->flatten(1)->map(fn ($c) => ['key' => $c['value'], 'label' => $c['label']])->values(),
            'statusOptions' => collect(ComplaintStatus::cases())->map(fn (ComplaintStatus $s) => ['key' => \Illuminate\Support\Str::slug($s->value, '_'), 'label' => $s->label()])->values(),
            'statusCounts' => collect($statusCounts)->mapWithKeys(function ($count, $statusValue) {
                return [\Illuminate\Support\Str::slug($statusValue, '_') => (int) $count];
            })->all(),
            'filters' => [
                'q' => $q,
                'department' => $department,
                'category' => $category,
                'status' => $statusSlug,
                'sort' => $sort,
            ],
            'anyFilter' => $anyFilter,
            'total' => $paginator->total(),
        ];
    }

    /**
     * The feed's regions, for soft navigation.
     *
     * Answering with rendered fragments lets the status chips, the active
     * filter chips and the results be swapped in place: a visitor filtering or
     * turning a page never loses the page, the scroll position or the map
     * behind it. The URL stays the truth, and without JavaScript every link
     * and form is still an ordinary one.
     */
    public function complaintsBrowser(Request $request, TransparencyFeed $feed, BarangayLocator $barangays): JsonResponse|RedirectResponse
    {
        // A person gets the page; only a script gets the fragments.
        if (! $this->wantsFragments($request)) {
            return redirect()->to($this->fragmentFallbackUrl($request, 'transparency.complaints'));
        }

        $data = $this->transparencyComplaints($request, $feed, $barangays);

        if (isset($data['redirect'])) {
            return response()->json(['redirect' => $data['redirect']]);
        }

        return response()->json([
            'chips' => view('pages.transparency.partials.complaints-chips', $data)->render(),
            'active' => view('pages.transparency.partials.complaints-active', $data)->render(),
            'results' => view('pages.transparency.partials.complaints-results', $data)->render(),
            // A filtered feed must not be indexed, and the rule lives in the
            // <head> — which a fragment swap does not touch.
            'head' => view('pages.transparency.partials.complaints-head', $data)->render(),
        ]);
    }

    public function show(Complaint $complaint, TransparencyFeed $feed): View
    {
        $this->ensurePublicComplaint($complaint);

        $complaint->load(['department', 'user']);
        $logs = $complaint->logs()
            ->where('is_internal', false)
            ->get()
            ->sortByDesc(fn (\App\Models\ComplaintLog $log): string => sprintf('%020d-%010d', $log->created_at?->getTimestamp() ?? 0, $log->id))
            ->values();
        $reporter = trim((string) $complaint->user?->display_alias);

        return view('pages.transparency.show', [
            'complaint' => $complaint,
            'logs' => $logs,
            'location' => $feed->publicLocation($complaint),
            'description' => $this->publicDescription($complaint),
            'categoryLabel' => $feed->categoryLabel($complaint->category),
            'reporter' => $reporter !== '' ? $reporter : 'Community member',
            'progressSteps' => $this->progressSteps($complaint, $logs),
        ]);
    }

    private function ensurePublicComplaint(Complaint $complaint): void
    {
        if (! $complaint->isPubliclyVisible() || $complaint->spam_status === 'spam') {
            abort(404);
        }
    }

    /**
     * Build a compact, date-aware progress trail for a public complaint.
     *
     * @param  Collection<int, \App\Models\ComplaintLog>  $logs
     * @return array<int, array{status: string, label: string, date: string|null, state: string}>
     */
    private function progressSteps(Complaint $complaint, Collection $logs): array
    {
        $statuses = [
            ComplaintStatus::Submitted,
            ComplaintStatus::UnderReview,
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
            ComplaintStatus::Closed,
        ];
        $current = $complaint->statusEnum;
        $rejected = $current === ComplaintStatus::Rejected;
        $closed = $current === ComplaintStatus::Closed;

        if ($closed) {
            $currentIndex = count($statuses); // all done, no current
        } elseif ($rejected) {
            $lastProgressLog = $logs->reject(fn ($log): bool => $log->new_status === ComplaintStatus::Rejected->value)->last();
            $lastReached = $lastProgressLog?->new_status;
            $currentIndex = max(0, (int) array_search($lastReached, $statuses, true));
        } else {
            $currentIndex = (int) array_search($current, $statuses, true);
        }

        $lastDate = null;

        return collect($statuses)->map(function (ComplaintStatus $status, int $index) use ($logs, $complaint, $currentIndex, $rejected, &$lastDate): array {
            $log = $logs->first(fn ($entry): bool => $entry->new_status === $status->value);
            $state = match (true) {
                $rejected && $index < $currentIndex => 'complete',
                $rejected && $index === $currentIndex => 'rejected',
                $rejected => 'stopped',
                $index < $currentIndex => 'complete',
                $index === $currentIndex => 'current',
                default => 'upcoming',
            };

            if ($status === ComplaintStatus::Submitted) {
                $rawDate = $complaint->created_at;
            } else {
                $rawDate = $log?->created_at;
            }

            $formatted = null;
            if ($rawDate !== null && ($lastDate === null || $rawDate->greaterThanOrEqualTo($lastDate))) {
                $formatted = $rawDate->format('M j, Y');
                $lastDate = $rawDate;
            } elseif ($state === 'complete') {
                $formatted = null; // will render "Done"
            }

            return [
                'status' => $status->value,
                'label' => $status->label(),
                'date' => $formatted,
                'state' => $state,
            ];
        })->all();
    }

    /**
     * The description the public may read.
     *
     * A staff redaction, when present, replaces the text outright — it is a
     * deliberate decision about what may be broadcast and must not be undone by
     * the automatic address scrub below. With no staff redaction, the exact
     * location is still stripped, because a precise address is a privacy risk
     * in its own right and has always been withheld from the public page.
     */
    private function publicDescription(Complaint $complaint): string
    {
        $description = trim((string) $complaint->publicDescription());

        if ($description === '') {
            return __('transparency.show.no_description');
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
}