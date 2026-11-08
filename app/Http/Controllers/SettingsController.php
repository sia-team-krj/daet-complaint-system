<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user()->load('department');
        $tab = $request->query('tab', 'profile');
        if (!in_array($tab, ['profile', 'security', 'notifications', 'preferences', 'privacy'], true)) {
            $tab = 'profile';
        }

        $sessions = collect();
        if ($tab === 'security') {
            $raw = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->limit(50)
                ->get();

            $sessions = $raw->map(function ($s) use ($request) {
                $ua = (string) ($s->user_agent ?? '');
                $browser = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'Browser')));
                $os = preg_match('/Windows/', $ua) ? 'Windows' : (preg_match('/Android/', $ua) ? 'Android' : (preg_match('/iPhone|iPad/', $ua) ? 'iOS' : (preg_match('/Mac OS/', $ua) ? 'macOS' : (preg_match('/Linux/', $ua) ? 'Linux' : 'Unknown OS'))));
                $ip = (string) ($s->ip_address ?? '');
                $parts = explode('.', $ip);
                $masked = count($parts) === 4 ? $parts[0].'.'.$parts[1].'.xxx.xxx' : ($ip ?: '—');
                $last = \Carbon\Carbon::createFromTimestamp($s->last_activity);
                return (object) [
                    'id' => $s->id,
                    'ip' => $masked,
                    'ip_full' => $ip,
                    'user_agent' => $ua,
                    'friendly' => $browser.' on '.$os,
                    'last_activity' => $last,
                    'last_relative' => $last->diffForHumans(),
                    'last_full' => $last->translatedFormat('F j, Y g:i A'),
                    'is_current' => $s->id === $request->session()->getId(),
                ];
            })->unique(fn ($s) => $s->friendly.'|'.$s->ip_full);
        }

        $notificationEvents = match ($user->role) {
            'citizen' => ['status_changed', 'staff_replied', 'needs_my_response'],
            'admin' => ['new_assignment', 'overdue_complaint', 'resident_replied', 'new_unassigned', 'admin_overdue'],
            default => ['new_assignment', 'overdue_complaint', 'resident_replied'],
        };

        return view('settings.index', compact('user', 'tab', 'sessions', 'notificationEvents'));
    }

    public function updateProfile(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user();
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
        ];
        if ($user->role === 'citizen') {
            $rules['barangay'] = ['nullable', 'string', Rule::in(User::BARANGAYS)];
        }

        $validated = $request->validate($rules);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => strtolower(trim($validated['email'])),
            'contact_number' => $validated['contact_number'] ?? null,
            'barangay' => $user->role === 'citizen' ? ($validated['barangay'] ?? null) : $user->barangay,
        ]);

        $activityLogger->log('profile.updated', "{$user->full_name} updated their profile.", $user, metadata: ['updated_fields' => array_keys($validated)]);

        return redirect()->route('settings', ['tab' => 'profile'])->with('status', __('settings.profile.saved'));
    }

    public function updatePassword(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user();
        $minLength = max(8, (int) \App\Models\SystemSetting::getValue('password_min_length', '8'));

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::min($minLength)->mixedCase()->numbers()],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => __('settings.security.current_incorrect')]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['new_password']),
            'must_change_password' => false,
            'password_expires_at' => null,
        ])->save();

        $activityLogger->log('profile.password_changed', "{$user->full_name} changed their account password.", $user);

        return redirect()->route('settings', ['tab' => 'security'])->with('status', __('settings.security.password_updated'));
    }

    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $current = $request->session()->getId();

        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $current)->delete();

        return redirect()->route('settings', ['tab' => 'security'])->with('status', __('settings.security.logout_other_done'));
    }

    public function logoutSession(Request $request, string $sessionId): RedirectResponse
    {
        $user = Auth::user();
        $current = $request->session()->getId();

        if ($sessionId === $current) {
            return redirect()->route('settings', ['tab' => 'security'])->with('error', __('settings.security.this_device'));
        }

        DB::table('sessions')->where('user_id', $user->id)->where('id', $sessionId)->delete();

        return redirect()->route('settings', ['tab' => 'security'])->with('status', __('settings.security.logout_other_done'));
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(['in_app', 'email'])],
            'events' => ['nullable', 'array'],
            'events.*' => ['string'],
            'default_queue_view' => ['nullable', 'string', Rule::in(['all', 'mine', 'unassigned'])],
            'daily_digest' => ['nullable', 'boolean'],
        ]);

        $user->forceFill([
            'notification_preferences' => [
                'channels' => $validated['channels'] ?? [],
                'events' => $validated['events'] ?? [],
            ],
            'default_queue_view' => $user->role === 'staff' ? ($validated['default_queue_view'] ?? null) : null,
            'daily_digest' => $user->role === 'staff' ? (bool) ($validated['daily_digest'] ?? false) : false,
        ])->save();

        return redirect()->route('settings', ['tab' => 'notifications'])->with('status', __('settings.notifications.saved'));
    }

    public function updatePreferences(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'locale' => ['nullable', 'string', Rule::in(array_keys(config('localization.supported', [])))],
            'text_size' => ['nullable', 'string', Rule::in(['normal', 'large'])],
        ]);

        $updates = [];
        if (array_key_exists('locale', $validated) && $validated['locale']) {
            $updates['locale'] = $validated['locale'];
            $request->session()->put('locale', $validated['locale']);
        }
        if (array_key_exists('text_size', $validated) && $validated['text_size']) {
            $updates['text_size'] = $validated['text_size'];
        }
        // Partial updates (auto-save toggles/segmented controls) only touch
        // the fields that were sent.
        if ($request->expectsJson()) {
            $user->forceFill($updates)->save();
            return response()->json(['ok' => true, 'preferences' => $updates, 'message' => __('settings.preferences.saved')]);
        }

        $user->forceFill([
            'locale' => $updates['locale'] ?? $user->locale,
            'text_size' => $updates['text_size'] ?? $user->text_size,
        ])->save();

        return redirect()->route('settings', ['tab' => 'preferences'])->with('status', __('settings.preferences.saved'));
    }

    public function downloadData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $data = [
            'profile' => $user->only(['id', 'first_name', 'last_name', 'email', 'contact_number', 'barangay', 'role', 'department_id', 'locale', 'created_at']),
            'complaints' => $user->complaints()->get(['id', 'ticket_id', 'category', 'title', 'description', 'status', 'created_at']),
            'notifications' => \App\Models\Notification::where('user_id', $user->id)->get(['id', 'type', 'title', 'body', 'read_at', 'created_at']),
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->json($data)->header('Content-Disposition', 'attachment; filename="daet-listens-data.json"');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'delete_email' => ['required', 'string', 'in:'.$user->email],
            'delete_password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['delete_password'], $user->password)) {
            return back()->withErrors(['delete_password' => __('settings.security.current_incorrect')]);
        }

        $user->update(['is_active' => false]);
        $user->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('settings.privacy.delete_done'));
    }
}
