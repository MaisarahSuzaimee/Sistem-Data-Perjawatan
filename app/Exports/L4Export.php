<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class L4Export implements FromCollection
{
    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        return collect();
    }
}
