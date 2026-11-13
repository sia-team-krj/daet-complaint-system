<?php

namespace App\Services;

use App\Models\Department;

class DepartmentRouter
{
    /**
     * Codes this router resolves, i.e. the codes that are routing keys rather
     * than free labels. An admin changing one of these silently strands every
     * category mapped to it, so AdminController refuses the rename instead of
     * accepting a change whose effect the operator cannot see.
     *
     * @var list<string>
     */
    public const ROUTABLE_CODES = ['ENG', 'MENRO', 'OM'];

    /**
     * The office that catches a category nobody owns yet, and the fallback
     * when the mapped office is missing or archived. The Mayor's Office is
     * the general desk: every escalated concern lands there.
     */
    public const FALLBACK_CODE = 'OM';

    /**
     * Category to department code mapping.
     *
     * @var array<string, string>
     */
    private const CATEGORY_MAP = [
        // Engineering Office
        'road_damage'       => 'ENG',
        'flooding'          => 'ENG',
        'streetlight'       => 'ENG',

        // Municipal Environment and Natural Resource Office
        'garbage'           => 'MENRO',
        'sanitation'        => 'MENRO',
        'plastic_usage'     => 'MENRO',

        // Mayor's Office (Municipal Information Office) — the general desk
        'others'            => 'OM',

        // Withdrawn for now: no longer offered to residents, but still
        // routable so anything already filed under them keeps an owner.
        'business_permit'   => 'OM',
        'staff_treatment'   => 'OM',
        'noise_complaint'   => 'OM',
        'park_maintenance'  => 'OM',
        'stray_animals'     => 'OM',
    ];

    /**
     * Categories that are no longer offered to residents.
     *
     * They stay routable and stay labelled, because complaints already filed
     * under them still exist and must still render with a proper name on the
     * public register.
     *
     * @var array<string, string>
     */
    private const RETIRED_CATEGORIES = [
        'business_permit' => 'Business Permit Issues',
        'staff_treatment' => 'How You Were Treated at the LGU',
        'noise_complaint' => 'Noise Complaint',
        'park_maintenance' => 'Park / Public Area Maintenance',
        'stray_animals' => 'Stray Animals',
    ];

    /**
     * Resolve category to Department model.
     *
     * Archived offices are skipped. Archiving is the admin's stated signal that
     * an office should stop receiving new work, so resolving to one would make
     * the whole archive feature decorative — complaints would keep arriving at a
     * department that no longer appears in any assignment list.
     *
     * Falls back to the Mayor's Office if no match is found, then to any active
     * office, so a misconfigured map can never silently drop a complaint into
     * the unassigned pile with no department at all.
     */
    public static function resolve(string $category): ?Department
    {
        $code = self::CATEGORY_MAP[$category] ?? self::FALLBACK_CODE;

        $department = Department::where('code', $code)->where('is_active', true)->first();

        if ($department !== null) {
            return $department;
        }

        return Department::where('is_active', true)->first();
    }

    /**
     * Get department name for a category (for UX display).
     */
    public static function getDepartmentName(string $category): string
    {
        $dept = self::resolve($category);

        return $dept?->name ?? "Mayor's Office";
    }

    /**
     * Get all category options grouped by the office that receives them.
     * For use in forms.
     *
     * The group key IS the office name — residents are told which office
     * handles their report ("Goes to {office}"), so the key is shown to them
     * verbatim and must match the name in the department directory.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function getCategoryOptions(): array
    {
        return [
            'Engineering Office' => [
                ['value' => 'road_damage', 'label' => 'Road Damage'],
                ['value' => 'flooding', 'label' => 'Flooding / Drainage'],
                ['value' => 'streetlight', 'label' => 'Streetlight Issues'],
            ],
            'Municipal Environment and Natural Resource Office' => [
                ['value' => 'garbage', 'label' => 'Garbage Collection'],
                ['value' => 'sanitation', 'label' => 'Sanitation'],
                ['value' => 'plastic_usage', 'label' => 'Plastic Product Usage'],
            ],
            "Mayor's Office" => [
                ['value' => 'others', 'label' => 'Other Concerns'],
            ],
        ];
    }

    /**
     * Every category the system still knows about, with its label.
     *
     * The picker's list plus the retired ones, so a complaint filed before a
     * category was withdrawn still shows its own name on the public register
     * instead of a guess.
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        $labels = [];

        foreach (self::getCategoryOptions() as $options) {
            foreach ($options as $option) {
                $labels[$option['value']] = $option['label'];
            }
        }

        return array_merge($labels, self::RETIRED_CATEGORIES);
    }
}
