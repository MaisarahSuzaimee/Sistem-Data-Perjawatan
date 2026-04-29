<?php

namespace App\Filament\Resources\OpsyenPencens\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OpsyenPencenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('opsyen')
                ->label('Opsyen Pencen')
                ->required()
                ->columnSpanFull()
            ]);
    }
}
