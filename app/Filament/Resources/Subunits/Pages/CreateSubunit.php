<?php

namespace App\Filament\Resources\Subunits\Pages;

use App\Filament\Resources\Subunits\SubunitResource;
use App\Models\Subunit;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSubunit extends CreateRecord
{
    protected static string $resource = SubunitResource::class;

    public function getTitle(): string
    {
        return 'KD / KKIA / Wad / Klinik';
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
        return SubunitResource::getUrl('index');
    }

    public function redirectToEditIfUnitHasSubunits(mixed $unitId): void
    {
        if (blank($unitId)) {
            return;
        }

        $existing = Subunit::query()
            ->where('unit_id', $unitId)
            ->orderBy('id')
            ->first();

        if (! $existing) {
            return;
        }

        Notification::make()
            ->title('Unit ini sudah mempunyai KD / KKIA / Wad / Klinik')
            ->body('Mengalihkan ke halaman kemaskini.')
            ->info()
            ->send();

        $this->redirect(SubunitResource::getUrl('edit', [
            'record' => $existing,
        ]));
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['subunits'] ?? null;
        $unitId = $data['unit_id'] ?? null;

        $existing = filled($unitId)
            ? Subunit::query()->where('unit_id', $unitId)->orderBy('id')->first()
            : null;

        if ($existing) {
            $this->redirectToEditIfUnitHasSubunits($unitId);

            return $existing;
        }

        if (is_array($items) && count($items) > 0) {
            $first = null;

            foreach ($items as $row) {
                $nama = strtoupper(trim((string) ($row['nama_subunit'] ?? '')));

                if ($nama === '') {
                    continue;
                }

                $record = static::getModel()::create([
                    'unit_id' => $unitId,
                    'nama_subunit' => $nama,
                    'parlimen_id' => $row['parlimen_id'] ?? null,
                    'dun_id' => $row['dun_id'] ?? null,
                ]);
                $record->syncAktivitis($row['aktiviti_ids'] ?? []);

                $first ??= $record;
            }

            return $first ?? static::getModel()::create([
                'unit_id' => $unitId,
                'nama_subunit' => strtoupper((string) ($data['nama_subunit'] ?? '')),
                'parlimen_id' => $data['parlimen_id'] ?? null,
                'dun_id' => $data['dun_id'] ?? null,
            ]);
        }

        unset($data['subunits']);

        if (isset($data['nama_subunit'])) {
            $data['nama_subunit'] = strtoupper((string) $data['nama_subunit']);
        }

        return static::getModel()::create($data);
    }
}
