<?php

namespace App\Filament\Resources\Ptjs\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PtjsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->width(1),
                TextColumn::make('nama_ptj')
                    ->label('PTJ')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('parlimen_dun')
                    ->label('Parlimen - Dun')
                    ->getStateUsing(fn ($record): string => static::locationLabel($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('parlimen', fn (Builder $q) => $q->where('nama_parlimen', 'like', "%{$search}%"))
                            ->orWhereHas('dun', fn (Builder $q) => $q->where('nama_dun', 'like', "%{$search}%"));
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('parlimen_id')
                    ->label('Parlimen')
                    ->relationship('parlimen', 'nama_parlimen')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('dun_id')
                    ->label('Dun')
                    ->relationship('dun', 'nama_dun')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::Modal)
            ->filtersApplyAction(fn (Action $action) => $action->label('Cari'))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function locationLabel($record): string
    {
        $parlimen = $record->parlimen?->nama_parlimen;
        $dun = $record->dun?->nama_dun;

        if ($parlimen && $dun) {
            return $parlimen.' - '.$dun;
        }

        return $parlimen ?: ($dun ?: '-');
    }
}
