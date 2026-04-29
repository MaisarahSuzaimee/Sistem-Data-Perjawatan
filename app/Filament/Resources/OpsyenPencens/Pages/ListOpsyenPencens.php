<?php

namespace App\Filament\Resources\OpsyenPencens\Pages;

use App\Filament\Resources\OpsyenPencens\OpsyenPencenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpsyenPencens extends ListRecords
{
    protected static string $resource = OpsyenPencenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->modal(),
        ];
    }
}
