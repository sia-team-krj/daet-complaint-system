<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\IdentityStorageService;
use App\Services\IdentityVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Resident-facing identity enrollment: submit files, cancel, and see status.
 */
class IdentityVerificationController extends Controller
{
    public function show(Request $request, IdentityVerificationService $verifications): View
    {
        $user = $request->user();
        abort_unless($user->isCitizen(), 403);

        $latest = $user->latestIdentityVerification();

        return view('identity.verify', [
            'user' => $user,
            'status' => $user->identity_status,
            'verification' => $latest,
            'rejectionReason' => $latest?->rejection_reason,
            'possibleDuplicate' => $latest?->possibleDuplicate,
            'idTypes' => config('identity.id_types', []),
        ]);
    }

    public function store(Request $request, IdentityStorageService $storage, IdentityVerificationService $verifications): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCitizen(), 403);

        $validated = $request->validate([
            'id_type' => ['required', 'string', Rule::in(config('identity.id_types', []))],
            'id_number' => ['required', 'string', 'max:128'],
            'id_front' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'terms' => ['accepted'],
            'consent_purpose' => ['required', 'boolean'],
            'consent_access' => ['required', 'boolean'],
            'consent_retention' => ['required', 'boolean'],
        ]);

        $paths = [
            'id_front_path' => $storage->storeIdFront($validated['id_front'], (int) $user->id),
            'selfie_path' => $storage->storeSelfie($validated['selfie'], (int) $user->id),
        ];

        try {
            $verification = $verifications->submit($user, $paths + $validated);
        } catch (\Throwable $e) {
            $storage->deleteVerificationFiles($paths['id_front_path'], $paths['selfie_path']);

            throw $e;
        }

        $message = $verification->possibleDuplicate !== null
            ? 'Your identity submission is pending review. Another account may already have this ID.'
            : 'Your identity submission is pending review.';

        return redirect()->route('identity.verify.show')
            ->with('status', __('identity.verify.submitted'))
            ->with('message', $message);
    }

    public function cancel(Request $request, IdentityVerificationService $verifications): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCitizen(), 403);

        $verification = $user->latestIdentityVerification();

        if ($verification === null || ! $verification->isPending()) {
            abort(403);
        }

        $verifications->cancel($user, $verification);

        return redirect()->route('identity.verify.show')
            ->with('status', __('identity.verify.cancelled'));
    }
}
