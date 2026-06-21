<?php

namespace App\Filament\Resources\LetakJawatans\Pages;

use App\Filament\Exports\LetakJawatanExporter;
use App\Filament\Resources\LetakJawatans\LetakJawatanResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListLetakJawatans extends ListRecords
{
    protected static string $resource = LetakJawatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Tambah Letak Jawatan'),
             ExportAction::make()
            ->exporter(LetakJawatanExporter::class)
        ];
    }

    public function getBreadcrumb(): string{
        return 'Senarai';
    }
}
