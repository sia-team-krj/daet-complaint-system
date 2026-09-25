<?php

namespace Tests\Unit;

use App\Services\PhotoLocationService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PhotoLocationServiceTest extends TestCase
{
    public function test_it_extracts_gps_coordinates_from_jpeg_exif(): void
    {
        $photo = UploadedFile::fake()->createWithContent(
            'geotagged.jpg',
            $this->geotaggedJpeg(),
        );

        $coordinates = app(PhotoLocationService::class)->extract($photo);

        $this->assertNotNull($coordinates);
        $this->assertEqualsWithDelta(14.1126389, $coordinates['latitude'], 0.00001);
        $this->assertEqualsWithDelta(122.976, $coordinates['longitude'], 0.00001);
    }

    public function test_it_returns_null_when_photo_has_no_gps_metadata(): void
    {
        $photo = UploadedFile::fake()->createWithContent(
            'plain.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
        );

        $this->assertNull(app(PhotoLocationService::class)->extract($photo));
    }

    private function geotaggedJpeg(): string
    {
        $gpsDataOffset = 80;
        $latitudeDataOffset = $gpsDataOffset;
        $longitudeDataOffset = $gpsDataOffset + 24;

        $ifd0 = pack('v', 1)
            .pack('vvV', 0x8825, 4, 1)
            .pack('V', 26)
            .pack('V', 0);

        $gpsIfd = pack('v', 4)
            .$this->asciiEntry(1, 'N')
            .$this->rationalEntry(2, $latitudeDataOffset)
            .$this->asciiEntry(3, 'E')
            .$this->rationalEntry(4, $longitudeDataOffset)
            .pack('V', 0);

        $gpsData = pack('VV', 14, 1)
            .pack('VV', 6, 1)
            .pack('VV', 455, 10)
            .pack('VV', 122, 1)
            .pack('VV', 58, 1)
            .pack('VV', 336, 10);

        $tiff = 'II'.pack('v', 42).pack('V', 8).$ifd0.$gpsIfd.$gpsData;
        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return "\xFF\xD8".$app1."\xFF\xD9";
    }

    private function asciiEntry(int $tag, string $value): string
    {
        return pack('vvV', $tag, 2, 2).str_pad($value, 4, "\0");
    }

    private function rationalEntry(int $tag, int $offset): string
    {
        return pack('vvV', $tag, 5, 3).pack('V', $offset);
    }
}
