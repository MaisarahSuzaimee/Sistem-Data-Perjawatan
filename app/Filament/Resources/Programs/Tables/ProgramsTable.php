<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Models\Program;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')

            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowindex()
                    ->width(1),
                TextColumn::make('desc_program')
                    ->label('PROGRAM')
                    ->getStateUsing(
                        fn ($record) => $record->aktiviti
                            ->map(fn ($a) => $a->program?->nama_program.' - '.$a->program?->desc_program)
                            ->filter()
                            ->unique()
                            ->join(', ')
                    )
                    ->badge()
                    ->wrap()
                    // ->sortable()
                    ->searchable(query: function ($query, $search) {
                        $query->where('nama_program', 'like', "%{$search}%")
                            ->orWhere('desc_program', 'like', "%{$search}%")
                            ->orWhereHas('aktiviti', function ($q) use ($search) {
                                $q->where('no_aktivit', 'like', "%{$search}%")
                                    ->orWhere('nama_aktiviti', 'like', "%{$search}%");
                            });
                    }),
                // ->defaultSort('nama_program'),

                TextColumn::make('aktiviti')
                    ->label('Aktiviti')
                    ->getStateUsing(
                        fn ($record) => $record->aktiviti
                            ->map(fn ($item) => $item->no_aktivit.' - '.$item->nama_aktiviti)
                            ->values()
                            ->all()
                    )
                    ->wrap()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->action(
                        Action::make('lihatSemuaAktiviti')
                            ->modalHeading('Senarai Aktiviti')
                            ->modalDescription(fn (Program $record): string => trim(
                                ($record->nama_program ?? '').' - '.($record->desc_program ?? ''),
                                ' -'
                            ))
                            ->modalContent(fn (Program $record): HtmlString => static::listModalContent(
                                $record->aktiviti
                                    ->map(fn ($item) => $item->no_aktivit.' - '.$item->nama_aktiviti)
                                    ->filter()
                                    ->values()
                                    ->all()
                            ))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->disabled(fn (Program $record): bool => $record->aktiviti->count() <= 3)
                    ),
                TextColumn::make('ptjs')
                    ->label('PTJ')
                    ->getStateUsing(
                        fn ($record) => $record->ptjs
                            ->pluck('nama_ptj')
                            ->filter()
                            ->values()
                            ->all()
                    )
                    ->wrap()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->placeholder('-')
                    ->action(
                        Action::make('lihatSemuaPtj')
                            ->modalHeading('Senarai PTJ')
                            ->modalDescription(fn (Program $record): string => trim(
                                ($record->nama_program ?? '').' - '.($record->desc_program ?? ''),
                                ' -'
                            ))
                            ->modalContent(fn (Program $record): HtmlString => static::listModalContent(
                                $record->ptjs
                                    ->pluck('nama_ptj')
                                    ->filter()
                                    ->values()
                                    ->all()
                            ))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->disabled(fn (Program $record): bool => $record->ptjs->count() <= 3)
                    ),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit')
                        ->tooltip('Edit'),
                    DeleteAction::make()
                        ->label('Padam')
                        ->modalHeading(fn ($record) => "Padam {$record->nama_program}")
                        ->modalDescription('Adakah anda pasti mahu memadam rekod ini? Tindakan ini tidak boleh dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Padam')
                        ->modalCancelActionLabel('Batal'),
                ]),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
