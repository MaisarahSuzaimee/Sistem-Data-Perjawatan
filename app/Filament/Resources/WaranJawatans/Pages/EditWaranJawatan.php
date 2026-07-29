<?php

namespace App\Filament\Resources\WaranJawatans\Pages;

use App\Filament\Resources\WaranJawatans\WaranJawatanResource;
use App\Models\Gred;
use App\Models\Tbk;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWaranJawatan extends EditRecord
{
    protected static string $resource = WaranJawatanResource::class;

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            DeleteAction::make()
                ->label('Padam'),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    // protected function afterCreate(): void
    // {
    //     \App\Models\Tbk::updateOrCreate(
    //         [
    //             'pegawai_id' => $this->record->pegawai_id,
    //         ],
    //         [
    //             'tbk' => $this->data['tbk'],
    //             'gred_id' => $this->data['tbk_gred_id'],
    //         ]
    //     );
    // }

    protected function afterSave(): void
    {
        $gredIds = $this->record->gred_ids;

        // Condition 1: Must have multiple gred
        if (count($gredIds) <= 1) {
            Tbk::where('waran_jawatan_id', $this->record->id)->delete();
            return;
        }

        $selectedGreds = Gred::query()
            ->whereIn('id', $gredIds)
            ->orderBy('kod_gred')
            ->get();


        // Condition 2: Gred must be between 1 to 8
        $gredNumbers = $selectedGreds->map(function ($gred) {

            // Example: U5 -> 5
            return (int) filter_var($gred->kod_gred, FILTER_SANITIZE_NUMBER_INT);

        });


        if ($gredNumbers->min() < 1 || $gredNumbers->max() > 8) {

            // remove existing TBK if condition no longer valid
            Tbk::where('waran_jawatan_id', $this->record->id)->delete();

            return;
        }


        $pegawai = $this->record->pegawai;

        if (!$pegawai) {
            return;
        }


        $pegawaiGredId = $pegawai->jawatan_gred->gred_id;


        $gredList = $selectedGreds->pluck('id')->values();


        // Find pegawai position in grade list
        $tbk = $gredList->search($pegawaiGredId);


        if ($tbk === false) {
            return;
        }


        // Condition 3: Pegawai must not be lowest grade
        if ($tbk === 0) {

            Tbk::where('waran_jawatan_id', $this->record->id)->delete();

            return;
        }


        // Create TBK
        Tbk::updateOrCreate(
            [
                'waran_jawatan_id' => $this->record->id,
            ],
            [
                'pegawai_id' => $pegawai->id,
                'gred_id' => $gredList->first(),
                'tbk' => $tbk,
            ]
        );
    }
}
