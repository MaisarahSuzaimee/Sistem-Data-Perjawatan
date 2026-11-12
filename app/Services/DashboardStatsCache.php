<?php

namespace App\Services;

use App\Models\Hebahan;
use App\Models\User;
use App\Models\Waran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsCache
{
    private const TTL_SECONDS = 300;

    private const VERSION_KEY = 'dashboard.stats.version';

    /**
     * @return array{
     *     totalWaran: int,
     *     totalPengisianSemasa: int,
     *     totalKekosongan: int,
     *     recentWarans: \Illuminate\Database\Eloquent\Collection<int, Waran>,
     *     waranByProgram: Collection<int, object>,
     *     recentHebahans: \Illuminate\Database\Eloquent\Collection<int, Hebahan>
     * }
     */
    public static function get(): array
    {
        $userId = auth()->id() ?? 0;
        $version = (int) Cache::get(self::VERSION_KEY, 1);

        /** @var array{totalWaran: int, totalPengisianSemasa: int, totalKekosongan: int, waranByProgram: list<array{nama_program: string, desc_program: ?string, waran_count: int}>} $stats */
        $stats = Cache::remember(
            "dashboard.stats.{$userId}.v{$version}",
            self::TTL_SECONDS,
            fn (): array => self::computeAggregates(auth()->user()),
        );

        return [
            'totalWaran' => $stats['totalWaran'],
            'totalPengisianSemasa' => $stats['totalPengisianSemasa'],
            'totalKekosongan' => $stats['totalKekosongan'],
            'waranByProgram' => collect($stats['waranByProgram'])
                ->map(fn (array $row): object => (object) $row)
                ->sortBy(fn (object $item): int => (int) filter_var($item->nama_program, FILTER_SANITIZE_NUMBER_INT))
                ->values(),
            'recentWarans' => Waran::query()
                ->setEagerLoads([])
                ->latest()
                ->take(5)
                ->get(),
            'recentHebahans' => Hebahan::query()
                ->where('status', 'published')
                ->where(function ($q): void {
                    $q->whereNull('dipaparkan_sehingga')
                        ->orWhere('dipaparkan_sehingga', '>=', now()->toDateString());
                })
                ->latest('tarikh_hebahan')
                ->take(5)
                ->get(),
        ];
    }

    public static function forget(?int $userId = null): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        Cache::forever(self::VERSION_KEY, $version + 1);
    }

    /**
     * @return array{totalWaran: int, totalPengisianSemasa: int, totalKekosongan: int, waranByProgram: list<array{nama_program: string, desc_program: ?string, waran_count: int}>}
     */
    protected static function computeAggregates(?User $user): array
    {
        $isiSql = self::isiCountSql($user);
        $jikSql = self::jikCountSql($user);

        $totals = Waran::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total_waran')
            ->selectRaw("COALESCE(SUM({$jikSql}), 0) as total_jik")
            ->selectRaw("COALESCE(SUM({$isiSql}), 0) as total_isi")
            ->first();

        $totalWaran = (int) ($totals->total_waran ?? 0);
        $totalPengisianSemasa = (int) ($totals->total_isi ?? 0);
        $totalKekosongan = (int) ($totals->total_jik ?? 0) - $totalPengisianSemasa;

        $waranByProgramQuery = DB::table('programs')
            ->join('aktivitis', 'aktivitis.program_id', '=', 'programs.id')
            ->join('waran_jawatans', 'waran_jawatans.aktiviti_id', '=', 'aktivitis.id')
            ->whereNull('waran_jawatans.deleted_at')
            ->select([
                'programs.nama_program',
                'programs.desc_program',
                DB::raw('COUNT(waran_jawatans.id) as waran_count'),
            ])
            ->groupBy('programs.id', 'programs.nama_program', 'programs.desc_program')
            ->having('waran_count', '>', 0);

        if ($user?->isUser() && filled($user->ptj_id)) {
            $waranByProgramQuery->where('waran_jawatans.ptj_id', (int) $user->ptj_id);
        }

        $waranByProgram = $waranByProgramQuery
            ->get()
            ->map(fn ($row): array => [
                'nama_program' => (string) $row->nama_program,
                'desc_program' => $row->desc_program,
                'waran_count' => (int) $row->waran_count,
            ])
            ->all();

        return [
            'totalWaran' => $totalWaran,
            'totalPengisianSemasa' => $totalPengisianSemasa,
            'totalKekosongan' => $totalKekosongan,
            'waranByProgram' => $waranByProgram,
        ];
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
