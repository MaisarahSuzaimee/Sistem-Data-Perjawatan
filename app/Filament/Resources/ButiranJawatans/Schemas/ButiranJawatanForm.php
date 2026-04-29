<?php

namespace App\Filament\Resources\ButiranJawatans\Schemas;

use App\Models\Butiran;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ButiranJawatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Select::make('butiran_id')
                //     ->label('Butiran')
                //     ->options(
                //         Butiran::pluck('butiran', 'id')
                //     )
                //     ->searchable()
                //     ->required(),

                // Select::make('jawatan_gred_id')
                //     ->label('Jawatan')
                //     ->multiple()
                //     ->relationship(
                //         'jawatanGred',
                //         'id',
                //         fn($query) => $query
                //             ->join('jawatans', 'jawatan__greds.jawatan_id', '=', 'jawatans.id')
                //             ->join('greds', 'jawatan__greds.gred_id', '=', 'greds.id')
                //             ->select('jawatan__greds.*')
                //     )
                //     ->getOptionLabelFromRecordUsing(
                //         fn($record) =>
                //         $record->jawatan->desc_jawatan . ' (' . $record->gred->kod_gred . ')'
                //     )
                //     ->searchable([
                //         'jawatans.desc_jawatan',
                //         'greds.kod_gred'
                //     ])
                //     ->preload()
                Select::make('butiran_id')
                    ->label('Butiran')
                    ->relationship('butiran', 'butiran')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('jawatan_gred_id')
                    ->label('Jawatan')
                    ->multiple()
                    ->relationship('jawatanGred', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn($record) =>
                        $record->jawatan->desc_jawatan . ' (' . $record->gred->kod_gred . ')'
                    )
                    ->searchable()
                    ->preload()
            ]);
    }
}
