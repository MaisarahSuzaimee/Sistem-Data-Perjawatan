<?php

namespace App\Filament\Resources\Greds\Pages;

use App\Filament\Resources\Greds\GredResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGred extends EditRecord
{
    protected static string $resource = GredResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
