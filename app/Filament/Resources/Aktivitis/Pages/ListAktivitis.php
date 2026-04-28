<?php

namespace App\Filament\Resources\Aktivitis\Pages;

use App\Filament\Resources\Aktivitis\AktivitiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAktivitis extends ListRecords
{
    protected static string $resource = AktivitiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
