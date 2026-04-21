<?php

namespace App\Filament\Resources\Ptjs\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PtjForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->components([
                TextInput::make('nama_ptj')
                    ->label('Nama PTJ')
                    ->required()
                    ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper($state))
                    ->columnSpanFull(),
                TextInput::make('kod_ptj')
                    ->label('Kod Ptj')
                    ->required()
                    ->numeric(),
                TextInput::make('rujukan_surat')
                    ->label('Rujukan Surat')
                    ->required(),
                TextInput::make('pengarah')
                    ->label('Pengarah')
                    ->required()
                    ->dehydrateStateUsing(fn(string $state): string => strtoupper($state))
                    ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                    ->columnSpanFull(),
                Textarea::make('alamat')
                    ->label('Alamat')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Checkbox::make('is_jkn')
                    ->label('Is JKN'),


            ]);
    }
}
