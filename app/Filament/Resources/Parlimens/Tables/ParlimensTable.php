<?php

namespace App\Filament\Resources\Parlimens\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParlimensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex(),
                TextColumn::make('nama_parlimen')
                    ->label('Parlimen')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('duns.nama_dun')
                    ->label('Dun')
                    ->listWithLineBreaks()

            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->color("info")
                    ->label("")
                    ->tooltip("View"),
                EditAction::make()
                    ->modal()
                    ->label("")
                    ->tooltip("Edit"),
                DeleteAction::make()
                    ->label("")
                    ->tooltip("Delete"),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
