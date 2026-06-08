<?php

namespace App\Filament\Resources\LetakJawatans\Pages;

use App\Filament\Resources\LetakJawatans\LetakJawatanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Stringable;

class CreateLetakJawatan extends CreateRecord
{
    protected static string $resource = LetakJawatanResource::class;

    protected function getCreateFormAction() : Action
    {
        return parent::getCreateFormAction()
        ->label('Tambah');
    }

    protected function getCreateAnotherFormAction() : Action
    {
        return parent::getCreateAnotherFormAction()
        ->hidden();
    }

    protected function getCancelFormAction() : Action
    {
        return parent::getCancelFormAction()
        ->label('Batal');
    }

    public function getTitle(): string
    {
        return 'Tambah Letak Jawatan';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
    }
}

