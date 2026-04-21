<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex(),
                // TextColumn::make('name')
                //     ->label('Nama / IC / No Tel.')
                //     ->formatStateUsing(
                //         fn($record) =>
                //         '<strong>' . $record->name . '</strong><br>' .
                //         $record->email . '<br>' .
                //         $record->phone_number
                //     )
                //     ->html()
                //     ->searchable()
                //     ->sortable(),
                TextColumn::make('name')
                ->label('Nama')
                ->sortable()
                ->searchable(),
                 TextColumn::make('email')
                ->label('Email')
                ->sortable()
                ->searchable(),
                TextColumn::make('ptj.nama_ptj')
                    ->label('Ptj')
                    ->searchable()
                    ->sortable(),
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
