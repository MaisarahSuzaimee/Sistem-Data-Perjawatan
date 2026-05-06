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
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {

                                $items = [];

                                for ($i = 0; $i < (int) $state; $i++) {
                                    $items[] = [
                                        'ptj_id' => null,
                                        'aktiviti_id' => null,
                                        'butiran' => null,
                                        'jawatan_gred_id' => null,
                                        'pegawai_id' => null,
                                    ];
                                }

                                $set('waranJawatan', $items);
                            }),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull()
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Maklumat Jawatan')
                    ->schema([
                        Repeater::make('waranJawatan')
                            ->relationship()
                            ->addable(false)
                            ->deletable(false)
                            ->label('Butiran Jawatan')
                            ->schema([
                                Select::make('ptj_id')
                                    ->relationship('ptj', 'nama_ptj')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Select::make('aktiviti_id')
                                    ->relationship('aktiviti', 'nama_aktiviti')
                                    ->required()
                                    ->options(function () {
                                        return \App\Models\Program::with('aktiviti')
                                            ->orderBy('nama_program')
                                            ->get()
                                            ->mapWithKeys(function ($program) {
                                                return [
                                                    $program->nama_program => $program->aktiviti
                                                        ->mapWithKeys(function ($aktiviti) {
                                                            return [
                                                                $aktiviti->id => $aktiviti->no_aktivit . ' - ' . $aktiviti->nama_aktiviti
                                                            ];
                                                        })
                                                        ->toArray(),
                                                ];
                                            })
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('butiran')
                                    ->required(),

                                Select::make('jawatan_gred_id')
                                    ->label('Jawatan')
                                    // ->multiple()
                                    ->relationship(
                                        'jawatanGred',
                                        'id',
                                        fn($query) => $query
                                            ->join('jawatans', 'jawatan__greds.jawatan_id', '=', 'jawatans.id')
                                            ->join('greds', 'jawatan__greds.gred_id', '=', 'greds.id')
                                            ->select('jawatan__greds.*')
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        $record->jawatan->desc_jawatan . ' (' . $record->gred->kod_gred . ')'
                                    )
                                    ->searchable([
                                        'jawatans.desc_jawatan',
                                        'greds.kod_gred'
                                    ])
                                    ->preload(),

                                Select::make('pegawai_id')
                                    ->relationship('pegawai', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columns(2)
                            ->itemLabel(function (array $state) {
                                if (!$state['ptj_id']) {
                                    return 'Tambah Jawatan';
                                }

                                $ptj = \App\Models\Ptj::find($state['ptj_id']);

                                return $ptj?->nama_ptj ?? 'Unknown PTJ';
                            })->collapsed(),

                    ])
                    ->columnSpanFull(),



            ]);
    }
}
