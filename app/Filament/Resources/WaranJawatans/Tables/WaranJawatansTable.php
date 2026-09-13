<?php

namespace App\Filament\Resources\WaranJawatans\Tables;

use App\Filament\Resources\WaranJawatans\WaranJawatanResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WaranJawatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->recordUrl(null)
            ->defaultSort(fn (Builder $query) => $query
                ->leftJoin('ptjs', 'waran_jawatans.ptj_id', '=', 'ptjs.id')
                ->orderBy('ptjs.nama_ptj')
                ->orderByRaw('pegawai_id IS NULL')
                ->select('waran_jawatans.*'))
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowindex()
                    ->width(1),

                TextColumn::make('pegawai_id')
                    ->label('Pegawai')
                    ->getStateUsing(function ($record) {
                        if (! $record->pegawai) {
                            return '<span class="italic text-gray-500">Tiada penyandang</span>';
                        }

                        return '<strong>'.e($record->pegawai->nama).'</strong><br>
                        <span class="text-xs text-gray-500 dark:text-gray-400">'.e($record->pegawai->nokp).'</span>';
                    })
                    ->html()
                    ->wrap()
                    ->sortable()
                    // ->searchable(),
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('pegawai', function ($q) use ($search) {
                            $q->where('nama', 'like', "%{$search}%")
                                ->orwhere('nokp', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('waran.no_waran')
                    ->label('No Waran / Butiran')
                    ->formatStateUsing(
                        fn ($record) => '<strong>'.e($record->waran?->no_waran).'</strong><br>'.e($record->butiran)
                    )
                    ->html()
                    ->wrap()
                    ->sortable()
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('butiran', 'like', "%{$search}%")
                                ->orWhereHas('waran', fn ($w) => $w->where('no_waran', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('aktiviti')
                    ->label('PTJ / Aktiviti / Jawatan')
                    ->getStateUsing(function ($record): string {
                        $muted = 'text-xs text-gray-500 dark:text-gray-400';

                        $ptj = e($record->ptj?->nama_ptj ?? '-');

                        $aktiviti = trim(
                            ($record->aktiviti?->no_aktivit ?? '').' - '.($record->aktiviti?->nama_aktiviti ?? ''),
                            ' -'
                        );
                        $aktiviti = $aktiviti !== '' ? e($aktiviti) : '-';

                        $jawatanParts = [];
                        if (filled($record->jawatan_list)) {
                            $jawatanParts[] = $record->jawatan_list;
                        }
                        if (filled($record->gred_list)) {
                            $jawatanParts[] = 'GRED '.$record->gred_list;
                        }

                        $jawatan = e($jawatanParts !== [] ? implode(' , ', $jawatanParts) : '-');

                        if (filled($record->butiran)) {
                            $jawatan .= ' ('.e($record->butiran).')';
                        }

                        return '<div class="font-medium">'.$ptj.'</div>'
                            .'<div class="'.$muted.'">'.$aktiviti.'</div>'
                            .'<div class="'.$muted.'">'.$jawatan.'</div>';
                    })
                    ->html()
                    ->wrap()
                    ->sortable(
                        query: function ($query, string $direction): void {
                            $query
                                ->leftJoin('ptjs as ptj_sort', 'waran_jawatans.ptj_id', '=', 'ptj_sort.id')
                                ->orderBy('ptj_sort.nama_ptj', $direction)
                                ->select('waran_jawatans.*');
                        }
                    )
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($q) use ($search): void {
                            $q->whereHas('ptj', fn ($ptj) => $ptj->where('nama_ptj', 'like', "%{$search}%"))
                                ->orWhereHas('aktiviti', function ($aktiviti) use ($search): void {
                                    $aktiviti->where('no_aktivit', 'like', "%{$search}%")
                                        ->orWhere('nama_aktiviti', 'like', "%{$search}%");
                                })
                                ->orWhere('butiran', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->size('lg')
                    ->sortable()
                    // ->searchable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'removed' => 'Dibuang',
                        'pindaan nama' => 'Pindaan Nama',
                        'batal nama' => 'Batal Nama',
                        default => 'Aktif',
                    })
                    ->color(
                        fn ($state) => match ($state) {
                            'removed' => 'danger',
                            'pindaan nama' => 'info',
                            'batal nama' => 'primary',
                            default => 'success',
                        }
                    )
                    ->searchable(
                        query: function ($query, string $search) {
                            $map = [
                                'aktif' => 'active',
                                'dibuang' => 'removed',
                                'pindaan nama' => 'pindaan nama',
                                'batal nama' => 'batal nama',
                            ];

                            $search = strtolower($search);

                            foreach ($map as $label => $value) {
                                if (str_contains($label, $search)) {
                                    $query->where('status', $value);

                                    return;
                                }
                            }
                        }
                    ),
                // TextColumn::make('status')
                // ->label('Status')

            ])
            ->filters([
                SelectFilter::make('ptj')
                    ->label('PTJ')
                    ->relationship('ptj', 'nama_ptj')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ], layout: FiltersLayout::Modal)
            ->filtersApplyAction(fn (Action $action) => $action->label('Cari'))
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Paparan')
                        ->modal()
                        ->color('info')
                        ->modalHeading(function ($record) {
                            if ($record->pegawai_id == null) {
                                return 'Tiada Penyandang';
                            } else {
                                return $record->pegawai?->nama;
                            }
                        })
                        ->extraModalFooterActions([
                            Action::make('edit')
                                ->label('Edit')
                                ->url(fn ($record) => WaranJawatanResource::getUrl('edit', [
                                    'record' => $record,
                                ])),
                        ]),
                    EditAction::make(),
                    Action::make('removePegawai')
                        ->label('Buang Pegawai')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'pegawai_id' => null,
                            ]);
                        }),
                ]),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
