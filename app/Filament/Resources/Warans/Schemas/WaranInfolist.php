<?php

namespace App\Filament\Resources\Warans\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Waran')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('no_waran')
                            ->label('No Waran'),
                        TextEntry::make('tarikh_kuatkuasa')
                            ->label('Tarikh Kuatkuasa Waran')
                            ->date('d F Y')
                            ->placeholder('-'),
                        TextEntry::make('nama_waran')
                            ->label('Nama Waran')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('jik')
                            ->label('Jumlah Jawatan Ditambah'),
                        TextEntry::make('jik_tolak')
                            ->label('Jumlah Jawatan Ditolak')
                            ->placeholder('0'),
                        TextEntry::make('catatan')
                            ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->catatan)),
                    ]),

                // Section::make('Senarai Penempatan')
                //     ->columnSpanFull()
                //     ->schema([
                //         RepeatableEntry::make('waranJawatan')
                //             ->schema([
                //                 TextEntry::make('butiran')
                //                     ->label('Butiran'),

                //                 TextEntry::make('tarikh_kuatkuasa')
                //                     ->label('Tarikh Kuatkuasa Waran')
                //                     ->date('d F Y')
                //                     ->placeholder('Tiada'),

                //                 TextEntry::make('ptj.nama_ptj')
                //                     ->label('PTJ'),

                //                 TextEntry::make('pegawai.nama')
                //                     ->label('Pegawai')
                //                     ->placeholder('Tiada Penyandang'),
                //             ])
                //             ->columns(2)
                //     ])

            ]);

    }
}
