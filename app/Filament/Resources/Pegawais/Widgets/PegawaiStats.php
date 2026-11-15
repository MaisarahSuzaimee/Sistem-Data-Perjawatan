<?php

namespace App\Filament\Resources\Pegawais\Widgets;

use App\Models\Pegawai;
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

        $this->tidakLengkap = Pegawai::query()->tidakLengkap()->count();

        $this->lengkap = $this->totalPegawai - $this->tidakLengkap;
    }
}
