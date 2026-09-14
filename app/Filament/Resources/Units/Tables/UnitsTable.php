<?php

namespace App\Filament\Resources\Units\Tables;

use App\Models\Program;
use App\Models\Ptj;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex()
                    ->width(1),
                TextColumn::make('ptj.nama_ptj')
                    ->label('Program / PTJ')
                    ->getStateUsing(function ($record): string {
                        $programs = $record->ptj?->programs
                            ?->pluck('nama_program')
                            ->filter()
                            ->implode(', ') ?: '-';

                        $muted = 'text-xs text-gray-500 dark:text-gray-400';
                        $html = '<div class="font-medium">PTJ: '.e($record->ptj?->nama_ptj ?? '-').'</div>';

                        if (filled($record->bahagian_id)) {
                            $html .= '<div class="font-medium">BAHAGIAN: '.e($record->bahagian?->nama_bahagian ?? '-').'</div>';
                        }

                        $html .= '<div class="'.$muted.'">UNIT: '.e($unit?->nama_unit ?? '-').'</div>';

                        // $html .= '<div class="text-xs text-gray-500 dark:text-gray-400">PROGRAM: '.e($programs).'</div>';

                        return $html;
                    })
                    ->html()
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('ptj', function (Builder $ptj) use ($search): void {
                                $ptj->where('nama_ptj', 'like', "%{$search}%")
                                    ->orWhereHas('programs', function (Builder $p) use ($search): void {
                                        $p->where('nama_program', 'like', "%{$search}%");
                                    });
                            })->orWhereHas('bahagian', function (Builder $b) use ($search): void {
                                $b->where('nama_bahagian', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('ptjs', 'units.ptj_id', '=', 'ptjs.id')
                            ->orderBy('ptjs.nama_ptj', $direction)
                            ->select('units.*');
                    }),
                TextColumn::make('nama_unit')
                    ->label('Jabatan / KK / KP')
                    ->getStateUsing(fn ($record): array => static::unitsForGroup($record))
                    ->wrap()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('nama_unit', 'like', "%{$search}%");
                    })
                    ->action(
                        Action::make('lihatSemuaUnit')
                            ->modalHeading('Senarai Jabatan / KK /KP')
                            ->modalDescription(function ($record): string {
                                if (filled($record->bahagian_id)) {
                                    return trim(
                                        ($record->ptj?->nama_ptj ?? '').' - '.($record->bahagian?->nama_bahagian ?? ''),
                                        ' -'
                                    );
                                }

                                return $record->ptj?->nama_ptj ?? '-';
                            })
                            ->modalContent(fn ($record): HtmlString => static::listModalContent(
                                static::unitsForGroup($record)
                            ))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->disabled(fn ($record): bool => count(static::unitsForGroup($record)) <= 3)
                    ),
            ])
            ->paginationPageOptions([5])
            ->defaultPaginationPageOption(5)
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(function (Builder $query): Builder {
                // One row per PTJ (Hierarchy 2) or Bahagian (Hierarchy 1 / JKN)
                return $query->whereIn('units.id', function ($q): void {
                    $q->selectRaw('MIN(id)')
                        ->from('units')
                        ->whereNull('deleted_at')
                        ->groupByRaw("IF(bahagian_id IS NOT NULL, CONCAT('b-', bahagian_id), CONCAT('p-', ptj_id))");
                });
            })
            ->filters([
                Filter::make('program_ptj')
                    ->label('Program / PTJ')
                    ->schema([
                        Select::make('program_id')
                            ->label('Program')
                            ->options(fn (): array => Program::query()->orderBy('nama_program')->pluck('nama_program', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('ptj_id', null)),
                        Select::make('ptj_id')
                            ->label('PTJ')
                            ->options(function (Get $get): array {
                                $programId = $get('program_id');

                                $query = Ptj::query()->orderBy('nama_ptj');

                                if (filled($programId)) {
                                    $query->whereHas('programs', fn ($q) => $q->whereKey($programId));
                                }

                                return $query->pluck('nama_ptj', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Sila pilih Program dahulu (pilihan)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['program_id'] ?? null,
                                fn (Builder $q, $programId): Builder => $q->whereHas('ptj.programs', fn (Builder $p): Builder => $p->whereKey($programId))
                            )
                            ->when(
                                $data['ptj_id'] ?? null,
                                fn (Builder $q, $ptjId): Builder => $q->where('ptj_id', $ptjId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['program_id'] ?? null)) {
                            $program = Program::find($data['program_id']);
                            $indicators[] = 'Program: '.($program?->nama_program ?? $data['program_id']);
                        }

                        if (filled($data['ptj_id'] ?? null)) {
                            $ptj = Ptj::find($data['ptj_id']);
                            $indicators[] = 'PTJ: '.($ptj?->nama_ptj ?? $data['ptj_id']);
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::Modal)
            ->filtersApplyAction(fn (Action $action) => $action->label('Cari'))
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->label('Padam')
                        ->modalHeading(function ($record): string {
                            $countQuery = Unit::query();

                            if (filled($record->bahagian_id)) {
                                $countQuery->where('bahagian_id', $record->bahagian_id);
                                $label = $record->bahagian?->nama_bahagian ?? 'Bahagian';
                            } else {
                                $countQuery->where('ptj_id', $record->ptj_id)->whereNull('bahagian_id');
                                $label = $record->ptj?->nama_ptj ?? 'PTJ';
                            }

                            $count = $countQuery->count();

                            return $count > 1 ? "Padam {$count} unit di {$label}?" : "Padam {$record->nama_unit}";
                        })
                        ->modalDescription('Adakah anda pasti mahu memadam rekod ini? Tindakan ini tidak boleh dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Padam')
                        ->modalCancelActionLabel('Batal')
                        ->action(function ($record): void {
                            if (filled($record->bahagian_id)) {
                                Unit::where('bahagian_id', $record->bahagian_id)->delete();
                            } else {
                                Unit::where('ptj_id', $record->ptj_id)->whereNull('bahagian_id')->delete();
                            }
                        }),
                ]),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if (filled($record->bahagian_id)) {
                                    Unit::where('bahagian_id', $record->bahagian_id)->delete();
                                } else {
                                    Unit::where('ptj_id', $record->ptj_id)->whereNull('bahagian_id')->delete();
                                }
                            }
                        }),
                ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function unitsForGroup($record): array
    {
        static $cache = [];

        $groupKey = filled($record->bahagian_id)
            ? 'b-'.$record->bahagian_id
            : 'p-'.$record->ptj_id;

        if (! isset($cache[$groupKey])) {
            $unitsQuery = Unit::query()->orderBy('nama_unit');

            if (filled($record->bahagian_id)) {
                $unitsQuery->where('bahagian_id', $record->bahagian_id);
            } else {
                $unitsQuery->where('ptj_id', $record->ptj_id)->whereNull('bahagian_id');
            }

            $cache[$groupKey] = $unitsQuery->pluck('nama_unit')->filter()->values()->all();
        }

        return $cache[$groupKey];
    }

    /**
     * @param  array<int, string>  $items
     */
    protected static function listModalContent(array $items): HtmlString
    {
        if ($items === []) {
            return new HtmlString('<p class="text-sm text-gray-500">Tiada rekod.</p>');
        }

        $lis = collect($items)
            ->map(fn (string $item): string => '<li>'.e($item).'</li>')
            ->implode('');

        return new HtmlString('<ul class="list-disc space-y-1 pl-5 text-sm">'.$lis.'</ul>');
    }
}
