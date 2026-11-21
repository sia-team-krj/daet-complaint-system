<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Encrypted private-MinIO storage for ID front and selfie images.
 *
 * Files are written as `identity/{userId}/{uuid}.enc` with no original
 * filename, encrypted at rest, and can be purged or streamed back to an admin.
 */
class IdentityStorageService
{
    public function storeIdFront(UploadedFile $file, int $userId): string
    {
        return $this->store($file, $userId, 'id_front');
    }

    public function storeSelfie(UploadedFile $file, int $userId): string
    {
        return $this->store($file, $userId, 'selfie');
    }

    private function store(UploadedFile $file, int $userId, string $kind): string
    {
        $file = app(IdentityDocumentSanitizer::class)->sanitize($file);

        $path = "identity/{$userId}/".Str::uuid().'.'.$kind.'.enc';
        $contents = Crypt::encryptString((string) file_get_contents($file->getPathname()));

        Storage::disk('minio')->put($path, $contents);

        return $path;
    }

    public function documentExists(string $path): bool
    {
        return Storage::disk('minio')->exists($path);
    }

    public function deleteDocument(string $path): bool
    {
        if (! $path || ! Storage::disk('minio')->exists($path)) {
            return false;
        }

        Storage::disk('minio')->delete($path);

        return true;
    }

    /**
     * Stream the decrypted image. Callers must send it with no-store headers and
     * close the stream.
     *
     * @return resource
     */
    public function streamDocument(string $path)
    {
        $encrypted = Storage::disk('minio')->get($path);

        if ($encrypted === null || $encrypted === '') {
            throw new RuntimeException('The identity document could not be read.');
        }

        try {
            $decrypted = Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            throw new RuntimeException('The identity document could not be decrypted.', 0, $e);
        }

        $stream = fopen('php://temp', 'rb+');
        if ($stream === false) {
            throw new RuntimeException('The identity document could not be streamed.');
        }

        fwrite($stream, $decrypted);
        rewind($stream);

        return $stream;
    }

    public function documentSize(string $path): int
    {
        return (int) Storage::disk('minio')->size($path);
    }

    /**
     * Delete both proof files. Returns the number of deleted files.
     */
    public function deleteVerificationFiles(?string $idFrontPath, ?string $selfiePath): int
    {
        $deleted = 0;
        foreach (array_filter([$idFrontPath, $selfiePath]) as $path) {
            if ($this->deleteDocument((string) $path)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
