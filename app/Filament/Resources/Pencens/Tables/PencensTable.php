<?php

namespace App\Filament\Resources\Pencens\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable(),
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
                TextColumn::make('ptj.nama_ptj')
                    ->label('PTJ')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tarikh')
                    ->label('Tarikh Pencen / Kuatkuasa')
                    ->getStateUsing(fn($record) => $record->tarikh_kuatkuasa ?? $record->tarikh_pencen)
                    ->date('d F Y')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("
                                                    COALESCE(tarikh_kuatkuasa, tarikh_pencen) {$direction}
                                                ");
                    })
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function ($query) use ($search) {
                                $query->where('tarikh_kuatkuasa', 'like', "%{$search}%")
                                    ->orWhere('tarikh_pencen', 'like', "%{$search}%");
                            });
                        }
                    ),

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
