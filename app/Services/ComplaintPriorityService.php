<?php

namespace App\Services;

use App\Enums\ComplaintPriority;
use App\Models\Complaint;
use Illuminate\Support\Str;

class ComplaintPriorityService
{
    /**
     * Category baselines are advisory only. A reviewer always confirms the
     * final priority after checking the report's legitimacy and context.
     *
     * @var array<string, string>
     */
    private const CATEGORY_BASELINES = [
        'road_damage' => ComplaintPriority::Elevated->value,
        'flooding' => ComplaintPriority::Elevated->value,
        'streetlight' => ComplaintPriority::Routine->value,
        'garbage' => ComplaintPriority::Routine->value,
        'sanitation' => ComplaintPriority::Elevated->value,
        'park_maintenance' => ComplaintPriority::Routine->value,
        'business_permit' => ComplaintPriority::Routine->value,
        'noise_complaint' => ComplaintPriority::Routine->value,
        'stray_animals' => ComplaintPriority::Routine->value,
        'others' => ComplaintPriority::Routine->value,
    ];

    /** @var array<int, string> */
    private const CRITICAL_TERMS = [
        'immediate danger',
        'gas leak',
        'building collapse',
        'electrocution',
        'unconscious',
        'not responding',
        'trapped',
        'fire hazard',
        'floodwater inside',
        'contaminated water',
        'sewage exposure',
    ];

    /** @var array<int, string> */
    private const URGENT_TERMS = [
        'dangerous',
        'injury',
        'injured',
        'accident',
        'blocked road',
        'blocked access',
        'no access',
        'open drain',
        'deep hole',
        'unsafe',
        'flooded',
        'stray animal',
        'child',
        'elderly',
        'school',
        'hospital',
        'health center',
    ];

    /** @var array<int, string> */
    private const ELEVATED_TERMS = [
        'several residents',
        'many residents',
        'repeated',
        'every day',
        'daily',
        'for weeks',
        'no collection',
        'overflowing',
        'not working',
        'broken',
        'dark at night',
    ];

    /**
     * @return array{priority: string, reasons: array<int, string>}
     */
    public function suggest(Complaint $complaint): array
    {
        $text = Str::lower(implode(' ', [
            (string) $complaint->title,
            (string) $complaint->description,
            (string) $complaint->address_text,
        ]));

        $priority = ComplaintPriority::from(
            self::CATEGORY_BASELINES[$complaint->category] ?? ComplaintPriority::Routine->value,
        );
        $reasons = [
            'Category baseline: '.$priority->label(),
        ];

        if ($this->containsAny($text, self::CRITICAL_TERMS)) {
            $priority = ComplaintPriority::Critical;
            $reasons[] = 'Description contains an immediate safety or access signal.';
        } elseif ($this->containsAny($text, self::URGENT_TERMS)) {
            $priority = $this->highest($priority, ComplaintPriority::Urgent);
            $reasons[] = 'Description contains a possible safety, access, or essential-service impact.';
        } elseif ($this->containsAny($text, self::ELEVATED_TERMS)) {
            $priority = $this->highest($priority, ComplaintPriority::Elevated);
            $reasons[] = 'Description suggests repeated or significant service impact.';
        }

        return [
            'priority' => $priority->value,
            'reasons' => $reasons,
        ];
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function containsAny(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            if (Str::contains($text, $term)) {
                return true;
            }
        }

        return false;
    }

    private function highest(ComplaintPriority $current, ComplaintPriority $candidate): ComplaintPriority
    {
        return $candidate->rank() > $current->rank() ? $candidate : $current;
    }
}
