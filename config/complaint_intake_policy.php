<?php

/**
 * Intake policy by complaint category (server-authoritative, extensible).
 *
 * Modes:
 * - physical_required: photos (1-5) + location required at filing (current behavior).
 * - proof_optional: location context required (barangay + landmark); photos optional (0-5) but encouraged.
 * - sensitive_intake: privacy-sensitive; no photo/location required at intake (steps hidden).
 * - follow_up_optional: ambiguous/other; no photo/location required at intake, staff may request via follow-up.
 *
 * Keys must stay aligned with ComplaintController::VALID_CATEGORIES / DepartmentRouter categories.
 */
return [
    'modes' => [
        'physical_required' => [
            'photos_required' => true,
            'photos_allowed' => true,
            'location_required' => true,
            'barangay_required' => true,
            'address_text_required' => false,
            'show_photos_step' => true,
            'show_location_steps' => true,
        ],
        'proof_optional' => [
            'photos_required' => false,
            'photos_allowed' => true,
            'location_required' => true,
            'barangay_required' => true,
            'address_text_required' => true, // 1a: barangay + landmark required
            'show_photos_step' => true,
            'show_location_steps' => true,
        ],
        'sensitive_intake' => [
            'photos_required' => false,
            'photos_allowed' => false, // intake step hidden; server rejects stray uploads for this mode
            'location_required' => false,
            'barangay_required' => false,
            'address_text_required' => false,
            'show_photos_step' => false,
            'show_location_steps' => false,
        ],
        'follow_up_optional' => [
            'photos_required' => false,
            'photos_allowed' => false, // intake step hidden (Option A)
            'location_required' => false,
            'barangay_required' => false,
            'address_text_required' => false,
            'show_photos_step' => false,
            'show_location_steps' => false,
        ],
    ],

    'categories' => [
        'road_damage' => 'physical_required',
        'flooding' => 'physical_required',
        'streetlight' => 'physical_required',
        'garbage' => 'physical_required',
        'sanitation' => 'physical_required',
        'park_maintenance' => 'physical_required',
        'noise_complaint' => 'proof_optional',
        'stray_animals' => 'proof_optional',
        'business_permit' => 'sensitive_intake',
        'others' => 'follow_up_optional',
    ],

    'default' => 'physical_required',
];
