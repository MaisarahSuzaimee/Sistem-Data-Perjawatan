<?php

namespace App\Filament\Resources\Ptjs\Tables;

use App\Models\Program;
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
                TextColumn::make('programs.display_name')
                    ->label('Program')
                    ->width('40%')
                    ->extraHeaderAttributes(['style' => 'width: 40%'])
                    ->getStateUsing(fn ($record) => $record->programs
                        ->sortBy('nama_program')
                        ->map(fn (Program $p) => $p->display_name)
                        ->filter()
                        ->values()
                        ->all() ?: ['-'])
                    ->badge()
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('programs', function (Builder $q) use ($search): void {
                            $q->where('nama_program', 'like', "%{$search}%")
                                ->orWhere('desc_program', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('nama_ptj')
                    ->label('PTJ / Parlimen - Dun')
                    ->width('60%')
                    ->extraHeaderAttributes(['style' => 'width: 60%'])
                    ->html()
                    ->wrap()
                    ->getStateUsing(function ($record): string {
                        $nama = e($record->nama_ptj ?? '-');
                        $parlimen = $record->parlimen?->nama_parlimen;
                        $dun = $record->dun?->nama_dun;

                        $sub = null;
                        if ($parlimen && $dun) {
                            $sub = e($parlimen).' - '.e($dun);
                        } elseif ($parlimen) {
                            $sub = e($parlimen);
                        } elseif ($dun) {
                            $sub = e($dun);
                        } else {
                            $sub = '-';
                        }

                        return '<div class="font-medium">'.$nama.'</div><div class="text-xs text-gray-500 dark:text-gray-400">'.$sub.'</div>';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('nama_ptj', 'like', "%{$search}%")
                            ->orWhereHas('parlimen', fn (Builder $q) => $q->where('nama_parlimen', 'like', "%{$search}%"))
                            ->orWhereHas('dun', fn (Builder $q) => $q->where('nama_dun', 'like', "%{$search}%"));
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('nama_ptj', $direction);
                    }),
                // TextColumn::make('parlimen.nama_parlimen')
                //     ->label('Parlimen')
                //     ->searchable()
                //     ->sortable(),
                // TextColumn::make('dun.nama_dun')
                //     ->label('Dun')
                //     ->searchable()
                //     ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('programs')
                    ->label('Program')
                    ->relationship('programs', 'nama_program')
                    ->getOptionLabelFromRecordUsing(fn (Program $record): string => $record->display_name)
                    ->searchable()
                    ->preload(),
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
}
