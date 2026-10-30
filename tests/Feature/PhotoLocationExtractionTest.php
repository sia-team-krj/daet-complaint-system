<?php

namespace Tests\Feature;

use App\Services\PhotoLocationService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The hand-rolled EXIF parser is the only thing reading a photo's location on a
 * server without the exif extension, and this project runs on one.
 *
 * It had no test at all, so it could have been broken and nothing would have
 * noticed — the failure would look exactly like "the browser stripped the GPS",
 * which is common enough to be believed. The fixture below is a real geotagged
 * JPEG built byte by byte, so the parser is exercised end to end rather than
 * mocked away.
 */
class PhotoLocationExtractionTest extends TestCase
{
    public function test_it_reads_coordinates_out_of_a_geotagged_jpeg(): void
    {
        $path = $this->geotaggedJpeg(14.1122, 122.9553);

        $extracted = $this->extract($path);

        $this->assertIsArray($extracted, 'The GPS reader returned nothing for a genuinely geotagged JPEG.');
        $this->assertEqualsWithDelta(14.1122, $extracted['latitude'], 0.0001);
        $this->assertEqualsWithDelta(122.9553, $extracted['longitude'], 0.0001);
    }

    public function test_it_honours_the_southern_and_western_hemispheres(): void
    {
        // A negative value must come back negative, not silently positive, or a
        // complaint from the other side of the world files in Daet.
        $path = $this->geotaggedJpeg(-33.8688, -151.2093, 'S', 'W');

        $extracted = $this->extract($path);

        $this->assertIsArray($extracted);
        $this->assertEqualsWithDelta(-33.8688, $extracted['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-151.2093, $extracted['longitude'], 0.0001);
    }

    public function test_it_returns_nothing_for_a_photo_with_no_gps(): void
    {
        $this->assertNull($this->extract($this->plainJpeg()));
    }

    public function test_it_returns_nothing_for_a_file_that_is_not_a_jpeg(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'notjpeg');
        file_put_contents($path, 'this is plainly not an image');

        $this->assertNull($this->extract($path));

        unlink($path);
    }

    public function test_a_truncated_geotagged_jpeg_reads_as_nothing_rather_than_garbage(): void
    {
        $full = $this->geotaggedJpeg(14.1122, 122.9553);
        $this->assertIsArray($this->extract($full), 'fixture is broken, not the reader');

        // A file cut off mid-IFD must not yield a half-read coordinate. A wrong
        // location is worse than none, because it looks legitimate.
        $truncated = $this->tempPath();
        file_put_contents($truncated, substr((string) file_get_contents($full), 0, 60));

        $this->assertNull($this->extract($truncated));
    }

    private function extract(string $path): ?array
    {
        $service = app(PhotoLocationService::class);

        return $service->extract(new UploadedFile($path, 'evidence.jpg', 'image/jpeg', null, true));
    }

    /**
     * A byte-accurate geotagged JPEG.
     *
     * Two things about EXIF bit this file, and both are easy to get wrong:
     *
     *  - TIFF is little-endian ('II'), but JPEG segment lengths are big-endian.
     *    Mixing them up produces a file that looks plausible and parses to null.
     *  - Every TIFF entry is exactly 12 bytes and its inline value field is
     *    always 4 bytes, whatever the declared type or count.
     */
    private function geotaggedJpeg(
        float $latitude,
        float $longitude,
        string $latitudeRef = 'N',
        string $longitudeRef = 'E',
    ): string {
        $u16 = fn (int $v): string => pack('v', $v);
        $u32 = fn (int $v): string => pack('V', $v);
        $be16 = fn (int $v): string => pack('n', $v);
        $inline4 = fn (string $b): string => str_pad(substr($b, 0, 4), 4, "\0");

        $dms = function (float $d): array {
            $a = abs($d);
            $deg = (int) $a;
            $min = (int) (($a - $deg) * 60);
            $sec = ($a - $deg - ($min / 60)) * 3600;

            return [$deg, $min, $sec];
        };

        $rationals = function (array $dms) use ($u32): string {
            return $u32($dms[0]) . $u32(1)
                . $u32($dms[1]) . $u32(1)
                . $u32((int) round($dms[2] * 10000)) . $u32(10000);
        };

        $header = 'II' . $u16(42) . $u32(8);
        $ifd0At = strlen($header);
        $gpsAt = $ifd0At + 18;
        $latAt = $gpsAt + (2 + (6 * 12) + 4);
        $lngAt = $latAt + 24;

        $ifd0 = $u16(1) . $u16(0x8825) . $u16(4) . $u32(1) . $u32($gpsAt) . $u32(0);

        $gps = $u16(6)
            . $u16(0x0000) . $u16(1) . $u32(4) . $inline4(pack('C', 2))
            . $u16(0x0001) . $u16(2) . $u32(2) . $inline4($latitudeRef)
            . $u16(0x0002) . $u16(5) . $u32(3) . $u32($latAt)
            . $u16(0x0003) . $u16(2) . $u32(2) . $inline4($longitudeRef)
            . $u16(0x0004) . $u16(5) . $u32(3) . $u32($lngAt)
            . $u16(0x0005) . $u16(1) . $u32(1) . $inline4(pack('C', 0))
            . $u32(0);

        $tiff = $header . $ifd0 . $gps . $rationals($dms($latitude)) . $rationals($dms($longitude));
        $app1 = "Exif\0\0" . $tiff;

        $jpeg = "\xFF\xD8"
            . "\xFF\xE1" . $be16(strlen($app1) + 2) . $app1
            . "\xFF\xDA" . $be16(8) . "\x01\x01\x00\x00\x3F\x00"
            . "\xFF\xD9";

        $path = $this->tempPath();
        file_put_contents($path, $jpeg);

        return $path;
    }

    private function plainJpeg(): string
    {
        $path = $this->tempPath();
        // SOI, a JFIF APP0 with no EXIF, then EOI.
        file_put_contents($path, "\xFF\xD8" . "\xFF\xE0" . pack('n', 16) . "JFIF\0" . str_repeat("\0", 9) . "\xFF\xD9");

        return $path;
    }

    private function tempPath(): string
    {
        return tempnam(sys_get_temp_dir(), 'geo').'.jpg';
    }
}
