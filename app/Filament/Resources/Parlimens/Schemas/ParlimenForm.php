<?php

namespace App\Filament\Resources\Parlimens\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ParlimenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_parlimen')
                    ->label('Parlimen')
                    ->required()
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper($state))
                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                    ->columnSpanFull()
                    ->unique(),
                Repeater::make('duns')
                    ->label('Dun')
                    ->relationship('duns')
                    ->schema([
                        TextInput::make('nama_dun')
                            ->required()
                            ->dehydrateStateUsing(fn($state) => $state ? strtoupper($state) : null)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
