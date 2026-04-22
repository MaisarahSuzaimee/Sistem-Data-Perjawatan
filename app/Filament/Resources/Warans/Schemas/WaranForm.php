<?php

namespace App\Filament\Resources\Warans\Schemas;

// use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class WaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Wizard::make([

                    // 🟦 Step 1: Maklumat Waran
                    \Filament\Schemas\Components\Wizard\Step::make('Maklumat Waran')
                    ->columns(2)
                        ->schema([
                            TextInput::make('no_waran')
                                ->label('No Waran')
                                ->required(),

                            TextInput::make('puncakuasa')
                                ->label('Punca Kuasa')
                                    ->required(),
                            TextInput::make('jik')
                            ->label('Bilangan Jawatan')
                            ->required(),
                            Textarea::make('catatan')
                            ->label('Catatan')
                        ]),

                    // 🟩 Step 2: Program
                    \Filament\Schemas\Components\Wizard\Step::make('Program'),
                    //     ->schema([
                    //         Select::make('program_id')
                    //             ->label('Program')
                    //             ->relationship('program', 'nama_program')
                    //             ->searchable()
                    //             ->preload()
                    //             ->required(),
                    //     ]),

                    // 🟨 Step 3: Nama Penyandang
                    \Filament\Schemas\Components\Wizard\Step::make('Nama Penyandang')
                //         ->schema([
                //             TextInput::make('nama_penyandang')
                //                 ->label('Nama Penyandang')
                //                 ->required(),

                //             TextInput::make('no_kp')
                //                 ->label('No KP')
                //                 ->required(),
                //         ]),
                ])
                ->columnSpanFull()
            ]);
    }
}
