<?php

namespace App\Services;

use App\Models\Department;

class DepartmentRouter
{
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
     * Falls back to GSO if no match found.
     */
    public static function resolve(string $category): ?Department
    {
        $code = self::CATEGORY_MAP[$category] ?? 'GSO';
        
        return Department::where('code', $code)->first();
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
