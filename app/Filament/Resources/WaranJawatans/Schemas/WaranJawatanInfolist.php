<?php

namespace App\Filament\Resources\WaranJawatans\Schemas;

use App\Models\WaranJawatan;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class WaranJawatanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->extraAttributes(fn (WaranJawatan $record): array => [
                        'class' => static::programWindowClass($record),
                    ])
                    ->tabs([
                        Tab::make('Maklumat Penyandang')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                ViewEntry::make('maklumat_penyandang')
                                    ->view('filament.infolists.maklumat-penyandang-table')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Maklumat Jawatan')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                ViewEntry::make('maklumat_jawatan')
                                    ->view('filament.infolists.maklumat-jawatan-table')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function programWindowClass(WaranJawatan $record): ?string
    {
        $namaProgram = $record->aktiviti?->program?->nama_program;

        if (blank($namaProgram)) {
            return null;
        }

        $classes = ['fi-modal-window-program'];
        $number = (int) filter_var($namaProgram, FILTER_SANITIZE_NUMBER_INT);

        if ($number >= 1) {
            $classes[] = 'fi-modal-window-program-'.$number;
        }

        return implode(' ', $classes);
    }
}
