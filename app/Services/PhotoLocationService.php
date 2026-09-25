<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class PhotoLocationService
{
    /**
     * Extract GPS coordinates from an uploaded photo.
     *
     * JPEG EXIF is parsed in PHP as a fallback because the exif extension
     * is not enabled in every deployment. The location is advisory input;
     * the department still verifies the report before acting on it.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function extract(UploadedFile $photo): ?array
    {
        $path = $photo->getRealPath();

        if (! $path || ! is_readable($path)) {
            return null;
        }

        $coordinates = $this->readNativeExif($path) ?? $this->readJpegExif($path);

        if (! $coordinates) {
            return null;
        }

        $latitude = (float) $coordinates['latitude'];
        $longitude = (float) $coordinates['longitude'];

        if (! is_finite($latitude) || ! is_finite($longitude)
            || $latitude < -90 || $latitude > 90
            || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [
            'latitude' => round($latitude, 8),
            'longitude' => round($longitude, 8),
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function readNativeExif(string $path): ?array
    {
        if (! function_exists('exif_read_data')) {
            return null;
        }

        $exif = @exif_read_data($path, 'IFD0', true);
        if (! is_array($exif)) {
            return null;
        }

        $latitude = $this->dmsToDecimal($exif['GPSLatitude'] ?? null);
        $longitude = $this->dmsToDecimal($exif['GPSLongitude'] ?? null);
        $latitudeRef = strtoupper((string) ($exif['GPSLatitudeRef'] ?? 'N'));
        $longitudeRef = strtoupper((string) ($exif['GPSLongitudeRef'] ?? 'E'));

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => $latitudeRef === 'S' ? -$latitude : $latitude,
            'longitude' => $longitudeRef === 'W' ? -$longitude : $longitude,
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function readJpegExif(string $path): ?array
    {
        $contents = @file_get_contents($path);
        if (! is_string($contents) || substr($contents, 0, 2) !== "\xFF\xD8") {
            return null;
        }

        $length = strlen($contents);
        $offset = 2;

        while ($offset + 4 <= $length) {
            if ($contents[$offset] !== "\xFF") {
                $offset++;

                continue;
            }

            while ($offset < $length && $contents[$offset] === "\xFF") {
                $offset++;
            }

            if ($offset >= $length) {
                break;
            }

            $marker = ord($contents[$offset++]);
            if ($marker === 0xD9 || $marker === 0xDA) {
                break;
            }

            if ($offset + 2 > $length) {
                break;
            }

            $segmentLength = $this->readUnsignedShort($contents, $offset, false);
            if ($segmentLength < 2 || $offset + $segmentLength > $length) {
                break;
            }

            $segment = substr($contents, $offset + 2, $segmentLength - 2);
            if ($marker === 0xE1 && str_starts_with($segment, "Exif\0\0")) {
                return $this->readTiffExif(substr($segment, 6));
            }

            $offset += $segmentLength;
        }

        return null;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function readTiffExif(string $tiff): ?array
    {
        if (strlen($tiff) < 8) {
            return null;
        }

        $byteOrder = substr($tiff, 0, 2);
        if ($byteOrder !== 'II' && $byteOrder !== 'MM') {
            return null;
        }

        $littleEndian = $byteOrder === 'II';
        if ($this->readUnsignedShort($tiff, 2, $littleEndian) !== 42) {
            return null;
        }

        $ifdOffset = $this->readUnsignedLong($tiff, 4, $littleEndian);
        $ifd = $this->readIfd($tiff, $ifdOffset, $littleEndian);
        $gpsOffset = $ifd[0x8825] ?? null;
        if (! is_int($gpsOffset)) {
            return null;
        }

        $gps = $this->readIfd($tiff, $gpsOffset, $littleEndian);
        $latitude = $this->rationalArrayToDecimal($gps[2] ?? null);
        $longitude = $this->rationalArrayToDecimal($gps[4] ?? null);
        $latitudeRef = is_string($gps[1] ?? null) ? strtoupper($gps[1]) : 'N';
        $longitudeRef = is_string($gps[3] ?? null) ? strtoupper($gps[3]) : 'E';

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => $latitudeRef === 'S' ? -$latitude : $latitude,
            'longitude' => $longitudeRef === 'W' ? -$longitude : $longitude,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function readIfd(string $tiff, int $offset, bool $littleEndian): array
    {
        if ($offset < 0 || $offset + 2 > strlen($tiff)) {
            return [];
        }

        $count = $this->readUnsignedShort($tiff, $offset, $littleEndian);
        $entries = [];
        $typeSizes = [1 => 1, 2 => 1, 3 => 2, 4 => 4, 5 => 8, 7 => 1, 9 => 4, 10 => 8];

        for ($index = 0; $index < $count; $index++) {
            $entryOffset = $offset + 2 + ($index * 12);
            if ($entryOffset + 12 > strlen($tiff)) {
                break;
            }

            $tag = $this->readUnsignedShort($tiff, $entryOffset, $littleEndian);
            $type = $this->readUnsignedShort($tiff, $entryOffset + 2, $littleEndian);
            $valueCount = $this->readUnsignedLong($tiff, $entryOffset + 4, $littleEndian);
            $typeSize = $typeSizes[$type] ?? 0;
            $byteCount = $typeSize * $valueCount;
            $valueOffset = $byteCount > 4
                ? $this->readUnsignedLong($tiff, $entryOffset + 8, $littleEndian)
                : $entryOffset + 8;

            if ($typeSize === 0 || $valueOffset < 0 || $valueOffset + $byteCount > strlen($tiff)) {
                continue;
            }

            $entries[$tag] = match ($type) {
                2 => rtrim(substr($tiff, $valueOffset, $byteCount), "\0"),
                5 => $this->readRationals($tiff, $valueOffset, $valueCount, $littleEndian),
                default => $this->readNumericValue($tiff, $valueOffset, $type, $valueCount, $littleEndian),
            };
        }

        return $entries;
    }

    /**
     * @return array<int, float>
     */
    private function readRationals(string $tiff, int $offset, int $count, bool $littleEndian): array
    {
        $values = [];
        for ($index = 0; $index < $count; $index++) {
            $numerator = $this->readUnsignedLong($tiff, $offset + ($index * 8), $littleEndian);
            $denominator = $this->readUnsignedLong($tiff, $offset + ($index * 8) + 4, $littleEndian);
            $values[] = $denominator === 0 ? 0.0 : $numerator / $denominator;
        }

        return $values;
    }

    private function readNumericValue(string $tiff, int $offset, int $type, int $count, bool $littleEndian): int|float
    {
        if ($type === 3) {
            return $this->readUnsignedShort($tiff, $offset, $littleEndian);
        }

        if ($type === 4 || $type === 9) {
            return $this->readUnsignedLong($tiff, $offset, $littleEndian);
        }

        return $count;
    }

    private function readUnsignedShort(string $data, int $offset, bool $littleEndian): int
    {
        if ($offset < 0 || $offset + 2 > strlen($data)) {
            return 0;
        }

        $value = unpack($littleEndian ? 'v' : 'n', substr($data, $offset, 2));

        return (int) ($value[1] ?? 0);
    }

    private function readUnsignedLong(string $data, int $offset, bool $littleEndian): int
    {
        if ($offset < 0 || $offset + 4 > strlen($data)) {
            return 0;
        }

        $value = unpack($littleEndian ? 'V' : 'N', substr($data, $offset, 4));

        return (int) ($value[1] ?? 0);
    }

    private function rationalArrayToDecimal(mixed $value): ?float
    {
        if (! is_array($value) || count($value) < 3) {
            return null;
        }

        $degrees = (float) $value[0];
        $minutes = (float) $value[1];
        $seconds = (float) $value[2];

        return $degrees + ($minutes / 60) + ($seconds / 3600);
    }

    private function dmsToDecimal(mixed $value): ?float
    {
        if (! is_array($value)) {
            return null;
        }

        $parts = [];
        foreach ($value as $part) {
            if (is_array($part) && count($part) >= 2) {
                $parts[] = ((float) $part[0]) / ((float) $part[1] ?: 1);
            } elseif (is_numeric($part)) {
                $parts[] = (float) $part;
            }
        }

        return count($parts) >= 3
            ? $parts[0] + ($parts[1] / 60) + ($parts[2] / 3600)
            : null;
    }
}
