<?php

namespace App\Support;

/**
 * The one generator for emailed temporary passwords.
 *
 * Staff accounts are never created with a password chosen by the person
 * creating them. Both the global administrator and a Department
 * Administrator use this so the rules cannot drift apart.
 */
final class TemporaryPassword
{
    /**
     * Strong temporary password: guaranteed upper, lower and digit,
     * length clamped to the requested window (12–16 by default).
     */
    public static function generate(int $length = 14): string
    {
        $length = max(12, min(16, $length));
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $digits = '23456789';
        $all = $upper.$lower.$digits;

        $pick = fn (string $chars) => $chars[random_int(0, strlen($chars) - 1)];

        $password = $pick($upper).$pick($lower).$pick($digits);
        for ($i = 3; $i < $length; $i++) {
            $password .= $pick($all);
        }

        return substr(str_shuffle($password), 0, $length);
    }
}
