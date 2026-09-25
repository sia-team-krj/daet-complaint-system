<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
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
}
