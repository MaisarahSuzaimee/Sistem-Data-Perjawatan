<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    public function getTitle(): string
    {
        return 'Tambah Unit';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
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

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function getRedirectUrl(): string
    {
        return UnitResource::getUrl('index');
    }

    public function redirectToEditIfParentHasUnits(?int $ptjId, ?int $bahagianId): void
    {
        $existing = $this->findExistingUnitForParent($ptjId, $bahagianId);

        if (! $existing) {
            return;
        }

        Notification::make()
            ->title(filled($bahagianId)
                ? 'Bahagian ini sudah mempunyai unit'
                : 'PTJ ini sudah mempunyai unit')
            ->body('Mengalihkan ke halaman kemaskini.')
            ->info()
            ->send();

        $this->redirect(UnitResource::getUrl('edit', [
            'record' => $existing,
        ]));
    }

    protected function findExistingUnitForParent(?int $ptjId, ?int $bahagianId): ?Unit
    {
        if (filled($bahagianId)) {
            return Unit::query()
                ->where('bahagian_id', $bahagianId)
                ->orderBy('id')
                ->first();
        }

        if (blank($ptjId)) {
            return null;
        }

        return Unit::query()
            ->where('ptj_id', $ptjId)
            ->whereNull('bahagian_id')
            ->orderBy('id')
            ->first();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $units = $data['units'] ?? null;
        $ptjId = isset($data['ptj_id']) ? (int) $data['ptj_id'] : null;
        $bahagianId = isset($data['bahagian_id']) ? (int) $data['bahagian_id'] : null;

        $existing = $this->findExistingUnitForParent($ptjId, $bahagianId);

        if ($existing) {
            $this->redirectToEditIfParentHasUnits($ptjId, $bahagianId);

            return $existing;
        }

        // Bulk creation via Repeater - each unit has its own parlimen/dun
        if (is_array($units) && count($units) > 0) {
            $firstRecord = null;

            foreach ($units as $unitData) {
                $nama = strtoupper(trim((string) ($unitData['nama_unit'] ?? '')));

                if ($nama === '') {
                    continue;
                }

                $payload = [
                    'ptj_id' => $ptjId,
                    'bahagian_id' => $bahagianId,
                    'nama_unit' => $nama,
                    'parlimen_id' => $unitData['parlimen_id'] ?? null,
                    'dun_id' => $unitData['dun_id'] ?? null,
                ];

                $record = static::getModel()::create($payload);
                $record->syncAktivitis($unitData['aktiviti_ids'] ?? []);

                $firstRecord ??= $record;
            }

            return $firstRecord ?? static::getModel()::create([
                'ptj_id' => $ptjId,
                'bahagian_id' => $bahagianId,
                'nama_unit' => strtoupper((string) ($data['nama_unit'] ?? '')),
                'parlimen_id' => $data['parlimen_id'] ?? null,
                'dun_id' => $data['dun_id'] ?? null,
            ]);
        }

        // Fallback single create (edit-form compatibility)
        unset($data['units'], $data['program_id']);

        if (isset($data['nama_unit'])) {
            $data['nama_unit'] = strtoupper((string) $data['nama_unit']);
        }

        return static::getModel()::create($data);
    }
}
