<?php

namespace App\Filament\Resources\Warans\Widgets;

use App\Models\Waran;
use App\Models\WaranJawatan;
use DB;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WaranStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
 $allWarans = Waran::with(['waranJawatan'])->get();

        $totalWaran    = $allWarans->count();
        $totalLebih    = $allWarans->filter(fn($w) => $w->status_jik === 'Lebih')->count();
        $totalKurang   = $allWarans->filter(fn($w) => $w->status_jik === 'Kurang')->count();
        $totalSeimbang = $allWarans->filter(fn($w) => $w->status_jik === 'Seimbang')->count();

        return [
            Stat::make('Jumlah Waran', Waran::count())
                ->chart([10, 12, 9, 14, Waran::count(), Waran::count() + 2, Waran::count() + 1]),

            Stat::make('Waran Seimbang', $totalSeimbang)
                ->description(
                    $totalWaran
                    ? round(($totalSeimbang / $totalWaran) * 100, 1) . '% dari keseluruhan'
                    : '0%'
                )
                // ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart([10, 12, 9, 14, $totalSeimbang, $totalSeimbang + 2, $totalSeimbang + 1])
                ->color('success'),

            Stat::make('Waran Tidak Seimbang', $totalKurang)
                ->description(
                    $totalWaran
                    ? round(($totalKurang / $totalWaran) * 100, 1) . '% dari keseluruhan'
                    : '0%'
                )
                // ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([5, 8, 10, 12, $totalKurang, $totalKurang + 1, $totalKurang + 3])
                ->color('danger'),

        ];

    }
}
