<?php

namespace App\Filament\Resources\Jawatans\Pages;

use App\Filament\Resources\Jawatans\JawatanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJawatan extends EditRecord
{
    protected static string $resource = JawatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
