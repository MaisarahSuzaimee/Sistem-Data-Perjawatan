<?php

namespace App\Filament\Resources\Butirans\Pages;

use App\Filament\Resources\Butirans\ButiranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListButirans extends ListRecords
{
    protected static string $resource = ButiranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
