<?php

namespace App\Http\Controllers;

use App\Models\ComplaintCategory;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $settings = SystemSetting::pluck('value', 'key')->all();
        $categories = ComplaintCategory::orderBy('sort_order')->orderBy('label_en')->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        $defaults = [
            'default_locale' => 'en',
            'overdue_days' => '5',
            'waiting_reminder_days' => '3',
            'invitation_expires_in_hours' => '48',
            'temp_password_expires_in_hours' => '72',
            'password_min_length' => '8',
            'session_timeout_minutes' => '120',
            'sender_name' => 'Daet Listens',
            'footer_text' => 'Municipality of Daet · Camarines Norte · Official Transparency Complaint Portal',
            'contact_text' => 'For concerns, contact the Municipality of Daet through the Transparency Register or visit the municipal hall.',
            'audit_retention_days' => '365',
            'barangays_json' => json_encode(User::BARANGAYS),
        ];

        foreach ($defaults as $key => $value) {
            $settings[$key] ??= $value;
        }

        return view('admin.settings.index', compact('settings', 'categories', 'departments'));
    }

    public function update(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'default_locale' => ['required', 'string', \Illuminate\Validation\Rule::in(['en', 'fil'])],
            'overdue_days' => ['required', 'integer', 'min:1', 'max:365'],
            'waiting_reminder_days' => ['required', 'integer', 'min:1', 'max:365'],
            'invitation_expires_in_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'temp_password_expires_in_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
            'session_timeout_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'sender_name' => ['required', 'string', 'max:100'],
            'subject_staff_welcome' => ['nullable', 'string', 'max:255'],
            'subject_invitation' => ['nullable', 'string', 'max:255'],
            'subject_reset' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['required', 'string', 'max:500'],
            'contact_text' => ['required', 'string', 'max:1000'],
            'audit_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'barangays' => ['required', 'string'],
        ]);

        $before = SystemSetting::pluck('value', 'key')->all();

        foreach ($validated as $key => $value) {
            if ($key === 'barangays') {
                $list = collect(preg_split('/\r\n|\r|\n/', (string) $value))
                    ->map(fn ($b) => trim($b))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                SystemSetting::setValue('barangays_json', json_encode($list));
                continue;
            }
            SystemSetting::setValue($key, (string) $value);
        }

        $after = SystemSetting::pluck('value', 'key')->all();
        $changed = array_diff_assoc($after, $before);

        $activityLogger->log(
            'system.settings_updated',
            'System settings updated.',
            null,
            metadata: [
                'changed_keys' => array_keys($changed),
                'changed' => $changed,
                'created_by' => auth()->id(),
            ],
        );

        return redirect()->route('admin.settings.index')->with('status', __('admin_settings.saved'));
    }

    public function storeCategory(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:50', 'unique:complaint_categories,key', 'regex:/^[a-z0-9_]+$/'],
            'label_en' => ['required', 'string', 'max:100'],
            'label_fil' => ['nullable', 'string', 'max:100'],
        ]);

        $category = ComplaintCategory::create([
            'key' => $validated['key'],
            'label_en' => $validated['label_en'],
            'label_fil' => $validated['label_fil'] ?? null,
            'is_active' => true,
            'sort_order' => (ComplaintCategory::max('sort_order') ?? 0) + 1,
        ]);

        $activityLogger->log('system.category_created', "Complaint category {$category->key} created.", $category, metadata: ['key' => $category->key]);

        return redirect()->route('admin.settings.index')->with('status', __('admin_settings.saved'));
    }

    public function updateCategory(Request $request, ComplaintCategory $category, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'label_en' => ['required', 'string', 'max:100'],
            'label_fil' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'label_en' => $validated['label_en'],
            'label_fil' => $validated['label_fil'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $activityLogger->log('system.category_updated', "Complaint category {$category->key} updated.", $category, metadata: ['key' => $category->key, 'is_active' => $category->is_active]);

        return redirect()->route('admin.settings.index')->with('status', __('admin_settings.saved'));
    }

    public function toggleCategory(ComplaintCategory $category, ActivityLogger $activityLogger): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        $activityLogger->log('system.category_updated', "Complaint category {$category->key} ".($category->is_active ? 'activated' : 'deactivated').'.', $category, metadata: ['key' => $category->key, 'is_active' => $category->is_active]);

        return redirect()->route('admin.settings.index')->with('status', __('admin_settings.saved'));
    }
}
