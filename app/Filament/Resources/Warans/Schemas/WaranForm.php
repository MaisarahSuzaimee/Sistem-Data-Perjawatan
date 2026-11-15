<?php

namespace App\Filament\Resources\Warans\Schemas;

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
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        TextInput::make('jik')
                            ->label('Jumlah Jawatan Ditambah')
                            ->required()
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
                            ->default(0)
                            ->readonly(
                                fn () => ! auth()->user()?->isSuperadmin()
                                    && ! auth()->user()?->isAdmin()
                            )
                            ->dehydrated(fn () => auth()->user()?->isSuperadmin() || auth()->user()?->isAdmin()),

                        Textarea::make('catatan')
                            ->label('Catatan')
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
