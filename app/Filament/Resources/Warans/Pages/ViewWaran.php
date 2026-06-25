<?php

namespace App\Filament\Resources\Warans\Pages;

use App\Filament\Resources\Warans\WaranResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWaran extends ViewRecord
{
    protected static string $resource = WaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
