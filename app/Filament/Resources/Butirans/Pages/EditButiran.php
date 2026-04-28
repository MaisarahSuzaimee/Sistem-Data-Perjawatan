<?php

namespace App\Filament\Resources\Butirans\Pages;

use App\Filament\Resources\Butirans\ButiranResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditButiran extends EditRecord
{
    protected static string $resource = ButiranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
