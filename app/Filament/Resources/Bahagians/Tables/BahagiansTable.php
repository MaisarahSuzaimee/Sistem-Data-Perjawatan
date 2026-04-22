<?php

namespace App\Filament\Resources\Bahagians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BahagiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex(),
                TextColumn::make('ptj.nama_ptj')
                    ->label('PTJ')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_bahagian')
                    ->label('Bahagian')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('units.nama_unit')
                    ->label('Unit')
                    ->listWithLineBreaks()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('')
                    ->color('info'),
                EditAction::make()
                    ->label('')
                    ->modal(),
                DeleteAction::make()
                    ->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
