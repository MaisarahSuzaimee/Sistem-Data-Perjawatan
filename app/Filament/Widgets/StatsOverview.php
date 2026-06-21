<?php

namespace App\Filament\Widgets;

use App\Models\Program;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $allWarans = Waran::with(['waranJawatan'])->get();

        $totalWaran = $allWarans->count();
        $totalLebih = $allWarans->filter(fn($w) => $w->status_jik === 'Lebih')->count();
        $totalKurang = $allWarans->filter(fn($w) => $w->status_jik === 'Kurang')->count();
        $totalSeimbang = $allWarans->filter(fn($w) => $w->status_jik === 'Seimbang')->count();

        $recentWarans = Waran::with(['waranJawatan'])->latest()->take(5)->get();

        $waranByProgram = collect();
        $programs = Program::with('aktiviti')->get();
        foreach ($programs as $program) {
            $count = WaranJawatan::whereIn('aktiviti_id', $program->aktiviti->pluck('id'))->count();
            if ($count > 0) {
                $waranByProgram->push((object) [
                    'nama_program' => $program->nama_program,
                    'desc_program' => $program->desc_program,
                    'waran_count' => $count,
                ]);
            }
        }

        // $chartData = [
        //     $totalLebih,
        //     $totalSeimbang,
        //     $totalKurang,
        // ];

        $chartData1 = [
            Waran::whereYear('created_at', now()->subYears(1)->year)->count(),
            Waran::whereYear('created_at', now()->year)->count(),
        ];

        $chartData2 = [
            Waran::count(),
            $totalLebih

        ];

        $chartData3 = [
            Waran::count(),
            $totalKurang,

        ];

        $chartData4 = [
            Waran::count(),
            $totalSeimbang
        ];

        $waranBaru =
            Waran::whereYear('created_at', now()->year)->count();


        return [
            Stat::make('Jumlah Waran', $totalWaran)
                ->description($waranBaru .  ' waran baharu tahun ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($chartData1)
                ->color('success'),

            Stat::make('Waran Lebih', $totalLebih)
                ->description('3 jawatan ditambah')
                ->descriptionIcon('heroicon-m-briefcase')
                ->chart($chartData2)
                ->color('primary'),

            Stat::make('Waran Kurang', $totalKurang)
                ->description('Perlu pengisian')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->chart($chartData3)
                ->color('warning'),

            Stat::make('Waran Seimbang', $totalSeimbang)
                ->description('Perlu pengisian')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->chart($chartData4)
                ->color('warning'),
        ];
    }
}
