<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'settings.all';

    /**
     * Get setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allCached()[$key] ?? null;

        return $value !== null ? $value : $default;
    }

    /**
     * Set setting value (upsert).
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrInsert(
            ['key' => $key],
            ['value' => $value !== null ? (string) $value : null, 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        try {
            /** @var array<string, string|null> */
            return Cache::remember(self::CACHE_KEY, 3600, function (): array {
                return static::query()->pluck('value', 'key')->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }
}
