<?php

namespace App\Filament\Resources\Programs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('no')
                //     ->label('Bil')
                //     ->rowIndex(),
                // TextColumn::make('nama_program')
                //     ->label('Program')
                //     ->sortable()
                //     ->searchable(),
                // TextColumn::make('aktiviti')
                //     ->label('Aktiviti')
                //     ->getStateUsing(
                //         fn($record) =>
                //         $record->aktiviti
                //             ->map(fn($item) => $item->no_aktivit . ' - ' . $item->nama_aktiviti)
                //             ->toArray()
                //     )
                //     ->listWithLineBreaks()
                //     ->searchable(query: function ($query, $search) {
                //         $query->orWhereHas('aktiviti', function ($q) use ($search) {
                //             $q->where('nama_aktiviti', 'like', "%{$search}%")
                //                 ->orWhere('no_aktivit', 'like', "%{$search}%");
                //         });
                //     }),
                // TextColumn::make('butiran')
                //     ->label('Butiran')
                //     ->getStateUsing(function ($record) {
                //         return $record->aktiviti
                //             ->flatMap(function ($aktiviti) {
                //                 return $aktiviti->butiran
                //                     ->map(fn($b) => $b->butiran);
                //             })
                //             ->toArray();
                //     })
                //     ->listWithLineBreaks()

                TextColumn::make('nama_program')
                    ->label('Program'),

                TextColumn::make('aktiviti')
                    ->label('Aktiviti')
                    ->getStateUsing(
                        fn($record) =>
                        $record->aktiviti
                            ->map(fn($item) => $item->no_aktivit . ' - ' . $item->nama_aktiviti)
                            ->toArray()
                    )
                    ->listWithLineBreaks(),

                TextColumn::make('butiran')
                    ->label('Butiran')
                    ->getStateUsing(function ($record) {
                        return $record->aktiviti
                            ->flatMap(function ($aktiviti) {
                                return $aktiviti->butiran
                                    ->pluck('butiran');
                            })
                            ->toArray();
                    })
                    ->listWithLineBreaks(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('')
                    ->color('info')
                    ->tooltip('View'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->modal(),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
