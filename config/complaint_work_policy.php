<?php

/**
 * Work policy by complaint category (server-authoritative, extensible).
 *
 * The mirror image of config/complaint_intake_policy.php: that file says what
 * the RESIDENT must supply when filing; this one says what the OFFICE must
 * supply when acting on it.
 *
 * Modes:
 * - labor_required: physical remediation. A photograph of the work is the
 *   proof, so one is required to move the complaint to In Progress or
 *   Resolved. A written message is never a substitute.
 * - report_only: enforcement, regulatory or desk work. There is no site work
 *   to photograph, so a written record of the action is required and a photo
 *   is optional.
 * - referral: handed to another body. A written referral is required — who
 *   took it and the reference number — and site photos are optional.
 *
 * The mode is resolved from this map when a complaint is first acted on and
 * then frozen onto the complaint, so editing this file never changes the
 * rules on a ticket that is already in flight. An office may override the
 * mode on a single complaint; the override is recorded in the audit trail.
 *
 * Keys must stay aligned with ComplaintController::VALID_CATEGORIES /
 * DepartmentRouter categories.
 */
return [
    'categories' => [
        'road_damage' => 'labor_required',
        'flooding' => 'labor_required',
        'streetlight' => 'labor_required',
        'garbage' => 'labor_required',
        'sanitation' => 'labor_required',
        'park_maintenance' => 'labor_required',
        'stray_animals' => 'labor_required',

        'noise_complaint' => 'report_only',
        'business_permit' => 'report_only',
        'others' => 'report_only',
    ],

    // A complaint in a category this file does not know about gets no
    // photograph demand: an unknown report should never be blocked by a rule
    // nobody wrote for it.
    'default' => 'report_only',
];
