<?php

namespace App\Rules;

use App\Services\DaetBoundary;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a map pin that is outside Daet's municipal boundary.
 *
 * The longitude is supplied by the form's sibling input, because a point is
 * only meaningful as a pair. The rule is attached to `latitude`; the error
 * therefore lands on the pin step in the filing wizard, where the map lives.
 */
class WithinDaet implements ValidationRule
{
    public function __construct(
        private readonly mixed $longitude,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! is_numeric($this->longitude)) {
            return;
        }

        $boundary = app(DaetBoundary::class);

        if (! $boundary->isWithin((float) $value, (float) $this->longitude)) {
            $fail(__('complaint_create.errors.outside_daet'));
        }
    }
}
