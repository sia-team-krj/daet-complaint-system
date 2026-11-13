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
 *   waste       = Garbage Collection (garbage), Sanitation (sanitation),
 *                 Plastic Product Usage (plastic_usage)
 *   other       = Other Concerns (others)
 *
 * Business & Permits and Staff & Personnel are withdrawn for now: the
 * municipalities closed those offices, so the categories go with them. They
 * stay in the system so anything already filed under them still reads
 * properly, and the Mayor's office receives them in the meantime.
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
        'categories' => ['garbage', 'sanitation', 'plastic_usage'],
    ],
    [
        'key' => 'other',
        'icon' => 'circle-help',
        'color' => 'other',
        'categories' => ['others'],
    ],
];
