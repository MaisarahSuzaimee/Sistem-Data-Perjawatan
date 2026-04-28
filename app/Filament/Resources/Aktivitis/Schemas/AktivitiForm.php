<?php

namespace App\Filament\Resources\Aktivitis\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AktivitiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->columns(3)
            ->components([
                TextInput::make('no_aktivit')
                ->columnSpan(1)
                ->readOnly(),
                TextInput::make('nama_aktiviti')
                ->columnSpan(2)
                ->readOnly(),
                Repeater::make('butiran') // ✅ lowercase MUST match relationship
                            ->relationship('butiran') // 🔥 THIS IS THE KEY
                            ->label('Butiran')
                            ->addActionLabel('Tambah No Butiran')
                            ->schema([
                                TextInput::make('butiran')
                                    ->required(),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['butiran'] ?? null)
                            ->collapsed()
                            ->columnSpanFull()

            ]);
    }
}
