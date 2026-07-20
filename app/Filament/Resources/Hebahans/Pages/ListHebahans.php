<?php

namespace App\Filament\Resources\Hebahans\Pages;

use App\Filament\Resources\Hebahans\HebahanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHebahans extends ListRecords
{
    protected static string $resource = HebahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Hebahan'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Senarai';
    }
}
