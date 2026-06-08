<?php

namespace App\Filament\Resources\Pegawais\Pages;

use App\Filament\Resources\Pegawais\PegawaiResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreatePegawai extends CreateRecord
{
    protected static string $resource = PegawaiResource::class;

    public function getTitle(): string
    {
        return 'Tambah Pegawai';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
    }

    protected function afterCreate(): void
    {
        if ($this->data['is_kontrak']) {
            \App\Models\PegawaiKontrak::create([
                'pegawai_id' => $this->record->id,

                'tarikh_lantikan1' => $this->data['tarikh_lantikan1'] ?? null,
                'tarikh_tamat1' => $this->data['tarikh_tamat1'] ?? null,
                'tarikh_lantikan2' => $this->data['tarikh_lantikan2'] ?? null,
                'tarikh_tamat2' => $this->data['tarikh_tamat2'] ?? null,
                'tarikh_lantikan3' => $this->data['tarikh_lantikan3'] ?? null,
                'tarikh_tamat3' => $this->data['tarikh_tamat3'] ?? null,
                'tarikh_lantikan4' => $this->data['tarikh_lantikan4'] ?? null,
                'tarikh_tamat4' => $this->data['tarikh_tamat4'] ?? null,
                'tarikh_lantikan5' => $this->data['tarikh_lantikan5'] ?? null,
                'tarikh_tamat5' => $this->data['tarikh_tamat5'] ?? null,
            ]);
        }
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Tambah');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden();
    }


}


