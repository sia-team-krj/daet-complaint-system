<?php

/*
 * Two-level category groups for the "File a complaint" picker.
 *
 * Each group is an ordered entry with:
 *   key        — stable group key (also used for lang lookups)
 *   icon       — lucide-style tile icon name for the group tile
 *   color      — token suffix; Blade maps it to .tile__icon--<color>
 *                (roads→--cat-road-*, waste→--cat-waste-*, spaces→--cat-spaces-*,
 *                 neighborhood→--cat-noise-*, business→--cat-business-*,
 *                 other→--cat-other-*)
 *   categories — category VALUES (keys in $categoryOptions), in display order.
 *
 * Live labels (from DepartmentRouter::getCategoryOptions()) resolved to values:
 *   roads       = Road Damage (road_damage), Streetlight Issues (streetlight),
 *                 Flooding / Drainage (flooding)
 *   waste       = Garbage Collection (garbage), Sanitation (sanitation)
 *   spaces      = Park / Public Area Maintenance (park_maintenance)
 *   neighborhood= Noise Complaint (noise_complaint), Stray Animals (stray_animals)
 *   business    = Business Permit Issues (business_permit)
 *   other       = Other Concerns (others)
 *
 * Values present in $categoryOptions but missing here are appended to "other"
 * by ComplaintController; values here that do not exist in $categoryOptions
 * are ignored. Nothing may disappear.
 */

return [
    [
        'key' => 'roads',
        'icon' => 'construction',
        'color' => 'roads',
        'categories' => ['road_damage', 'streetlight', 'flooding'],
    ],
    [
        'key' => 'waste',
        'icon' => 'trash-2',
        'color' => 'waste',
        'categories' => ['garbage', 'sanitation'],
    ],
    [
        'key' => 'spaces',
        'icon' => 'trees',
        'color' => 'spaces',
        'categories' => ['park_maintenance'],
    ],
    [
        'key' => 'neighborhood',
        'icon' => 'volume-2',
        'color' => 'neighborhood',
        'categories' => ['noise_complaint', 'stray_animals'],
    ],
    [
        'key' => 'business',
        'icon' => 'briefcase',
        'color' => 'business',
        'categories' => ['business_permit'],
    ],
    [
        'key' => 'other',
        'icon' => 'circle-help',
        'color' => 'other',
        'categories' => ['others'],
    ],
];
