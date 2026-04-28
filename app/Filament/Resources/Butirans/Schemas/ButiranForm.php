<?php

namespace App\Filament\Resources\Butirans\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ButiranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('aktiviti_id')
                    ->label('Aktiviti')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull()
                    ->options(function () {
                        return \App\Models\Program::with('aktiviti')
                            ->orderBy('nama_program')
                            ->get()
                            ->mapWithKeys(function ($program) {
                                return [
                                    $program->nama_program => $program->aktiviti
                                        ->sortBy('nama_aktiviti')
                                        ->mapWithKeys(function ($aktiviti) {
                                            return [
                                                $aktiviti->id =>
                                                $aktiviti->no_aktivit . ' - ' . $aktiviti->nama_aktiviti,
                                            ];
                                        })
                                        ->toArray(),
                                ];
                            })
                            ->toArray();
                    }),

                // Repeater::make('butiran')
                //     // ->relationship()
                //     ->label('Butiran')
                //     ->schema([
                //         TextInput::make('butiran')
                //             ->required(),


                //     ])
                //     ->defaultItems(1)
                //     ->addActionLabel('Tambah Butiran')
                //     ->reorderable()
                //     ->cloneable()
                //     ->collapsible()
                //     ->mutateRelationshipDataBeforeCreateUsing(function (array $data, callable $get) {
                //         $data['aktiviti_id'] = $get('aktiviti_id');
                //         return $data;
                //     })

                Repeater::make('butiran')
    // ->relationship('butiran')
    ->schema([
        TextInput::make('butiran')->required(),
        TextInput::make('catatan'),
    ])
            ]);
    }
}
