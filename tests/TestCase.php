<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    /**
     * A real 1x1 JPEG. The EXIF segment is built on top of it.
     */
    private const ONE_PIXEL_JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCABkAGQBAREA/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==';

    protected function complaintEvidence(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'complaint-evidence.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
        );
    }

    /**
     * @return array<int, UploadedFile>
     */
    protected function complaintEvidenceSet(int $count = 3): array
    {
        return array_map(
            fn (int $index): UploadedFile => UploadedFile::fake()->createWithContent(
                "complaint-evidence-{$index}.png",
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            ),
            range(1, $count),
        );
    }

    /**
     * A real photograph that carries its own GPS coordinates.
     *
     * The staff proof capture reads the place from the photograph's EXIF
     * record, so a test that exercises the reader needs a file that really
     * carries one. This builds a 1x1 JPEG with a GPS EXIF segment attached.
     */
    protected function proofPhotoWithGps(float $latitude, float $longitude): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'proof').'.jpg';
        file_put_contents($path, $this->jpegWithGps($latitude, $longitude));

        return new UploadedFile($path, 'proof.jpg', 'image/jpeg', null, true);
    }

    /**
     * A JPEG with a GPS EXIF segment.
     *
     * The EXIF block is a TIFF structure: a header, an IFD pointing at the GPS
     * IFD, and four GPS entries -- two rationals for the position and two
     * ASCII references for the hemispheres. Real cameras put the JFIF segment
     * first and the EXIF segment second, and libmagic will not call the file
     * a JPEG at all otherwise.
     */
    protected function jpegWithGps(float $latitude, float $longitude): string
    {
        $jpeg = base64_decode(self::ONE_PIXEL_JPEG);

        // Degrees, minutes and seconds -- the form EXIF stores a position in.
        $toRationals = function (float $value): array {
            $absolute = abs($value);
            $degrees = (int) floor($absolute);
            $minutes = ($absolute - $degrees) * 60;
            $wholeMinutes = (int) floor($minutes);
            $seconds = ($minutes - $wholeMinutes) * 60;

            return [
                [$degrees, 1],
                [$wholeMinutes, 1],
                [(int) round($seconds * 10000), 10000],
            ];
        };

        $latitudeParts = $toRationals($latitude);
        $longitudeParts = $toRationals($longitude);

        $rational = fn (array $parts): string => pack('N', $parts[0]).pack('N', $parts[1]);
        $reference = fn (string $hemisphere): string => $hemisphere."\0\0\0";

        // The GPS IFD sits at TIFF offset 26; the position rationals follow it
        // at 80 and 104.
        $gpsIfd = pack('n', 4)
            .pack('n', 0x0001).pack('n', 2).pack('N', 2).$reference('N')
            .pack('n', 0x0002).pack('n', 5).pack('N', 3).pack('N', 80)
            .pack('n', 0x0003).pack('n', 2).pack('N', 2).$reference('E')
            .pack('n', 0x0004).pack('n', 5).pack('N', 3).pack('N', 104)
            .pack('N', 0);

        // The EXIF IFD points at the GPS IFD.
        $exifIfd = pack('n', 1)
            .pack('n', 0x8825).pack('n', 4).pack('N', 1).pack('N', 26)
            .pack('N', 0);

        $tiff = 'MM'.pack('n', 42).pack('N', 8)
            .$exifIfd
            .$gpsIfd
            .$rational($latitudeParts[0]).$rational($latitudeParts[1]).$rational($latitudeParts[2])
            .$rational($longitudeParts[0]).$rational($longitudeParts[1]).$rational($longitudeParts[2]);

        $app1 = "Exif\0\0".$tiff;
        $segment = "\xFF\xE1".pack('n', strlen($app1) + 2).$app1;

        // After the JFIF segment, which is where a camera puts it.
        $app0Length = unpack('n', substr($jpeg, 4, 2))[1];
        $insertAt = 2 + 2 + $app0Length;

        return substr($jpeg, 0, $insertAt).$segment.substr($jpeg, $insertAt);
    }
}
