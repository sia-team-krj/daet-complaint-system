<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The audit directory: who is in the log, grouped and counted.
 *
 * The log grows with the municipality (every resident filing is a row), so the
 * directory never loads every actor into memory. It keeps one cached snapshot
 * of the counts and the roster *ids*, and fetches identity rows only for the
 * people actually on screen.
 */
class AuditDirectory
{
    /** Rows shown per group before the admin asks for the rest. */
    public const PREVIEW_ROWS = 8;

    /** Rows per page once a group is opened. Never a dump: a group of 3,000
     *  residents pages 25 at a time. */
    public const PAGE_ROWS = 25;

    /** Seconds the snapshot (counts + roster) stays cached. */
    public const CACHE_TTL = 60;

    private const CACHE_KEY = 'audit.directory.snapshot';

    /** Drop the cached snapshot (call when the offices change). */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Cached snapshot: totals, per-actor counts and the roster ids per group.
     *
     * @return array{
     *     total: int,
     *     system: int,
     *     actor_counts: array<int, int>,
     *     groups: array<string, array{type: string, ids: array<int, int>, entries: int}>,
     *     order: array<int, string>,
     *     departments: array<int, array{id: int, name: string, code: string, count: int}>
     * }
     */
    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => $this->build());
    }

    /**
     * @return array{total: int, system: int, actor_counts: array<int, int>, groups: array<string, array{type: string, ids: array<int, int>, entries: int}>, order: array<int, string>, departments: array<int, array{id: int, name: string, code: string, count: int}>}
     */
    private function build(): array
    {
        $actorCounts = ActivityLog::query()
            ->selectRaw('actor_id, count(*) as total')
            ->whereNotNull('actor_id')
            ->groupBy('actor_id')
            ->pluck('total', 'actor_id')
            ->map(fn ($count): int => (int) $count)
            ->all();

        $departments = Department::orderBy('name')->get(['id', 'name', 'code']);

        $departmentCounts = ActivityLog::query()
            ->selectRaw('department_id, count(*) as total')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->pluck('total', 'department_id')
            ->map(fn ($count): int => (int) $count)
            ->all();

        // One narrow pass over the users table; the actor set is applied in PHP
        // so this stays a plain scan instead of a 20k-parameter IN list.
        $actors = DB::table('users')
            ->select('id', 'first_name', 'last_name', 'role', 'department_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        /** @var array<string, array{type: string, ids: array<int, int>, entries: int}> $groups */
        $groups = [];

        foreach ($departments as $department) {
            $groups['department-'.$department->id] = [
                'type' => 'department',
                'ids' => [],
                'entries' => 0,
            ];
        }

        $groups['administration'] = ['type' => 'administration', 'ids' => [], 'entries' => 0];
        $groups['residents'] = ['type' => 'residents', 'ids' => [], 'entries' => 0];

        foreach ($actors as $actor) {
            $id = (int) $actor->id;

            if (! array_key_exists($id, $actorCounts)) {
                continue;
            }

            $count = $actorCounts[$id];

            if ($actor->role === 'citizen') {
                $groups['residents']['ids'][] = $id;
                $groups['residents']['entries'] += $count;

                continue;
            }

            $officeKey = $actor->department_id ? 'department-'.$actor->department_id : null;

            if ($officeKey !== null && isset($groups[$officeKey])) {
                $groups[$officeKey]['ids'][] = $id;
                $groups[$officeKey]['entries'] += $count;

                continue;
            }

            $groups['administration']['ids'][] = $id;
            $groups['administration']['entries'] += $count;
        }

        // Offices first (by name), then the office-less admins, then residents.
        $order = [];

        foreach ($groups as $key => $group) {
            if ($group['type'] === 'department' && $group['ids'] !== []) {
                $order[] = $key;
            }
        }

        if ($groups['administration']['ids'] !== []) {
            $order[] = 'administration';
        }

        if ($groups['residents']['ids'] !== []) {
            $order[] = 'residents';
        }

        return [
            'total' => ActivityLog::query()->count(),
            'system' => ActivityLog::query()->whereNull('actor_id')->count(),
            // Every entry a resident made — the sum of the residents group,
            // so the scope in the rail and the group in the roster agree.
            'residents' => $groups['residents']['entries'],
            // People, not entries: how many accounts are behind each scope.
            'people' => count($actorCounts),
            'residents_people' => count($groups['residents']['ids']),
            'actor_counts' => $actorCounts,
            'groups' => $groups,
            'order' => $order,
            'departments' => $departments->map(fn (Department $department): array => [
                'id' => (int) $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'count' => (int) ($departmentCounts[$department->id] ?? 0),
                'people' => count($groups['department-'.$department->id]['ids'] ?? []),
            ])->all(),
        ];
    }

    /**
     * The groups for display: label, head count, entry total and the rows to
     * render. A group is expanded when the admin asked for it.
     *
     * @return array<int, array{key: string, label: string, type: string, people: int, entries: int, rows: array<int, array<string, mixed>>, expanded: bool, page: int, pages: int}>
     */
    public function groups(?string $expandedKey = null, int $page = 1): array
    {
        $snapshot = $this->snapshot();
        $groups = [];

        foreach ($snapshot['order'] as $key) {
            $group = $snapshot['groups'][$key];
            $expanded = $expandedKey === $key;
            $slice = $this->slice($key, $expanded ? $page : null);
            $people = count($group['ids']);

            $groups[] = [
                'key' => $key,
                'label' => $this->groupLabel($key, $group['type'], $snapshot),
                'type' => $group['type'],
                'people' => $people,
                'entries' => $group['entries'],
                'rows' => $slice['rows'],
                'expanded' => $expanded,
                'page' => $slice['page'],
                'pages' => $slice['pages'],
                'from' => $expanded ? ((($slice['page'] - 1) * self::PAGE_ROWS) + 1) : 0,
                'to' => $expanded ? min($slice['page'] * self::PAGE_ROWS, $people) : 0,
            ];
        }

        return $groups;
    }

    /**
     * People matching a name, newest activity first. Only people who appear in
     * the log are searchable — the directory is an audit surface, not a
     * directory of registered accounts.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function search(string $query, int $limit = 20): array
    {
        $needle = trim($query);

        if (mb_strlen($needle) < 2) {
            return ['rows' => [], 'total' => 0];
        }

        $snapshot = $this->snapshot();

        $users = User::withTrashed()
            ->where(function (Builder $builder) use ($needle): void {
                $like = '%'.$needle.'%';
                $builder->where('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like)
                    ->orWhereRaw("(first_name || ' ' || last_name) ilike ?", [$like]);
            })
            ->whereExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('activity_logs')
                    ->whereColumn('activity_logs.actor_id', 'users.id');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get();

        $rows = [];

        foreach ($users as $user) {
            $count = $snapshot['actor_counts'][$user->getKey()] ?? 0;

            if ($count === 0) {
                continue;
            }

            $rows[] = $this->personRow($user, $count, $snapshot);
        }

        return ['rows' => $rows, 'total' => count($rows)];
    }

    /**
     * The identity of one person, for the selection label.
     */
    public function personLabel(User $user): string
    {
        $office = $user->department_id
            ? Department::whereKey($user->department_id)->value('name')
            : null;

        return $office
            ? "{$user->full_name} ({$this->roleLabel((string) $user->role)} · {$office})"
            : "{$user->full_name} ({$this->roleLabel((string) $user->role)})";
    }

    /**
     * Human label for a stored role key. Roles are language-neutral keys in
     * the database; they are translated only on display.
     */
    public function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => __('nav.administrator'),
            'staff' => __('nav.staff'),
            'citizen' => __('nav.citizen'),
            default => ucfirst($role),
        };
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function slice(string $groupKey, ?int $page): array
    {
        $snapshot = $this->snapshot();
        $group = $snapshot['groups'][$groupKey] ?? null;

        if ($group === null) {
            return ['rows' => [], 'page' => 1, 'pages' => 1];
        }

        $ids = $group['ids'];
        $total = count($ids);

        if ($page === null) {
            return [
                'rows' => $this->peopleRows(array_slice($ids, 0, self::PREVIEW_ROWS), $snapshot),
                'page' => 1,
                'pages' => max(1, (int) ceil($total / self::PAGE_ROWS)),
            ];
        }

        $pages = max(1, (int) ceil($total / self::PAGE_ROWS));
        $page = max(1, min($page, $pages));
        $slice = array_slice($ids, ($page - 1) * self::PAGE_ROWS, self::PAGE_ROWS);

        return [
            'rows' => $this->peopleRows($slice, $snapshot),
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * @param  array<int, int>  $ids
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, mixed>>
     */
    private function peopleRows(array $ids, array $snapshot): array
    {
        if ($ids === []) {
            return [];
        }

        $users = User::withTrashed()->whereIn('id', $ids)->get()->keyBy(fn (User $user) => $user->getKey());
        $rows = [];

        foreach ($ids as $id) {
            $user = $users->get($id);

            if ($user === null) {
                continue;
            }

            $rows[] = $this->personRow($user, $snapshot['actor_counts'][$id] ?? 0, $snapshot);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function personRow(User $user, int $count, array $snapshot): array
    {
        $office = $user->department_id
            ? collect($snapshot['departments'])->firstWhere('id', (int) $user->department_id)
            : null;

        return [
            'id' => $user->getKey(),
            'name' => $user->full_name,
            'initials' => $user->initials,
            'role' => $this->roleLabel((string) $user->role),
            'office' => $office['name'] ?? null,
            'count' => $count,
            'deleted' => $user->trashed(),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function groupLabel(string $key, string $type, array $snapshot): string
    {
        if ($type === 'department') {
            $departmentId = (int) str_replace('department-', '', $key);
            $department = collect($snapshot['departments'])->firstWhere('id', $departmentId);

            return $department['name'] ?? __('audit.departments');
        }

        return match ($type) {
            'administration' => __('audit.administration'),
            'residents' => __('audit.residents'),
            default => $type,
        };
    }
}
