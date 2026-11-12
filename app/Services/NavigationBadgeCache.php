<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\Waran;
use Illuminate\Support\Facades\Cache;

class NavigationBadgeCache
{
    /** Keep badges warm longer — counts change only when pegawai/waran data changes. */
    private const TTL_SECONDS = 600;

    private const VERSION_KEY = 'nav.badge.version';

    /** @var array<string, int> */
    private static array $memo = [];

    public static function waranUnbalancedCount(): int
    {
        $userId = auth()->id() ?? 0;
        $memoKey = "waran.{$userId}";

        if (array_key_exists($memoKey, self::$memo)) {
            return self::$memo[$memoKey];
        }

        return self::$memo[$memoKey] = (int) Cache::remember(
            self::cacheKey('waran', $userId),
            self::TTL_SECONDS,
            fn (): int => self::computeWaranUnbalancedCount(auth()->user()),
        );
    }

    public static function pegawaiTidakLengkapCount(): int
    {
        $userId = auth()->id() ?? 0;
        $memoKey = "pegawai.{$userId}";

        if (array_key_exists($memoKey, self::$memo)) {
            return self::$memo[$memoKey];
        }

        return self::$memo[$memoKey] = (int) Cache::remember(
            self::cacheKey('pegawai', $userId),
            self::TTL_SECONDS,
            fn (): int => (int) Pegawai::query()->tidakLengkap()->count(),
        );
    }

    /**
     * Invalidate every user's badge caches. Prefer this over per-user forget —
     * navigation badges are cheap to recompute once, and pegawai/waran writes
     * affect many users' scoped counts.
     */
    public static function flush(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        Cache::forever(self::VERSION_KEY, $version + 1);
        self::$memo = [];
    }

    /**
     * @deprecated Use flush() — kept for existing call sites.
     */
    public static function forget(?int $userId = null): void
    {
        self::flush();
    }

    protected static function cacheKey(string $type, int $userId): string
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);

        return "nav.badge.{$type}.{$userId}.v{$version}";
    }

    protected static function computeWaranUnbalancedCount(?User $user): int
    {
        $isiSql = self::isiCountSql($user);
        $jikSql = self::jikCountSql($user);

        return (int) Waran::query()
            ->whereRaw("({$jikSql}) - ({$isiSql}) != 0")
            ->count();
    }

    protected static function jikCountSql(?User $user): string
    {
        if ($user?->isUser() && filled($user->ptj_id)) {
            $ptjId = (int) $user->ptj_id;

            return "(SELECT COUNT(*) FROM waran_jawatans wj
                WHERE wj.deleted_at IS NULL
                  AND wj.ptj_id = {$ptjId}
                  AND (
                    (warans.jenis = 'Tolak' AND wj.waran_tolak_id = warans.id)
                    OR (COALESCE(warans.jenis, '') != 'Tolak' AND wj.waran_id = warans.id)
                  ))";
        }

        return 'COALESCE(warans.jik, 0)';
    }

    protected static function isiCountSql(?User $user): string
    {
        $ptjFilter = '';

        if ($user?->isUser() && filled($user->ptj_id)) {
            $ptjFilter = ' AND wj.ptj_id = '.(int) $user->ptj_id;
        }

        return "CASE
            WHEN warans.jenis = 'Tolak' THEN (
                SELECT COUNT(*) FROM waran_jawatans wj
                WHERE wj.waran_tolak_id = warans.id
                  AND wj.status = 'removed'{$ptjFilter}
            )
            ELSE (
                SELECT COUNT(*) FROM waran_jawatans wj
                WHERE wj.waran_id = warans.id
                  AND wj.pegawai_id IS NOT NULL
                  AND wj.status = 'active'
                  AND wj.deleted_at IS NULL{$ptjFilter}
            )
        END";
    }
}
