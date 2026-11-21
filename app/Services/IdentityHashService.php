<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * HMAC-SHA256 duplicate detection for government-issued IDs.
 *
 * The raw ID number never reaches the database: only the hash does. A
 * dedicated IDENTITY_HASH_KEY keeps duplicate detection stable when APP_KEY
 * rotates.
 */
class IdentityHashService
{
    /** @param list<string> $parts */
    public function hash(string $idType, string $idNumber, array $parts = []): string
    {
        $normalized = strtoupper($this->normalize($idNumber));
        $key = config('identity.hash_key') ?? config('app.key');

        if (! is_string($key) || $key === '') {
            throw new InvalidArgumentException('identity.hash_key is not configured.');
        }

        $context = trim($idType.'|'.implode('|', $parts));

        return hash_hmac('sha256', $context.'|'.$normalized, $key);
    }

    public function normalize(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value)) ?? '';
    }
}
