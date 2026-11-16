<?php

/**
 * Privacy policy by complaint category (server-authoritative).
 *
 * Some complaints name somebody. A report about how a staff member treated a
 * resident names a person; a report about a business using plastic names a
 * business. For those, two things are true that are not true of the other
 * categories:
 *
 *  1. The name of the party is captured in a private field that is not
 *     rendered on the transparency page and is not part of the public search
 *     text. The handling office and the Mayor's office see it so the public
 *     copy can be checked; nobody else does.
 *
 *  2. The complaint is filed private and cannot reach the public register
 *     until every one of these is true:
 *       - the office has settled it (resolved or closed),
 *       - the information has been verified as true,
 *       - the Mayor's office has redacted the public copy,
 *       - the Mayor's office has approved the publication.
 *
 * The handoff is automatic once the office resolves a verified report. The
 * handling office never writes the public copy and never asks by hand.
 *
 * A category that is genuinely a privacy concern is never published, and the
 * resident is told so.
 *
 * The mirror image of config/complaint_intake_policy.php (what the RESIDENT
 * must supply) and config/complaint_work_policy.php (what the OFFICE must
 * supply): this file says what happens to the complaint afterwards.
 *
 * Keys must stay aligned with ComplaintController::VALID_CATEGORIES /
 * DepartmentRouter categories.
 */
return [
    /**
     * The office whose staff sign off on publication. The Mayor's office.
     */
    'approval_office_code' => 'OM',

    'categories' => [
        // How you were treated at the LGU. Names a member of personnel.
        'staff_treatment' => [
            'named_party' => 'person',
            'named_party_position' => true,
        ],

        // The plastic policy. Names a business.
        'plastic_usage' => [
            'named_party' => 'business',
            'named_party_position' => false,
        ],
    ],

    /**
     * The default for every category that is not listed above.
     */
    'default' => [
        'named_party' => null,
        'named_party_position' => false,
    ],
];
