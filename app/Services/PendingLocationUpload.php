<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Holds uploaded evidence between the two submissions of a location decision.
 *
 * When a photo's own GPS disagrees with the pin the resident placed, the
 * complaint is not filed until they say which one they meant. That means asking
 * them to submit twice, and making someone re-attach three photos from a phone
 * to answer a yes/no question is not an acceptable price — so the uploads are
 * parked here and picked up on the second pass.
 *
 * Files live under a single-session-scoped token rather than in a temp directory
 * keyed by nothing, so one resident cannot reach another's staged upload by
 * guessing a filename.
 */
class PendingLocationUpload
{
    private const PREFIX = 'pending-complaint-uploads';

    public function __construct(
        private readonly string $sessionKey = 'pending_location_upload',
    ) {}

    /**
     * Move already-stored photos into the pending area and remember them.
     *
     * @param  array<int, string>  $storedPaths
     */
    public function stage(array $storedPaths, array $context): void
    {
        $this->discard();

        $token = bin2hex(random_bytes(20));
        $target = $this->directory($token);

        foreach ($storedPaths as $path) {
            $moved = $this->disk()->move($path, $target.'/'.basename($path));

            if ($moved === false) {
                // Roll back rather than leave half the evidence in limbo.
                $this->purge($token);
                throw new RuntimeException('The evidence photo could not be held for review.');
            }
        }

        session()->put($this->sessionKey, [
            'token' => $token,
            'paths' => array_map(fn (string $p): string => $target.'/'.basename($p), $storedPaths),
            'context' => $context,
        ]);
    }

    /**
     * The staged photos and the two candidate locations, or null if this session
     * has nothing pending.
     *
     * @return array{token: string, paths: array<int, string>, context: array<string, mixed>}|null
     */
    public function pending(): ?array
    {
        $pending = session()->get($this->sessionKey);

        return is_array($pending) && isset($pending['token']) ? $pending : null;
    }

    /**
     * Move the staged photos back to where a real complaint expects them and
     * clear the session entry.
     *
     * @return array<int, string>
     */
    public function release(): array
    {
        $pending = $this->pending();

        if ($pending === null) {
            return [];
        }

        $released = [];

        foreach ($pending['paths'] as $path) {
            $target = $this->destinationFor(basename($path));

            if ($this->disk()->move($path, $target) !== false) {
                $released[] = $target;
            }
        }

        $this->forget();

        return $released;
    }

    /** Delete the staged files and the session entry. */
    public function discard(): void
    {
        $pending = $this->pending();

        if ($pending !== null) {
            $this->purge($pending['token']);
        }

        $this->forget();
    }

    /**
     * True when a photo's GPS and the resident's pin are far enough apart to be
     * worth a question.
     *
     * A couple of metres is just GPS noise, and asking someone to arbitrate over
     * their own street corner because of rounding would be silly.
     */
    public function isConflict(
        ?array $photoLocation,
        float|int|string|null $pinLatitude,
        float|int|string|null $pinLongitude,
    ): bool {
        if ($photoLocation === null) {
            return false;
        }

        if (! is_numeric($pinLatitude) || ! is_numeric($pinLongitude)) {
            return false;
        }

        $threshold = (float) config('complaints.location_conflict_threshold_metres', 100);

        return $this->metresBetween(
            (float) $photoLocation['latitude'],
            (float) $photoLocation['longitude'],
            (float) $pinLatitude,
            (float) $pinLongitude,
        ) > $threshold;
    }

    public function metresBetween(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radius = 6_378_100.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function disk()
    {
        return Storage::disk('minio');
    }

    private function directory(string $token): string
    {
        return self::PREFIX.'/'.$token;
    }

    /** Evidence paths on the complaint keep their existing `complaints/...` shape. */
    private function destinationFor(string $filename): string
    {
        return 'complaints/'.bin2hex(random_bytes(8)).'-'.$filename;
    }

    private function purge(string $token): void
    {
        $directory = $this->directory($token);

        foreach ($this->disk()->files($directory) as $file) {
            $this->disk()->delete($file);
        }

        $this->disk()->deleteDirectory($directory);
    }

    private function forget(): void
    {
        session()->forget($this->sessionKey);
    }

    /** Unused today, kept so the class reads as a complete lifecycle. */
    public function hasStagedFiles(): bool
    {
        $pending = $this->pending();

        return $pending !== null && $this->disk()->exists($this->directory($pending['token']));
    }
}
