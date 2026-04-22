<?php

namespace App\Filament\Resources\Subunits\Pages;

use App\Filament\Resources\Subunits\SubunitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubunit extends EditRecord
{
    protected static string $resource = SubunitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
