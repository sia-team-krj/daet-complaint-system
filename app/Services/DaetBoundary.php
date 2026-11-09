<?php

namespace App\Services;

/**
 * Daet's municipal boundary, used to keep a complaint pin inside the city.
 *
 * The polygon ships as GeoJSON in resources/data/daet-boundary.geojson. It is
 * the geoBoundaries PHL ADM3 feature for Daet, built from NAMRIA / PSA / OCHA
 * data and licensed CC BY 3.0 IGO. It is simplified (42 vertices), so it is a
 * jurisdiction check, not a survey instrument.
 *
 * Why this exists: `between:-90,90` accepted a pin on the other side of the
 * planet. A complaint outside Daet cannot be labelled with a barangay from the
 * app's own table, and it has no place on the city's service map. The boundary
 * is checked with a tolerance buffer because the simplified line is not exact
 * and a resident on the municipal edge should not be turned away over a few
 * metres of map data.
 *
 * If the data file is ever missing or unreadable, the check fails open and logs
 * a warning: refusing every complaint because a bundled file is absent would be
 * a worse failure than accepting a pin that staff can review.
 */
class DaetBoundary
{
    /** @var array<string, mixed>|null */
    private ?array $geojson = null;

    /** @var array<int, array<int, array<int, array{0: float|int, 1: float|int}>>> */
    private array $polygons = [];

    /**
     * The raw FeatureCollection, for pages that need to draw or inspect it.
     *
     * @return array<string, mixed>
     */
    public function geojson(): array
    {
        return $this->geojson ??= $this->load();
    }

    /**
     * How far outside the simplified boundary a pin may still fall.
     */
    public function toleranceMetres(): float
    {
        return (float) config('daet-barangays.boundary_tolerance_metres', 250);
    }

    /**
     * The boundary's bounding box as [[south, west], [north, east]], or null.
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}|null
     */
    public function bounds(): ?array
    {
        $south = $west = INF;
        $north = $east = -INF;

        foreach ($this->polygons() as $rings) {
            foreach ($rings as $ring) {
                foreach ($ring as $point) {
                    $longitude = (float) ($point[0] ?? NAN);
                    $latitude = (float) ($point[1] ?? NAN);

                    if (! is_finite($latitude) || ! is_finite($longitude)) {
                        continue;
                    }

                    $south = min($south, $latitude);
                    $north = max($north, $latitude);
                    $west = min($west, $longitude);
                    $east = max($east, $longitude);
                }
            }
        }

        if (! is_finite($south) || ! is_finite($west)) {
            return null;
        }

        return [[$south, $west], [$north, $east]];
    }

    /**
     * True when the coordinate falls inside the municipal polygon.
     *
     * Points in a hole (a polygon ring after the first) are outside, matching
     * the GeoJSON winding convention.
     */
    public function contains(float $latitude, float $longitude): bool
    {
        foreach ($this->polygons() as $rings) {
            $outer = $rings[0] ?? [];

            if (! $this->pointInRing($latitude, $longitude, $outer)) {
                continue;
            }

            $inHole = false;

            foreach (array_slice($rings, 1) as $hole) {
                if ($this->pointInRing($latitude, $longitude, $hole)) {
                    $inHole = true;
                    break;
                }
            }

            if (! $inHole) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the coordinate is inside the city or within the edge tolerance.
     */
    public function isWithin(float $latitude, float $longitude, ?float $toleranceMetres = null): bool
    {
        if ($this->polygons() === []) {
            return true;
        }

        if ($this->contains($latitude, $longitude)) {
            return true;
        }

        return $this->distanceToBoundaryMetres($latitude, $longitude)
            <= ($toleranceMetres ?? $this->toleranceMetres());
    }

    /**
     * The shortest distance, in metres, from a coordinate to any boundary ring.
     */
    public function distanceToBoundaryMetres(float $latitude, float $longitude): float
    {
        $minimum = INF;

        foreach ($this->polygons() as $rings) {
            foreach ($rings as $ring) {
                $minimum = min(
                    $minimum,
                    $this->distanceToRingMetres($latitude, $longitude, $ring),
                );
            }
        }

        return $minimum;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        $path = (string) config('daet-barangays.boundary_path', resource_path('data/daet-boundary.geojson'));

        if (! is_file($path) || ! is_readable($path)) {
            logger()->warning('Daet boundary file is missing; pin jurisdiction enforcement is disabled.', [
                'path' => $path,
            ]);

            return ['type' => 'FeatureCollection', 'features' => []];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            logger()->warning('Daet boundary file is not valid JSON; pin jurisdiction enforcement is disabled.', [
                'path' => $path,
            ]);

            return ['type' => 'FeatureCollection', 'features' => []];
        }

        return $decoded;
    }

    /**
     * @return array<int, array<int, array<int, array{0: float|int, 1: float|int}>>>
     */
    private function polygons(): array
    {
        if ($this->polygons !== []) {
            return $this->polygons;
        }

        $geojson = $this->geojson();
        $features = ($geojson['type'] ?? null) === 'FeatureCollection'
            ? ($geojson['features'] ?? [])
            : [($geojson['type'] ?? null) === 'Feature' ? $geojson : ['geometry' => $geojson]];

        foreach ($features as $feature) {
            $geometry = $feature['geometry'] ?? null;
            $type = $geometry['type'] ?? null;
            $coordinates = $geometry['coordinates'] ?? null;

            if (! is_array($coordinates)) {
                continue;
            }

            if ($type === 'Polygon') {
                $this->polygons[] = $coordinates;
            } elseif ($type === 'MultiPolygon') {
                foreach ($coordinates as $polygon) {
                    if (is_array($polygon)) {
                        $this->polygons[] = $polygon;
                    }
                }
            }
        }

        return $this->polygons;
    }

    /**
     * Ray-casting point-in-ring test. Ring points are GeoJSON [longitude, latitude].
     */
    private function pointInRing(float $latitude, float $longitude, array $ring): bool
    {
        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        $inside = false;

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) ($ring[$i][0] ?? NAN);
            $yi = (float) ($ring[$i][1] ?? NAN);
            $xj = (float) ($ring[$j][0] ?? NAN);
            $yj = (float) ($ring[$j][1] ?? NAN);

            if (! is_finite($xi) || ! is_finite($yi) || ! is_finite($xj) || ! is_finite($yj)) {
                continue;
            }

            if ((($yi > $latitude) !== ($yj > $latitude))
                && ($longitude < (($xj - $xi) * ($latitude - $yi) / ($yj - $yi)) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Distance from a coordinate to a ring, using a local equirectangular
     * projection. Daet is small enough that the curvature error is irrelevant
     * for a 250 m tolerance.
     */
    private function distanceToRingMetres(float $latitude, float $longitude, array $ring): float
    {
        $count = count($ring);

        if ($count < 2) {
            return INF;
        }

        $earthRadius = 6_378_100.0;
        $cosLatitude = cos(deg2rad($latitude));
        $minimum = INF;

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $ax = deg2rad((float) ($ring[$j][0] ?? NAN) - $longitude) * $earthRadius * $cosLatitude;
            $ay = deg2rad((float) ($ring[$j][1] ?? NAN) - $latitude) * $earthRadius;
            $bx = deg2rad((float) ($ring[$i][0] ?? NAN) - $longitude) * $earthRadius * $cosLatitude;
            $by = deg2rad((float) ($ring[$i][1] ?? NAN) - $latitude) * $earthRadius;

            if (! is_finite($ax) || ! is_finite($ay) || ! is_finite($bx) || ! is_finite($by)) {
                continue;
            }

            $dx = $bx - $ax;
            $dy = $by - $ay;
            $lengthSquared = ($dx * $dx) + ($dy * $dy);

            $t = $lengthSquared > 0
                ? max(0.0, min(1.0, -(($ax * $dx) + ($ay * $dy)) / $lengthSquared))
                : 0.0;

            $x = $ax + ($t * $dx);
            $y = $ay + ($t * $dy);
            $minimum = min($minimum, sqrt(($x * $x) + ($y * $y)));
        }

        return $minimum;
    }
}
