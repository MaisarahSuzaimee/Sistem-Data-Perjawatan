<?php

namespace App\Filament\Resources\Warans\Schemas;

use App\Filament\Resources\Warans\WaranResource;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\Pegawai;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\Waran;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;

class WaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->components([
                // Section::make('Maklumat Waran')
                //     ->schema([

                View::make('filament.custom.senarai-waran-table')
                    // ->viewData(fn ($record) => [
                    //     'warans' => Waran::where('parent_id', $record->id)->get(),
                    // ]),
                    // View::make('filament.custom.senarai-waran-table')
                    ->viewData(fn($record) => [
                        'warans' => Waran::with([
                            'waranJawatans.aktiviti',
                            'waranJawatans.ptj',
                            'waranJawatans.pegawai',
                            'children.waranJawatans.aktiviti',
                            'children.waranJawatans.ptj',
                            'children.waranJawatans.pegawai',
                        ])
                            ->whereNull('parent_id')
                            ->get()
                    ])
                    ->columnSpanFull(),
                // ])
                // ->columnSpanFull(),

                Hidden::make('selected_waran_id')
                    ->live(),

                Section::make('Edit Waran')
                    ->visible(fn(Get $get) => filled($get('selected_waran_id')))
                    ->columnSpanFull()
                    ->columns(2)
                    ->live()
                    ->schema([
                        TextEntry::make('selected_waran')
                            ->label('No Waran')
                            ->state(function (Get $get) {

                                $selectedId = $get('selected_waran_id');

                                if (!$selectedId) {
                                    return '-';
                                }

                                return Waran::find($selectedId)?->no_waran;
                            })
                            ->extraAttributes([
                                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm text-gray-900',
                            ]),
                        TextEntry::make('jik')
                            ->label('JIK')
                            ->state(function (Get $get) {

                                $selectedId = $get('selected_waran_id');

                                if (!$selectedId) {
                                    return '-';
                                }

                                return Waran::find($selectedId)?->jik;
                            })
                            ->extraAttributes([
                                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm text-gray-900',
                            ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Get $get) {

                                $id = $get('selected_waran_id');

                                if (!$id)
                                    return;

                                Waran::where('id', $id)->update([
                                    'catatan' => $state,
                                ]);
                            })
                            ->columnSpanFull(),

                        Repeater::make('waranJawatans')
                        ->label('Butiran Jawatan')
->relationship('waranJawatans')                        ->columnSpanFull()
                        ->schema([
                            Select::make('ptj_id')
                                    ->relationship('ptj', 'nama_ptj')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                        ])
                    ])
            ]);


    }
}
