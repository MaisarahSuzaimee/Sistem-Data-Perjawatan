<?php

namespace App\Filament\Resources\Pencens\Pages;

use App\Filament\Resources\Pencens\PencenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPencens extends ListRecords
{
    protected static string $resource = PencenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Tambah Penamatan Perkhidmatan'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Senarai';
    }
}
