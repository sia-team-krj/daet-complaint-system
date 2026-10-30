<?php

namespace App\Services;

/**
 * Names a Daet barangay from a coordinate.
 *
 * This exists because "14.12121, 122.95818" means nothing to a resident standing
 * on a street, while "Barangay VI" is something they can check against their own
 * knowledge and be confident we understood them.
 *
 * Deliberately local. A reverse geocoder would name the street, but it would
 * also post the resident's precise position to a third party, and this is a
 * platform whose stated purpose includes protecting the identity of people
 * reporting problems to their government. The coordinates never leave here.
 */
class BarangayLocator
{
    /**
     * The barangay a coordinate falls in, or null if it is nowhere near Daet.
     */
    public function locate(mixed $latitude, mixed $longitude): ?string
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $points = $this->points();

        if ($points === []) {
            return null;
        }

        $best = null;
        $bestMetres = INF;

        foreach ($points as $barangay => [$lat, $lng]) {
            $metres = $this->metresBetween((float) $latitude, (float) $longitude, $lat, $lng);

            if ($metres < $bestMetres) {
                $bestMetres = $metres;
                $best = $barangay;
            }
        }

        $limit = (float) config('daet-barangays.max_metres_from_table', 3500);

        return $best !== null && $bestMetres <= $limit ? $best : null;
    }

    /**
     * A short human label, or null when the point cannot be placed.
     *
     * Returns the barangay name verbatim rather than prefixing "Barangay",
     * because most names already are "Barangay VI (Centro)" and the prefix
     * produced "Barangay Barangay VI (Centro)". Never includes the coordinate:
     * callers show this to residents and to the public, and a coordinate in
     * either place is what this whole class exists to avoid.
     */
    public function describe(mixed $latitude, mixed $longitude): ?string
    {
        return $this->locate($latitude, $longitude);
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

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public function points(): array
    {
        $configured = config('daet-barangays.points', []);

        return is_array($configured) ? $configured : [];
    }

    /**
     * How many of the app's own barangay list the table actually covers.
     *
     * A silent gap here would mean a resident in that barangay is told a
     * neighbouring one, so the count is asserted rather than assumed.
     */
    public function coverageAgainst(array $officialNames): array
    {
        $known = array_keys($this->points());

        $missing = array_values(array_diff($officialNames, $known));
        $extra = array_values(array_diff($known, $officialNames));

        return [
            'covered' => count($known),
            'expected' => count($officialNames),
            'missing' => $missing,
            'unknown' => $extra,
        ];
    }
}
