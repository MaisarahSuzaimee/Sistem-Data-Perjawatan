<?php

namespace App\Filament\Resources\Hebahans\Pages;

use App\Filament\Resources\Hebahans\HebahanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHebahan extends EditRecord
{
    protected static string $resource = HebahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
