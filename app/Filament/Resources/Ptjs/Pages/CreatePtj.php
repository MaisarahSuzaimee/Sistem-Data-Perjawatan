<?php

namespace App\Filament\Resources\Ptjs\Pages;

use App\Filament\Resources\Ptjs\PtjResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePtj extends CreateRecord
{
    protected static string $resource = PtjResource::class;

   protected static ?string $title = 'Tambah PTJ';
}
