<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

class BlockedPegawaiDelete
{
    public static function notify(): void
    {
        Notification::make()
            ->title('Tidak boleh padam')
            ->body('Tidak boleh padam kerana terdapat pegawai yang ditugaskan.')
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
