<?php

namespace App\Filament\Resources\Warans\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->components([
                Section::make('Maklumat Waran')
                    ->schema([
                        TextInput::make('no_waran')
                            ->label('No Waran'),
                        TextInput::make('jik')
                            ->label('Jumlah Jawatan')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $items = [];

                                for ($i = 0; $i < (int) $state; $i++) {
                                    $items[] = [
                                        'ptj' => null,
                                        'jik' => 1,
                                        'catatan' => '',
                                    ];
                                }

                                $set('jawatan', $items);
                            }),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull()
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Maklumat Jawatan')
                    ->schema([
                        Repeater::make('jawatan')
                            ->schema([
                                Select::make('ptj_id')
                                    ->label('PTJ')
                                    ->relationship()
                                    ->required(),
                                Select::make('bahagian')
                                    ->label('Bahagian')
                                    ->required(),
                                Select::make('unit')
                                    ->label('Unit')
                                    ->required(),
                                Select::make('subunit')
                                    ->label('Sub Unit')
                                    ->required(),
                                Select::make('program')
                                    ->label('Program')
                                    ->required(),
                                Select::make('aktiviti')
                                    ->label('Aktiviti')
                                    ->required(),
                                Select::make('pegawai')
                                    ->label('Pegawai')
                                    ->required()
                                    ->columnSpanFull(),

                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['ptj'] ?? null)
                            ->collapsed(),

                    ])
                    ->columnSpanFull(),



            ]);
    }
}
