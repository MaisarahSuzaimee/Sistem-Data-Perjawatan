<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

class BlockedPegawaiDelete
{
    public static function notify(): void
    {
        Notification::make()
            ->title('Akses untuk memadam waran telah disekat')
            ->body('Anda tidak dapat memadam waran ini kerana terdapat penyandang yang menyandang jawatan di bawah waran ini.')
            ->danger()
            ->send();
    }

    public static function haltIfAssigned(Action $action, bool $assigned): void
    {
        if (! $assigned) {
            return;
        }

        static::notify();
        $action->halt();
    }
}
