<?php

namespace App\Filament\Resources\ButiranJawatans\Pages;

use App\Filament\Resources\ButiranJawatans\ButiranJawatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListButiranJawatans extends ListRecords
{
    protected static string $resource = ButiranJawatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
