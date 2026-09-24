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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
                TextColumn::make('nama_program')
                    ->label(new HtmlString(static::matrixHeaderHtml()))
                    ->formatStateUsing(fn (Program $record): HtmlString => new HtmlString(static::matrixHtml($record)))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('nama_program', 'like', "%{$search}%")
                                ->orWhere('desc_program', 'like', "%{$search}%")
                                ->orWhereHas('aktiviti', function (Builder $q) use ($search): void {
                                    $q->where('no_aktivit', 'like', "%{$search}%")
                                        ->orWhere('nama_aktiviti', 'like', "%{$search}%");
                                })
                                ->orWhereHas('ptjs', function (Builder $q) use ($search): void {
                                    $q->where('nama_ptj', 'like', "%{$search}%");
                                })
                                ->orWhereHas('aktiviti.ptjs', function (Builder $q) use ($search): void {
                                    $q->where('nama_ptj', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->extraHeaderAttributes(['class' => 'fi-program-matrix-header'])
                    ->extraCellAttributes(['class' => 'fi-program-matrix-cell'])
                    ->action(
                        Action::make('lihatSemuaPtj')
                            ->modalHeading('Senarai PTJ')
                            ->modalDescription(fn (Program $record): string => trim(
                                ($record->nama_program ?? '').' - '.($record->desc_program ?? ''),
                                ' -'
                            ))
                            ->modalContent(fn (Program $record): HtmlString => static::ptjModalContent($record))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->disabled(fn (Program $record): bool => ! static::hasExtraPtj($record))
                    ),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Kemaskini')
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

    public static function matrixHeaderHtml(): string
    {
        return '<div class="fi-program-matrix fi-program-matrix-head">'
            .'<div class="fi-program-matrix-program">Program</div>'
            .'<div class="fi-program-matrix-aktiviti">Aktiviti</div>'
            .'<div class="fi-program-matrix-ptj">PTJ</div>'
            .'</div>';
    }

    public static function matrixHtml(Program $record): string
    {
        $aktiviti = $record->aktiviti->sortBy('no_aktivit')->values();
        $span = max($aktiviti->count(), 1);

        $programCell = '<div class="fi-program-matrix-program" style="grid-row: span '.$span.'">'
            .'<div class="font-medium">'.e($record->nama_program).'</div>';

        if (filled($record->desc_program)) {
            $programCell .= '<div class="text-xs text-gray-500 dark:text-gray-400">'.e($record->desc_program).'</div>';
        }

        $programCell .= '</div>';

        $cells = $programCell;

        if ($aktiviti->isEmpty()) {
            $cells .= '<div class="fi-program-matrix-aktiviti">-</div>'
                .'<div class="fi-program-matrix-ptj">'.static::ptjListHtml(static::unassignedPtjs($record)->pluck('nama_ptj')).'</div>';
        } else {
            foreach ($aktiviti as $item) {
                $label = trim(($item->no_aktivit ?? '').' - '.($item->nama_aktiviti ?? ''), ' -');

                $cells .= '<div class="fi-program-matrix-aktiviti">'.e($label !== '' ? $label : '-').'</div>'
                    .'<div class="fi-program-matrix-ptj">'.static::ptjListHtml($item->ptjs->pluck('nama_ptj')).'</div>';
            }
        }

        return '<div class="fi-program-matrix">'.$cells.'</div>';
    }

    protected static function unassignedPtjs(Program $record): Collection
    {
        return $record->ptjs->filter(
            fn ($ptj): bool => $ptj->aktivitis->isEmpty()
        );
    }

    protected static function hasExtraPtj(Program $record): bool
    {
        $aktiviti = $record->aktiviti;

        if ($aktiviti->isEmpty()) {
            return static::ptjNames(static::unassignedPtjs($record)->pluck('nama_ptj'))->count() > 3;
        }

        return $aktiviti->contains(
            fn ($item): bool => static::ptjNames($item->ptjs->pluck('nama_ptj'))->count() > 3
        );
    }

    protected static function ptjModalContent(Program $record): HtmlString
    {
        $aktiviti = $record->aktiviti->sortBy('no_aktivit')->values();
        $blocks = '';

        if ($aktiviti->isEmpty()) {
            return static::listModalContent(static::ptjNames(static::unassignedPtjs($record)->pluck('nama_ptj'))->all());
        }

        foreach ($aktiviti as $item) {
            $label = trim(($item->no_aktivit ?? '').' - '.($item->nama_aktiviti ?? ''), ' -');
            $names = static::ptjNames($item->ptjs->pluck('nama_ptj'));
            $lis = $names->isEmpty()
                ? '<li class="text-gray-500">-</li>'
                : $names->map(fn (string $name): string => '<li>'.e($name).'</li>')->implode('');

            $blocks .= '<p class="mt-3 text-sm font-medium">'.e($label !== '' ? $label : '-').'</p>'
                .'<ul class="list-disc space-y-1 pl-5 text-sm">'.$lis.'</ul>';
        }

        return new HtmlString($blocks);
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

    /**
     * @param  iterable<int, mixed>  $names
     * @return Collection<int, string>
     */
    protected static function ptjNames(iterable $names): Collection
    {
        return collect($names)
            ->filter(fn ($name): bool => filled($name))
            ->map(fn ($name): string => (string) $name)
            ->sort()
            ->values();
    }

    /**
     * @param  iterable<int, mixed>  $names
     */
    protected static function ptjListHtml(iterable $names): string
    {
        $items = static::ptjNames($names);

        if ($items->isEmpty()) {
            return '-';
        }

        $visible = $items->take(3);
        $html = $visible->map(fn (string $name): string => e($name))->implode('<br>');
        $extra = $items->count() - $visible->count();

        if ($extra > 0) {
            $html .= '<div class="fi-program-matrix-more">'.e(__('filament-tables::table.columns.text.more_list_items', ['count' => $extra])).'</div>';
        }

        return $html;
    }
}
