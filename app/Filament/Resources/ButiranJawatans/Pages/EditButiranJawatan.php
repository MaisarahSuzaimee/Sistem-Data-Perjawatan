<?php

namespace App\Filament\Resources\ButiranJawatans\Pages;

use App\Filament\Resources\ButiranJawatans\ButiranJawatanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditButiranJawatan extends EditRecord
{
    protected static string $resource = ButiranJawatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
