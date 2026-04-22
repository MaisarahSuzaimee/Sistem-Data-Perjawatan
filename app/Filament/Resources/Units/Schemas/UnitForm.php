<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bahagian_id')
                    ->label('Bahagian')
                    ->relationship('bahagian', 'nama_bahagian')
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpanFull(),

                TextInput::make('nama_unit')
                    ->label('Unit')
                    ->required()
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper($state))
                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                    ->columnSpanFull()

            ]);
    }
}
