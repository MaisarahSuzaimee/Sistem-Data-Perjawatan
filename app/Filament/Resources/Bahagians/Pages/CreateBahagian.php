<?php

namespace App\Filament\Resources\Bahagians\Pages;

use App\Filament\Resources\Bahagians\BahagianResource;
use App\Models\Bahagian;
use App\Models\Ptj;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateBahagian extends CreateRecord
{
    protected static string $resource = BahagianResource::class;

    public function getTitle(): string
    {
        return 'Tambah Bahagian';
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
        return BahagianResource::getUrl('index');
    }

    public function redirectToEditIfPtjHasBahagian(mixed $ptjId): void
    {
        if (blank($ptjId)) {
            return;
        }

        $existing = Bahagian::query()
            ->where('ptj_id', $ptjId)
            ->orderBy('id')
            ->first();

        if (! $existing) {
            return;
        }

        Notification::make()
            ->title('PTJ ini sudah mempunyai bahagian')
            ->body('Mengalihkan ke halaman kemaskini.')
            ->info()
            ->send();

        $this->redirect(BahagianResource::getUrl('edit', [
            'record' => $existing,
        ]));
    }

    protected function handleRecordCreation(array $data): Model
    {
        $ptjId = isset($data['ptj_id']) ? (int) $data['ptj_id'] : null;

        if (! Ptj::usesBahagianHierarchyFor($ptjId)) {
            throw ValidationException::withMessages([
                'ptj_id' => 'Bahagian hanya dibenarkan untuk PTJ JKN (termasuk VEKTOR).',
            ]);
        }

        $existing = Bahagian::query()
            ->where('ptj_id', $ptjId)
            ->orderBy('id')
            ->first();

        if ($existing) {
            $this->redirectToEditIfPtjHasBahagian($ptjId);

            return $existing;
        }

        $items = $data['bahagians'] ?? null;

        if (is_array($items) && count($items) > 0) {
            $first = null;

            foreach ($items as $row) {
                $nama = strtoupper(trim((string) ($row['nama_bahagian'] ?? '')));

                if ($nama === '') {
                    continue;
                }

                $record = static::getModel()::create([
                    'ptj_id' => $ptjId,
                    'nama_bahagian' => $nama,
                ]);

                $first ??= $record;
            }

            return $first ?? static::getModel()::create([
                'ptj_id' => $ptjId,
                'nama_bahagian' => strtoupper((string) ($data['nama_bahagian'] ?? '')),
            ]);
        }

        unset($data['bahagians']);

        if (isset($data['nama_bahagian'])) {
            $data['nama_bahagian'] = strtoupper((string) $data['nama_bahagian']);
        }

        return static::getModel()::create($data);
    }
}
