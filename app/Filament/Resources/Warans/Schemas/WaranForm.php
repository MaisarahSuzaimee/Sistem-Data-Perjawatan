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
                                    ->required()
                                    ->columnSpanFull()
                                    ->searchable()
                                    ->preload()
                                    ->options(
                                        \App\Models\Ptj::orderBy('nama_ptj')
                                            ->pluck('nama_ptj', 'id')
                                            ->toArray()
                                    ),

                                Select::make('aktiviti_id')
                                    ->label('Aktiviti')
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        return \App\Models\Program::with('aktiviti')
                                            ->orderBy('nama_program')
                                            ->get()
                                            ->mapWithKeys(function ($program) {
                                                return [
                                                    $program->nama_program => $program->aktiviti
                                                        ->pluck('nama_aktiviti', 'id')
                                                        ->toArray(),
                                                ];
                                            })
                                            ->toArray();
                                    }),
                                Select::make('butiran_id')
    ->label('Butiran')
    ->required()
    ->searchable()
    ->options(function (callable $get) {

        $aktivitiId = $get('aktiviti_id');

        if (! $aktivitiId) {
            return [];
        }

        return \App\Models\Butiran::where('aktiviti_id', $aktivitiId)
            ->orderBy('butiran')
            ->pluck('butiran', 'id')
            ->toArray();
    }),
                                Select::make('Jawatan')
                                    ->label('Jawatan'),
                                Select::make('Gred')
                                    ->label('Gred'),
                                Select::make('pegawai_id')
                                    ->label('Nama Pegawai')
                                    ->columnSpanFull()

                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['ptj'] ?? null)
                            ->collapsed(),

                    ])
                    ->columnSpanFull(),



            ]);
    }
}
