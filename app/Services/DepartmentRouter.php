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
     */
    public const ROUTABLE_CODES = ['ENG', 'GSO', 'BPLO', 'OM', 'HLTH'];

    /**
     * Category to department code mapping.
     */
    private const CATEGORY_MAP = [
        // Engineering Office
        'road_damage'       => 'ENG',
        'flooding'          => 'ENG',
        'streetlight'       => 'ENG',

        // General Services Office
        'garbage'           => 'GSO',
        'sanitation'        => 'GSO',
        'park_maintenance'  => 'GSO',
        'others'            => 'GSO', // fallback

        // Business Permit and Licensing Office
        'business_permit'   => 'BPLO',

        // Office of the Mayor (peace & order concerns)
        'noise_complaint'   => 'OM',

        // Health Office (animal welfare)
        'stray_animals'     => 'HLTH',
    ];

    /**
     * Resolve category to Department model.
     *
     * Archived offices are skipped. Archiving is the admin's stated signal that
     * an office should stop receiving new work, so resolving to one would make
     * the whole archive feature decorative — complaints would keep arriving at a
     * department that no longer appears in any assignment list.
     *
     * Falls back to GSO if no match found, then to any active office, so a
     * misconfigured map can never silently drop a complaint into the unassigned
     * pile with no department at all.
     */
    public static function resolve(string $category): ?Department
    {
        $code = self::CATEGORY_MAP[$category] ?? 'GSO';

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
        
        return $dept?->name ?? 'General Services Office';
    }

    /**
     * Get all category options grouped by department.
     * For use in forms.
     */
    public static function getCategoryOptions(): array
    {
        return [
            'Engineering' => [
                ['value' => 'road_damage', 'label' => 'Road Damage'],
                ['value' => 'flooding', 'label' => 'Flooding / Drainage'],
                ['value' => 'streetlight', 'label' => 'Streetlight Issues'],
            ],
            'General Services' => [
                ['value' => 'garbage', 'label' => 'Garbage Collection'],
                ['value' => 'sanitation', 'label' => 'Sanitation'],
                ['value' => 'park_maintenance', 'label' => 'Park / Public Area Maintenance'],
                ['value' => 'others', 'label' => 'Other Concerns'],
            ],
            'Business Permits' => [
                ['value' => 'business_permit', 'label' => 'Business Permit Issues'],
            ],
            'Peace & Order' => [
                ['value' => 'noise_complaint', 'label' => 'Noise Complaint'],
            ],
            'Agriculture' => [
                ['value' => 'stray_animals', 'label' => 'Stray Animals'],
            ],
        ];
    }
}
