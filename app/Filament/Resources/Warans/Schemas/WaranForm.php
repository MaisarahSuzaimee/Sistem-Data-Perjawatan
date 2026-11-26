<?php

namespace App\Filament\Resources\Warans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                            ->label('No Waran')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        DatePicker::make('tarikh_kuatkuasa')
                            ->label('Tarikh Kuatkuasa Waran')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        Textarea::make('nama_waran')
                            ->label('Nama Waran')
                            ->rows(3)
                            ->autosize()
                            ->columnSpanFull()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? mb_strtoupper(trim((string) $state), 'UTF-8') : null)
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        TextInput::make('jik')
                            ->label('Jumlah Jawatan Ditambah')
                            ->numeric()
                            ->minValue(0)
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        TextInput::make('jik_tolak')
                            ->label('Jumlah Jawatan Ditolak')
                            ->numeric()
                            ->minValue(0)
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
