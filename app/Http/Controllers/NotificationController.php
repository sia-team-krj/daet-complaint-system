<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(): View
    {
        $userId = auth()->id();

        return view('notifications.index', [
            'notifications' => Notification::query()
                ->where('user_id', $userId)
                ->recentFirst()
                ->paginate(20),
            'unreadCount' => Notification::query()
                ->where('user_id', $userId)
                ->unread()
                ->count(),
        ]);
    }

    /**
     * The dropdown feed, rendered for the navbar.
     */
    public function feed(): View
    {
        $userId = auth()->id();

        return view('layouts.partials.notification-feed', [
            'notifications' => Notification::query()
                ->where('user_id', $userId)
                ->recentFirst()
                ->limit(self::FEED_SIZE)
                ->get(),
            'unreadCount' => Notification::query()
                ->where('user_id', $userId)
                ->unread()
                ->count(),
        ]);
    }

    /**
     * Badge count for polling. Cheap on purpose: it runs on a timer.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'badge' => Notification::unreadBadgeFor(auth()->id()),
            'unread' => Notification::query()
                ->where('user_id', auth()->id())
                ->unread()
                ->count(),
        ]);
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
