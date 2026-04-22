<?php

namespace App\Filament\Resources\Subunits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;


class SubunitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('dun_id')
                    ->label('Dun')
                    ->relationship('dun', 'nama_dun')
                    ->searchable()
                    ->preload(),
                Select::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'nama_unit')
                    ->searchable()
                    ->preload(),
                TextInput::make('nama_subunit')
                    ->label('Sub Unit')
                    ->required()
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper($state))
                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                    ->columnSpanFull(),

            ]);
    }
}
