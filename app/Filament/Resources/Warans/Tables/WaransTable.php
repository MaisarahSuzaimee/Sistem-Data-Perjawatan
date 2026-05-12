<?php

namespace App\Filament\Resources\Warans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WaransTable
{
    public static function configure(Table $table): Table
    {
        return $table

            // ✅ ONE correct eager load only
            ->modifyQueryUsing(
                fn($query) =>
                $query->with([
                    'waranJawatans.ptj',
                    'waranJawatans.aktiviti',
                    'children.waranJawatans',
                ])
                ->whereNull('parent_id')
            )

            ->columns([

                // Bil
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex(),

                // Waran Info
                TextColumn::make('no_waran')
                    ->label('Maklumat Waran')
                    ->formatStateUsing(
                        fn($record) =>
                        '<strong>' . $record->no_waran . '</strong><br>Jawatan: ' . $record->jik
                    )
                    ->html(),
               TextColumn::make('waran_tambahan')
    ->label('Waran Tambahan')
    ->formatStateUsing(function ($record) {

        $children = $record->children;

        if ($children->isEmpty()) {
            return '-';
        }

        return $children
            ->map(fn ($c) => $c->no_waran)
            ->join('<br>')
            . "<br><span class='text-gray-500'>(".$children->count()." waran)</span>";
    })
    ->html(),
                TextColumn::make('butiran_list')
                    ->label('Butiran')
                    ->html(),
                // Aktiviti (unique, no repeat)
                TextColumn::make('aktiviti_list')
                    ->label('Aktiviti')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->waranJawatans
                            ->map(fn($wj) => $wj->aktiviti?->no_aktivit . ' - ' . $wj->aktiviti?->nama_aktiviti)
                            ->filter()
                            ->unique()
                            ->join('<br>')
                    )
                    ->html()
                    ->wrap(),

                TextColumn::make('penempatan_list')
                    ->label('Penempatan')
                    ->html()
                    ->wrap(),

            ])

            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
