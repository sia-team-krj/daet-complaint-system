<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The in-app notification feed behind the navbar bell.
 *
 * Read state lives in the database rather than in the DOM, so the badge, the
 * dropdown and a later email digest can never disagree about what someone has
 * already seen.
 */
class NotificationController extends Controller
{
    private const FEED_SIZE = 8;

    public function index(Request $request): View
    {
        $userId = auth()->id();
        $filter = $request->query('filter', 'all');
        $filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';

        $base = Notification::query()->where('user_id', $userId);
        $unreadCount = (clone $base)->unread()->count();
        $totalCount = (clone $base)->count();

        $query = (clone $base)->with('subject')->recentFirst();
        if ($filter === 'unread') {
            $query->unread();
        }

        $notifications = $query->paginate(20)->withQueryString();

        // Group the current page for the Today / Earlier this week / Older
        // sections, keeping the paginator intact for the pagination links.
        $grouped = $notifications->getCollection()->groupBy(fn (Notification $n) => $n->groupKey());
        $groupOrder = ['today', 'earlier-this-week', 'older'];
        $groupLabels = ['today' => 'Today', 'earlier-this-week' => 'Earlier this week', 'older' => 'Older'];
        $groups = collect($groupOrder)
            ->filter(fn ($key) => $grouped->has($key))
            ->mapWithKeys(fn ($key) => [$key => ['label' => $groupLabels[$key], 'items' => $grouped->get($key)]]);

        return view('notifications.index', [
            'notifications' => $notifications,
            'groups' => $groups,
            'filter' => $filter,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * The dropdown feed, rendered for the navbar and re-fetched when the bell
     * opens. Never cached: the whole point is that it is current.
     */
    public function feed(): Response
    {
        $userId = auth()->id();

        return response()
            ->view('layouts.partials.notification-feed', [
                'notifications' => Notification::query()
                    ->where('user_id', $userId)
                    ->recentFirst()
                    ->limit(self::FEED_SIZE)
                    ->get(),
                'unreadCount' => Notification::query()
                    ->where('user_id', $userId)
                    ->unread()
                    ->count(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Badge count for polling. Cheap on purpose: it runs on a timer.
     */
    public function unreadCount(): JsonResponse
    {
        return response()
            ->json([
                'badge' => Notification::unreadBadgeFor(auth()->id()),
                'unread' => Notification::query()
                    ->where('user_id', auth()->id())
                    ->unread()
                    ->count(),
            ])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Opening a notification marks it read and sends the reader onward.
     */
    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markRead();

        $target = $notification->destination();

        return $target !== null
            ? redirect()->to($target)
            : redirect()->route('notifications.index');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
