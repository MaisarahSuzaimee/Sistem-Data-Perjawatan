<?php

namespace App\Filament\Resources\Pegawais\Tables;

use App\Models\Pegawai;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\View\Components\BadgeComponent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PegawaisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Bil')
                    ->rowIndex(),
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->formatStateUsing(function ($record) {

                        $lantikan = match (true) {
                            $record->is_tetap == 1 => ['TETAP'],
                            $record->is_kontrak == 1 => ['KONTRAK'],
                            $record->is_kontrak_interim == 1 => ['KONTRAK INTERIM'],
                            default => ['-', 'gray'],
                        };

                        return
                            '<strong>' . ($record->nama ?? '-') . '</strong><br>' .
                            ($record->nokp ?? '-') . '<br>' .
                            ($record->jawatan_gred
                                ? $record->jawatan_gred->jawatan->desc_jawatan .
                                ' (' . $record->jawatan_gred->gred->kod_gred . ')'
                                : '-') .
                            '<br>'
                            . ($lantikan[0]);
                    })
                    ->html(),

                TextColumn::make('ptj')
                    ->label('Penempatan')
                    ->formatStateUsing(
                        fn($record) =>
                        '<strong>' . ($record->ptj?->nama_ptj ?? '') . '</strong><br>' .
                        ($record->bahagian?->nama_bahagian ?? '') . '<br>' .
                        ($record->unit?->nama_unit ?? '') . '<br>' .
                        ($record->subunit?->nama_subunit ?? '')
                    )

                    ->html(),

                // TextColumn::make('lantikan')
                //     ->label('Lantikan')
                //     ->badge()
                //     ->getStateUsing(function ($record) {

                //         return match (true) {
                //             $record->is_tetap == 1 => 'Tetap',
                //             $record->is_kontrak == 1 => 'Kontrak',
                //             $record->is_kontrak_interim == 1 => 'Kontrak Interim',
                //             default => '-',
                //         };
                //     })
                //     ->color(function ($state) {

                //         return match ($state) {
                //             'Tetap' => 'success',
                //             'Kontrak' => 'warning',
                //             'Kontrak Interim' => 'info',
                //             default => 'gray',
                //         };
                //     }),

                TextColumn::make('waran')
                    ->label('Waran')
                    ->getStateUsing(function ($record) {

                        if ($record->is_jtw == 1) {
                            return '<strong> Jawatan tanpa waran </strong>';
                        }

                        return '<strong>' . ($record->waranJawatan?->first()?->waran?->no_waran ?? '') . '</strong>';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Pegawai $record) {

                        $noWaran = $record->waranJawatan?->first()?->waran?->no_waran;

                        $tidakLengkap =
                            is_null($record->ptj_id) ||
                            is_null($record->bahagian_id) ||
                            is_null($record->subunit_id) ||
                            is_null($record->unit_id) ||

                            (
                                $record->is_jtw == 0 &&
                                is_null($noWaran)
                            );

                        return $tidakLengkap ? 'Tidak Lengkap' : 'Lengkap';
                    })
                    ->badge()
                    ->color(function (Pegawai $record) {

                        $noWaran = $record->waranJawatan?->first()?->waran?->no_waran;

                        $tidakLengkap =
                            is_null($record->ptj_id) ||
                            is_null($record->bahagian_id) ||
                            is_null($record->subunit_id) ||
                            is_null($record->unit_id) ||
                            (
                                $record->is_jtw == 0 &&
                                is_null($noWaran)
                            );

                        return $tidakLengkap ? 'danger' : 'success';
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
