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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;

class WaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema

            ->components([
                Section::make('Maklumat Waran')
                    ->schema([
                        Select::make('waran_id')
                            ->label('No Waran')
                            ->visibleOn('create')
                            ->searchable()
                            ->preload()
                            ->options(
                                fn() =>
                                Waran::orderBy('no_waran')
                                    ->pluck('no_waran', 'id')
                            )
                            ->getSearchResultsUsing(
                                fn(string $search) =>
                                Waran::where('no_waran', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->pluck('no_waran', 'id')
                            )
                            ->createOptionForm([
                                TextInput::make('no_waran')->required(),
                                TextInput::make('jik')
                                    ->label('Jumlah Jawatan')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->createOptionUsing(
                                fn(array $data) =>
                                Waran::create($data)->id
                            )
                            ->live()
                            ->afterStateUpdated(function ($state) {

                                if (!$state)
                                    return;

                                return redirect()->to(
                                    WaranResource::getUrl('edit', [
                                        'record' => $state
                                    ])
                                );
                            }),

                        TextInput::make('no_waran')
                            ->label('No Waran')
                            ->visibleOn('edit')
                            ->disabled(),

                        // TextInput::make('jik')
                        //     ->label('Jumlah Jawatan')
                        //     ->disabled(),
                        TextInput::make('jik')
    ->label('Jumlah Jawatan'),
    // ->disabled()
    // ->dehydrated(false)
    // ->afterStateHydrated(function (Set $set, $record) {

    //     $parentJik = $record?->jik ?? 0;

    //     $childrenJik = $record?->children?->sum('jik') ?? 0;

    //     $set('jik', $parentJik + $childrenJik);
    // }),
Repeater::make('children')
    ->relationship('children')
    ->label('Waran Baru')
    ->schema([
        TextInput::make('no_waran')
            ->disabled()
            ->dehydrated(false),

        TextInput::make('jik')
            ->disabled()
            ->dehydrated(false),
    ])
    ->addable(false)
    ->deletable(false)
    ->columns(2)
    ->columnSpanFull(),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull()
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Maklumat Jawatan')
                    ->schema([
                        Repeater::make('waranJawatan')
                            ->relationship()
                            ->addable(false)
                            ->deletable(false)
                            ->columns(2)
                            ->label('Butiran Jawatan')
                            ->schema([
                                Select::make('ptj_id')
                                    ->relationship('ptj', 'nama_ptj')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),

                                Select::make('aktiviti_id')
                                    ->relationship('aktiviti', 'nama_aktiviti')
                                    ->required()
                                    ->options(function () {
                                        return Program::with('aktiviti')
                                            ->orderBy('nama_program')
                                            ->get()
                                            ->mapWithKeys(function ($program) {
                                                return [
                                                    $program->nama_program => $program->aktiviti
                                                        ->mapWithKeys(function ($aktiviti) {
                                                            return [
                                                                $aktiviti->id => $aktiviti->no_aktivit . ' - ' . $aktiviti->nama_aktiviti
                                                            ];
                                                        })
                                                        ->toArray(),
                                                ];
                                            })
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('butiran')
                                    ->required(),



                                Select::make('jawatan_id')
                                    ->label('Jawatan')
                                    ->options(
                                        Jawatan::query()
                                            ->orderBy('desc_jawatan')
                                            ->pluck('desc_jawatan', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->reactive()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($state, Get $get, Set $set) {

                                        $jawatanGredId = $get('jawatan_gred_id');

                                        if (!$jawatanGredId) {
                                            return;
                                        }

                                        $jawatanGred = Jawatan_Gred::find($jawatanGredId);

                                        if (!$jawatanGred) {
                                            return;
                                        }

                                        $set('jawatan_id', $jawatanGred->jawatan_id);
                                    }),

                                Select::make('gred_id')
                                    ->label('Gred')
                                    ->options(function (Get $get) {

                                        $jawatanId = $get('jawatan_id');

                                        if (blank($jawatanId)) {
                                            return [];
                                        }

                                        return Jawatan_Gred::query()
                                            ->where('jawatan_id', $jawatanId)
                                            ->join('greds', 'jawatan__greds.gred_id', '=', 'greds.id')
                                            ->pluck('greds.kod_gred', 'greds.id')
                                            ->toArray();
                                    })
                                    ->live()
                                    ->searchable()
                                    ->preload()
                                    ->dehydrated(false)
                                    ->disabled(fn(Get $get) => blank($get('jawatan_id')))
                                    ->afterStateHydrated(function ($state, Get $get, Set $set) {

                                        $jawatanGredId = $get('jawatan_gred_id');

                                        if (!$jawatanGredId) {
                                            return;
                                        }

                                        $jawatanGred = Jawatan_Gred::find($jawatanGredId);

                                        if (!$jawatanGred) {
                                            return;
                                        }

                                        $set('gred_id', $jawatanGred->gred_id);
                                    })
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {

                                        if (blank($state)) {
                                            return;
                                        }

                                        $jawatanGred = Jawatan_Gred::query()
                                            ->where('jawatan_id', $get('jawatan_id'))
                                            ->where('gred_id', $state)
                                            ->first();

                                        $set('jawatan_gred_id', $jawatanGred?->id);

                                        $set('pegawai_id', null);
                                    }),
                                Hidden::make('jawatan_gred_id'),

                                Select::make('pegawai_id')
                                    ->label('Pegawai')
                                    ->options(function (Get $get) {

                                        $jawatanGredId = $get('jawatan_gred_id');

                                        if (blank($jawatanGredId)) {
                                            return [];
                                        }

                                        return Pegawai::query()
                                            ->where('jawatan_gred_id', $jawatanGredId)
                                            ->orderBy('nama')
                                            ->pluck('nama', 'id')
                                            ->toArray();
                                    })

                                    // 🔥 GLOBAL + LOCAL VALIDATION
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, $fail) use ($get) {

                                                if (blank($value))
                                                    return;

                                                // 1. CHECK DB (global uniqueness)
                                                $existsInDb = \App\Models\WaranJawatan::query()
                                                    ->where('pegawai_id', $value)
                                                    ->exists();

                                                $currentId = $get('id');

                                                if ($existsInDb && !$currentId) {
                                                    $fail('Pegawai ini sudah mempunyai waran.');
                                                    return;
                                                }

                                                // 2. CHECK REPEATER (same form)
                                                $rows = $get('../../waranJawatan') ?? [];

                                                $count = collect($rows)
                                                    ->pluck('pegawai_id')
                                                    ->filter(fn($id) => $id == $value)
                                                    ->count();

                                                if ($count > 1) {
                                                    $fail('Pegawai ini telah dipilih dalam baris lain.');
                                                }
                                            };
                                        },
                                    ])

                                    ->disableOptionWhen(function ($value, Get $get) {

                                        $current = $get('pegawai_id');

                                        // allow current value (edit mode safety)
                                        if ((int) $current === (int) $value) {
                                            return false;
                                        }

                                        // 1. check repeater duplicates
                                        $rows = $get('../../waranJawatan') ?? [];

                                        $selected = collect($rows)
                                            ->pluck('pegawai_id')
                                            ->filter()
                                            ->toArray();

                                        if (in_array($value, $selected)) {
                                            return true;
                                        }

                                        // 2. check DB global uniqueness
                                        return \App\Models\WaranJawatan::query()
                                            ->where('pegawai_id', $value)
                                            ->exists();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->disabled(fn(Get $get) => blank($get('jawatan_gred_id')))
                                    ->columnSpanFull(),
                                Textarea::make('catatan_jawatan')
                                    ->label('Catatan')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columns(2)
                            ->itemLabel(function (array $state) {
                                if (!$state['ptj_id']) {
                                    return 'Tambah Jawatan';
                                }

                                $ptj = Ptj::find($state['ptj_id']);

                                return $ptj?->nama_ptj ?? 'Unknown PTJ';
                            })->collapsed(),

                    ])
                    ->columnSpanFull(),


            ]);
    }
}
