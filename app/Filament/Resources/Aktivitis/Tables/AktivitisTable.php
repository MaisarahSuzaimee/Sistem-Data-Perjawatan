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
            ->columns([

                TextColumn::make('no_aktivit')
                    ->label('No. Aktiviti')
                    ->sortable()
                    ->searchable()
                    ->width('150px'),

                TextColumn::make('nama_aktiviti')
                    ->label('Nama Aktiviti')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

            ])

            ->groups([
                Group::make('program.nama_program')
                    ->label('Program')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->orderQueryUsing(fn ($query, $direction) =>
                        $query->join('programs', 'aktivitis.program_id', '=', 'programs.id')
                              ->orderBy('programs.id', $direction)
                    ),
            ])

            ->defaultGroup('program.nama_program')
            ->groupingSettingsHidden()
            ->defaultPaginationPageOption(100)
            ->defaultSort('no_aktivit', 'asc')

            ->recordActions([
                EditAction::make()->modal(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}