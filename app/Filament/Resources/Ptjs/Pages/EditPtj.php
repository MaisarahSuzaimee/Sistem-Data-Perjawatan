<?php

namespace App\Filament\Resources\Ptjs\Pages;

use App\Filament\Resources\Ptjs\PtjResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPtj extends EditRecord
{
    protected static string $resource = PtjResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    
}
