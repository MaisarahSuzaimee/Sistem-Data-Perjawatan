<?php

namespace App\Filament\Resources\Aktivitis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class AktivitisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('program.nama_program')
            ->groupingSettingsHidden()

            ->columns([

                TextColumn::make('no_aktivit')
                    ->label('Aktiviti')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->no_aktivit . ' - ' . $record->nama_aktiviti
                    )
                    ->sortable()
                    ->searchable(),

                TextColumn::make('butiran')
                    ->label('Butiran')
                    ->getStateUsing(
                        fn($record) =>
                        $record->butiran
                            ->pluck('butiran')
                            ->toArray()
                    )
                    ->listWithLineBreaks()
                // ->defaultGroup('')
                ,

            ])

            ->groups([
                Group::make('program.nama_program')
                    ->label('Program')
                    ->collapsible()
            ])

            ->recordActions([
                EditAction::make()
                ->modal(),
                DeleteAction::make()
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
