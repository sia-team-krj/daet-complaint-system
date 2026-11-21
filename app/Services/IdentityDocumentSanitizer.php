<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Strips EXIF/GPS and normalizes orientation when php-gd + php-exif are present.
 *
 * Until php-gd and php-exif are deployed, this is a safe no-op so uploads still
 * work; the strongest path (re-encoding to JPEG) is available after deployment.
 */
class IdentityDocumentSanitizer
{
    public function isAvailable(): bool
    {
        return in_array('gd', get_loaded_extensions(), true)
            && function_exists('imagecreatetruecolor')
            && function_exists('exif_read_data');
    }

    public function sanitize(UploadedFile $file): UploadedFile
    {
        if (! $this->isAvailable() || ! $file->isValid()) {
            return $file;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?? '');
        $source = $file->getRealPath();

        if ($extension === 'png' && function_exists('imagecreatefrompng')) {
            $image = @imagecreatefrompng($source);
        } elseif (in_array($extension, ['jpg', 'jpeg'], true) && function_exists('imagecreatefromjpeg')) {
            $image = @imagecreatefromjpeg($source);
        } else {
            return $file;
        }

        if ($image === false) {
            return $file;
        }

        $orientation = @exif_read_data($source)['Orientation'] ?? null;

        if ($orientation) {
            $image = $this->applyOrientation($image, (int) $orientation);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'id_') ?: throw new RuntimeException('Could not create a temporary image file.');

        if (! @imagejpeg($image, $tmp, 85)) {
            @imagedestroy($image);
            @unlink($tmp);
            return $file;
        }

        @imagedestroy($image);

        return new UploadedFile($tmp, 'id.jpg', 'image/jpeg', null, true);
    }

    private function applyOrientation($image, int $orientation)
    {
        return match ($orientation) {
            2 => imageflip($image, IMG_FLIP_VERTICAL),
            3 => imageflip($image, IMG_FLIP_BOTH),
            4 => imageflip($image, IMG_FLIP_HORIZONTAL),
            5 => imageflip($image, IMG_FLIP_BOTH),
            default => $image,
        };
    }
}
