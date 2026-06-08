<?php

namespace App\Filament\Resources\Warans\Pages;

use App\Filament\Resources\Warans\WaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWaran extends CreateRecord
{
    protected static string $resource = WaranResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tambah Waran';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
    }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Tambah'),

            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
