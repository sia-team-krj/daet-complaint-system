<?php

/*
 * Reference points for the 25 barangays of Daet, Camarines Norte.
 *
 * Their only job is to turn a coordinate into something a resident recognises —
 * "Barangay VI (Centro)" rather than "14.12121, 122.95818". Exact coordinates
 * are never shown to a resident and never leave this server.
 *
 * The keys are deliberately identical to User::BARANGAYS, so the name shown
 * here is the same name they pick during registration. Two spellings for the
 * same place would defeat the whole point of asking them.
 *
 * WHY A BUNDLED TABLE AND NOT REVERSE GEOCODING
 * A reverse geocoder would name the street, but it would mean posting a
 * resident's precise position to a third party on every conflict. This portal
 * exists to protect the identity of people reporting problems to a government,
 * and it is currently reachable over a public Tailscale Funnel, so the
 * coordinates stay here. The trade is accepted knowingly: a barangay is less
 * precise than a street, and precision is not the point.
 *
 * ACCURACY
 * These are representative points, one per barangay, resolved from OpenStreetMap
 * and rounded to 5 decimal places (about a metre). Nearest-point lookup is
 * therefore approximate: two barangays sharing a border can be swapped for a
 * point close to it. Good enough to tell two candidate locations apart, which
 * is all this is used for. Not good enough for anything that needs certainty
 * about which barangay a pin is in.
 */
return [

    'points' => [
        'Alawihao' => [14.10970, 122.93031],
        'Awitan' => [14.13889, 122.96250],
        'Bagasbas' => [14.13720, 122.98160],
        'Barangay I (Hilahod)' => [14.11261, 122.95705],
        'Barangay II (Pasig)' => [14.10615, 122.95629],
        'Barangay III (Iraya)' => [14.11323, 122.95265],
        'Barangay IV (Mantagbac)' => [14.11978, 122.95003],
        'Barangay V (Pandan)' => [14.12377, 122.94896],
        'Barangay VI (Centro)' => [14.12121, 122.95818],
        'Barangay VII (Diego Liñan)' => [14.11860, 122.96191],
        'Barangay VIII (Salcedo)' => [14.11466, 122.95949],
        'Bibirao' => [14.09333, 122.92213],
        'Borabod' => [14.12743, 122.95814],
        'Calasgasan' => [14.08588, 122.93686],
        'Camambugan' => [14.10720, 122.94877],
        'Cobangbang' => [14.10507, 122.96055],
        'Dogongan' => [14.10300, 122.90602],
        'Gahonon' => [14.12373, 122.94668],
        'Gubat (Moreno, Gubat, Mandulongan)' => [14.11492, 122.96654],
        'Lag-on' => [14.11766, 122.94634],
        'Magang' => [14.10077, 122.95447],
        'Mambalite' => [14.10256, 122.96914],
        'Mancruz' => [14.09633, 122.95523],
        'Pamorangon' => [14.09967, 122.95856],
        'San Isidro' => [14.10911, 122.96106],
    ],

    /*
     * How far a point may be from the whole table and still be called "in Daet".
     * Generous on purpose: these are reference points, not a boundary, so a
     * genuine complaint near the edge should still be given a barangay name
     * rather than an error.
     */
    'max_metres_from_table' => 3500,
];
