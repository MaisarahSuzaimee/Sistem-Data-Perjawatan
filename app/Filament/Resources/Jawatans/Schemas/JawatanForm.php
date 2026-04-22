<?php

namespace App\Filament\Resources\Jawatans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JawatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('kod_jawatan')
                    ->label('Kod Jawatan')
                    ->required()
                    ->columnSpan(1),
                TextInput::make('desc_jawatan')
                    ->label('Jawatan')
                    ->required()
                    ->columnSpan(2),
                Select::make('gred_id')
                ->label('Gred')
                    ->multiple()
                    ->relationship('greds', 'kod_gred')
                    ->preload()
                    ->columnSpanFull()
            ]);
    }
}
