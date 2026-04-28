<?php

namespace App\Filament\Resources\Programs\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_program')
                    ->label('Program')
                    ->required()
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper($state))
                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                    ->columnSpanFull()
                    ->unique(),
                Repeater::make('aktiviti')
                    ->label('Aktiviti')
                    ->relationship('aktiviti')
                    ->columnSpanFull()
                    ->columns(2)
                    ->addActionLabel('Tambah Aktiviti')
                    ->schema([
                        TextInput::make('no_aktivit')
                            ->label('No Aktiviti')
                            ->required()
                            ->dehydrateStateUsing(fn($state) => $state ? strtoupper($state) : null)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                        TextInput::make('nama_aktiviti')
                            ->label('Nama Aktiviti')
                            ->dehydrateStateUsing(fn($state) => $state ? strtoupper($state) : null)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                        // Textarea::make('desc_aktiviti')
                        //     ->label('Butiran Aktiviti')
                        //     ->dehydrateStateUsing(fn($state) => $state ? strtoupper($state) : null)
                        //     ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        //     ->columnSpanFull(),
                            Repeater::make('Butiran')
                    ->label('Butiran')
                    ->addActionLabel('Tambah No Butiran')
                    ->schema([
                        TextInput::make('no_butiran')
                            ->required(),
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['no_butiran'] ?? null)
                    ->collapsed()
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['nama_aktiviti'] ?? null)
                    ->collapsed()


            ]);
    }
}
