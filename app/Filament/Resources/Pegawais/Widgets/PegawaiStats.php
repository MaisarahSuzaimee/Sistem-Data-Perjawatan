<?php

namespace App\Filament\Resources\Pegawais\Widgets;

use App\Models\Pegawai;
use App\Services\NavigationBadgeCache;
use Filament\Widgets\StatsOverviewWidget;

class PegawaiStats extends StatsOverviewWidget
{
    protected string $view = 'filament.widgets.pegawai-stats';

    public int $totalPegawai = 0;

    public int $lengkap = 0;

    public int $tidakLengkap = 0;

    public function mount(): void
    {
        $this->totalPegawai = Pegawai::count();
        $this->tidakLengkap = NavigationBadgeCache::pegawaiTidakLengkapCount();
        $this->lengkap = max(0, $this->totalPegawai - $this->tidakLengkap);
    }
}
