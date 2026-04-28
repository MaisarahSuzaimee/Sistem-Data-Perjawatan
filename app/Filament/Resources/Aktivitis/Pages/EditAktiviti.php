<?php

namespace App\Filament\Resources\Aktivitis\Pages;

use App\Filament\Resources\Aktivitis\AktivitiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAktiviti extends EditRecord
{
    protected static string $resource = AktivitiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
