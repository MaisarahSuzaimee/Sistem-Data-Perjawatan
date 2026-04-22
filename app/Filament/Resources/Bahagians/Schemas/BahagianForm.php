<?php

namespace App\Filament\Resources\Bahagians\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BahagianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ptj_id')
                    ->label('Ptj')
                    ->relationship('ptj', 'nama_ptj')
                    ->searchable()
                    ->preload()
                    ->required()
                    // ->unique()
                    ->columnSpanFull(),
                TextInput::make('nama_bahagian')
                    ->label('Bahagian')
                    ->required()
                    ->columnSpanFull()
                    ->unique()
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper(($state)))
                    ->extraInputAttributes((['style' => 'text-transform:uppercase'])),

                Repeater::make('units')
                ->label('Unit')
                    ->relationship()
                    ->schema([
                        TextInput::make('nama_unit')
                            ->required()
                            ->unique()
                            ->dehydrateStateUsing(fn($state) => $state ? strtoupper($state) : null)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
