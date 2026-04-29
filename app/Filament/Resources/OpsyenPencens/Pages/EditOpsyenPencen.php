<?php

namespace App\Filament\Resources\OpsyenPencens\Pages;

use App\Filament\Resources\OpsyenPencens\OpsyenPencenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpsyenPencen extends EditRecord
{
    protected static string $resource = OpsyenPencenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
