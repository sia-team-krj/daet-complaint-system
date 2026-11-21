<?php

namespace App\Support;

/**
 * The finite presentation vocabulary an admin may choose from.
 *
 * Keeping these lists in one place means the validation rule, the icon/colour
 * pickers and the public stylesheet can never drift apart. A value is only
 * selectable if `tile-icon.blade.php` can draw it and tokens.css has a tint
 * for it.
 */
final class ComplaintTaxonomyOptions
{
    /** @var list<string> */
    public const ICONS = [
        'construction',
        'waves',
        'lightbulb',
        'trash-2',
        'spray-can',
        'leaf',
        'trees',
        'volume-2',
        'paw-print',
        'briefcase',
        'file-badge',
        'users',
        'circle-help',
    ];

    /** @var list<string> */
    public const COLORS = [
        'roads',
        'flood',
        'streetlight',
        'waste',
        'sanitation',
        'spaces',
        'neighborhood',
        'noise',
        'business',
        'personnel',
        'stray',
        'other',
    ];

    /** @var list<string> */
    public const INTAKE_MODES = [
        'physical_required',
        'proof_optional',
        'sensitive_intake',
        'follow_up_optional',
    ];

    /**
     * @return array<string, string>
     */
    public static function icons(): array
    {
        $options = [];

        foreach (self::ICONS as $icon) {
            $options[$icon] = __('admin_categories.icons.'.$icon);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        $options = [];

        foreach (self::COLORS as $color) {
            $options[$color] = __('admin_categories.colors.'.$color);
        }

        return $options;
    }

    /**
     * @return array<string, array{label: string, helper: string}>
     */
    public static function intakeModes(): array
    {
        $options = [];

        foreach (self::INTAKE_MODES as $mode) {
            $options[$mode] = [
                'label' => __('admin_categories.intake_modes.'.$mode.'.label'),
                'helper' => __('admin_categories.intake_modes.'.$mode.'.helper'),
            ];
        }

        return $options;
    }
}
