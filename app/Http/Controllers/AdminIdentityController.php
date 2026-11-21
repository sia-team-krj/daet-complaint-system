<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Services\ActivityLogger;
use App\Services\IdentityStorageService;
use App\Services\IdentityVerificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Global-admin identity queue and review screen.
 */
class AdminIdentityController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $status = (string) $request->input('status', IdentityVerification::PENDING);
        $search = Str::lower(trim((string) $request->input('search', '')));

        $query = IdentityVerification::query()
            ->with(['user', 'reviewer', 'possibleDuplicate'])
            ->latest();

        if (in_array($status, [IdentityVerification::PENDING, IdentityVerification::APPROVED, IdentityVerification::REJECTED, IdentityVerification::REVOKED, IdentityVerification::EXPIRED, IdentityVerification::CANCELLED], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search): void {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        $verifications = $query->paginate(20, ['*'], 'page', $request->integer('page') ?: 1);

        $pendingCount = IdentityVerification::query()
            ->where('status', IdentityVerification::PENDING)
            ->count();

        return view('admin.identity.index', [
            'verifications' => $verifications,
            'status' => $status,
            'search' => $search,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function show(IdentityVerification $verification): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $storage = app(IdentityStorageService::class);

        return view('admin.identity.show', [
            'verification' => $verification,
            'user' => $verification->user,
            'idFrontExists' => $storage->documentExists($verification->id_front_path ?? ''),
            'selfieExists' => $storage->documentExists($verification->selfie_path ?? ''),
        ]);
    }

    public function document(IdentityVerification $verification, string $side, Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(in_array($side, ['id_front', 'selfie'], true), 404);

        $path = $side === 'id_front' ? ($verification->id_front_path ?? '') : ($verification->selfie_path ?? '');
        $storage = app(IdentityStorageService::class);

        if (! $path || ! $storage->documentExists($path)) {
            abort(404);
        }

        $stream = $storage->streamDocument($path);

        $head = fread($stream, 64) ?: '';
        rewind($stream);
        $mime = 'image/jpeg';
        if ($head !== '') {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($head);
            if (is_string($detected) && in_array($detected, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $mime = $detected;
            }
        }

        $response = new StreamedResponse(function () use ($stream): void {
            while (! feof($stream)) {
                $chunk = fread($stream, 65536);
                if ($chunk !== false && $chunk !== '') {
                    echo $chunk;
                }
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        });

        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Disposition', 'inline; filename="identity-document.jpg"');
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        app(ActivityLogger::class)->log(
            'identity.document_viewed',
            auth()->user()->full_name.' viewed an identity document.',
            $verification,
            metadata: ['side' => $side, 'verification_id' => $verification->id],
        );

        return $response;
    }

    public function approve(IdentityVerification $verification, Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $service = app(IdentityVerificationService::class);
        $service->approve(auth()->user(), $verification);

        return redirect()->route('admin.identity.show', $verification)
            ->with('status', __('identity.admin.approved'));
    }

    public function reject(IdentityVerification $verification, Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        app(IdentityVerificationService::class)->reject(auth()->user(), $verification, $validated['reason']);

        return redirect()->route('admin.identity.show', $verification)
            ->with('status', __('identity.admin.rejected'));
    }
}
