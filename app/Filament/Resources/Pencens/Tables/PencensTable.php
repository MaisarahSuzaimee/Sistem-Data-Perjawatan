<?php

namespace App\Filament\Resources\Pencens\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PencensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex(),
                TextColumn::make('nama')
                    ->label('Nama Pegawai'),
                    // ->formatStateUsing(
                    //     fn($record) =>
                    //     '<strong>' . ($record->nama ?? '-') . '</strong><br>' .
                    //     ($record->nokp ?? '-') . '<br>' .
                    //     (
                    //         $record->jawatan_gred
                    //         ? $record->jawatan_gred->jawatan->desc_jawatan .
                    //         ' (' . $record->jawatan_gred->gred->kod_gred . ')'
                    //         : '-'
                    //     )
                    // )
                    // ->html(),
                TextColumn::make('ptj.nama_ptj'),
                TextColumn::make('tarikh')
                    ->label('Tarikh Pencen / Kuatkuasa')
                    ->getStateUsing(function ($record) {
                        return $record->tarikh_kuatkuasa ?? $record->tarikh_pencen;
                    })
                    ->date('d F Y')

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
