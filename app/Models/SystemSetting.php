<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getInt(string $key, int $default): int
    {
        $value = static::getValue($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key);

        return $value === null ? $default : (bool) $value;
    }
}
