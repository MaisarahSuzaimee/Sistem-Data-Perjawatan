<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\Waran;
use Illuminate\Support\Facades\Cache;

class NavigationBadgeCache
{
    private const TTL_SECONDS = 60;

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
            "nav.badge.waran.{$userId}",
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
            "nav.badge.pegawai.{$userId}",
            self::TTL_SECONDS,
            fn (): int => (int) Pegawai::query()->tidakLengkap()->count(),
        );
    }

    public static function forget(?int $userId = null): void
    {
        $userId ??= auth()->id() ?? 0;

        Cache::forget("nav.badge.waran.{$userId}");
        Cache::forget("nav.badge.pegawai.{$userId}");
        unset(self::$memo["waran.{$userId}"], self::$memo["pegawai.{$userId}"]);
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
