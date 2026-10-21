<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Models\Bahagian;
use App\Models\Dun;
use App\Models\Parlimen;
use App\Models\Ptj;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Jabatan / KK / KP')
                    ->schema([
                        Select::make('ptj_id')
                            ->label('PTJ')
                            ->required()
                            ->options(
                                Ptj::query()
                                    ->orderBy('nama_ptj')
                                    ->pluck('nama_ptj', 'id')
                            )
                            ->live()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function (Get $get, Set $set, $livewire): void {
                                $set('bahagian_id', null);

                                $ptjId = $get('ptj_id');
                                $set('programs_for_ptj', static::programsLabelForPtj(
                                    filled($ptjId) ? (int) $ptjId : null
                                ));

                                static::clearRepeaterAktiviti($get, $set);

                                if (
                                    filled($ptjId)
                                    && ! Ptj::usesBahagianHierarchyFor((int) $ptjId)
                                    && method_exists($livewire, 'redirectToEditIfParentHasUnits')
                                ) {
                                    $livewire->redirectToEditIfParentHasUnits(
                                        ptjId: (int) $ptjId,
                                        bahagianId: null,
                                    );
                                }
                            })
                            ->helperText('Jika PTJ/Bahagian sudah ada unit, anda akan diarah ke halaman kemaskini.')
                            ->visible(fn ($record) => $record === null)
                            ->columnSpanFull(),

                        // TextInput::make('programs_for_ptj')
                        //     ->label('Program')
                        //     ->readOnly()
                        //     ->dehydrated(false)
                        //     ->placeholder('Sila pilih PTJ dahulu')
                        //     ->helperText(fn (Get $get): ?string => blank($get('ptj_id'))
                        //         ? 'Semua program yang diassign kepada PTJ dipaparkan di sini'
                        //         : null)
                        //     ->visible(fn ($record) => $record === null)
                        //     ->columnSpanFull(),

                        Select::make('bahagian_id')
                            ->label('Bahagian')
                            ->options(function (Get $get): array {
                                $ptjId = $get('ptj_id');

                                if (! $ptjId || ! Ptj::usesBahagianHierarchyFor((int) $ptjId)) {
                                    return [];
                                }

                                return Bahagian::query()
                                    ->where('ptj_id', $ptjId)
                                    ->orderBy('nama_bahagian')
                                    ->pluck('nama_bahagian', 'id')
                                    ->toArray();
                            })
                            ->required(fn (Get $get): bool => Ptj::usesBahagianHierarchyFor(
                                filled($get('ptj_id')) ? (int) $get('ptj_id') : null
                            ))
                            ->visible(fn (Get $get, $record): bool => $record === null && Ptj::usesBahagianHierarchyFor(
                                filled($get('ptj_id')) ? (int) $get('ptj_id') : null
                            ))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Get $get, $livewire): void {
                                if (
                                    filled($state)
                                    && method_exists($livewire, 'redirectToEditIfParentHasUnits')
                                ) {
                                    $livewire->redirectToEditIfParentHasUnits(
                                        ptjId: filled($get('ptj_id')) ? (int) $get('ptj_id') : null,
                                        bahagianId: (int) $state,
                                    );
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('ptj_display')
                            ->label('PTJ')
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state(
                                    $record?->ptj?->nama_ptj
                                );
                            })
                            ->readOnly()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),

                        // TextInput::make('program_display')
                        //     ->label('Program')
                        //     ->afterStateHydrated(function ($component, $state, $record): void {
                        //         $component->state(
                        //             $record?->ptj?->programs?->pluck('nama_program')->filter()->implode(', ')
                        //         );
                        //     })
                        //     ->readOnly()
                        //     ->dehydrated(false)
                        //     ->visible(fn ($record) => $record !== null)
                        //     ->columnSpanFull(),

                        TextInput::make('bahagian_display')
                            ->label('Bahagian')
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state($record?->bahagian?->nama_bahagian);
                            })
                            ->readOnly()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null && filled($record?->bahagian_id))
                            ->columnSpanFull(),

                        Repeater::make('units')
                            ->label('Senarai Jabatan / KK / KP')
                            ->columnSpanFull()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Jabatan / KK / KP')
                            ->addAction(fn (Action $action) => $action
                                ->color('info')
                                ->icon('heroicon-m-plus'))
                            ->columns(2)
                            ->afterStateHydrated(function ($component, ?array $state, $record): void {
                                if ($record && blank($state)) {
                                    $unitsQuery = Unit::query()->orderBy('nama_unit');

                                    if (filled($record->bahagian_id)) {
                                        $unitsQuery->where('bahagian_id', $record->bahagian_id);
                                    } else {
                                        $unitsQuery->where('ptj_id', $record->ptj_id)->whereNull('bahagian_id');
                                    }

                                    $units = $unitsQuery
                                        ->with('aktivitis')
                                        ->get()
                                        ->map(fn (Unit $u): array => [
                                            'id' => $u->id,
                                            'nama_unit' => $u->nama_unit,
                                            'aktiviti_ids' => $u->aktivitis->pluck('id')->all(),
                                            'parlimen_id' => $u->parlimen_id,
                                            'dun_id' => $u->dun_id,
                                        ])
                                        ->toArray();

                                    $component->state($units);
                                }
                            })
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('nama_unit')
                                    ->label('Nama Jabatan / KK / KP')
                                    ->required()
                                    ->distinct()
                                    ->unique(
                                        table: 'units',
                                        column: 'nama_unit',
                                        ignorable: fn (Get $get) => filled($get('id')) ? Unit::find($get('id')) : null,
                                        modifyRuleUsing: function (Unique $rule, Get $get, Component $component): Unique {
                                            $ptjId = $get('../../ptj_id');
                                            $bahagianId = $get('../../bahagian_id');

                                            if (blank($ptjId)) {
                                                $record = $component->getRecord();

                                                if ($record) {
                                                    $ptjId = $record->ptj_id;
                                                    $bahagianId = $record->bahagian_id;
                                                }
                                            }

                                            if (blank($ptjId)) {
                                                $id = $get('id');
                                                if (filled($id)) {
                                                    $unit = Unit::find($id);
                                                    $ptjId = $unit?->ptj_id;
                                                    $bahagianId = $unit?->bahagian_id;
                                                }
                                            }

                                            if (filled($bahagianId)) {
                                                $rule->where('bahagian_id', $bahagianId);
                                            } elseif (filled($ptjId)) {
                                                $rule->where('ptj_id', $ptjId)->whereNull('bahagian_id');
                                            }

                                            $rule->whereNull('deleted_at');

                                            return $rule;
                                        }
                                    )
                                    ->validationMessages([
                                        'distinct' => 'Nama Jabatan / KK / KP tidak boleh duplikat dalam senarai ini.',
                                        'unique' => 'Nama Jabatan / KK / KP telah wujud untuk PTJ/Bahagian ini.',
                                    ])
                                    ->dehydrateStateUsing(fn (?string $state): string => $state ? strtoupper($state) : '')
                                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                                    ->columnSpanFull(),
                                Select::make('aktiviti_ids')
                                    ->label('Aktiviti')
                                    ->multiple()
                                    ->options(fn (Get $get, $record): array => static::aktivitiOptions($get, $record))
                                    ->searchable()
                                    ->preload()
                                    ->helperText(function (Get $get, $record): ?string {
                                        if ($record !== null) {
                                            return null;
                                        }

                                        return blank($get('../../ptj_id'))
                                            ? 'Sila pilih PTJ dahulu'
                                            : null;
                                    })
                                    ->columnSpanFull(),
                                Select::make('parlimen_id')
                                    ->label('Parlimen')
                                    ->options(fn (): array => Parlimen::query()->orderBy('nama_parlimen')->pluck('nama_parlimen', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('dun_id', null)),
                                Select::make('dun_id')
                                    ->label('DUN')
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Get $get): array {
                                        $parlimenId = $get('parlimen_id');
                                        if (blank($parlimenId)) {
                                            return [];
                                        }

                                        return Dun::where('parlimen_id', $parlimenId)
                                            ->pluck('nama_dun', 'id')
                                            ->toArray();
                                    })
                                    ->disabled(fn (Get $get): bool => blank($get('parlimen_id')))
                                    ->helperText('Sila pilih Parlimen dahulu'),
                            ])
                            ->itemLabel(fn (array $state): ?string => filled($state['nama_unit'] ?? null) ? strtoupper($state['nama_unit']) : 'Jabatan / KK / KP baharu')
                            ->collapsed()
                            ->collapsible()
                            ->deleteAction(function (Action $action): Action {
                                return $action
                                    ->requiresConfirmation()
                                    ->modalHeading(function (array $arguments, Repeater $component): string {
                                        $items = $component->getRawState();
                                        $item = $items[$arguments['item']] ?? [];
                                        $nama = trim((string) ($item['nama_unit'] ?? ''));

                                        return $nama !== '' ? "Padam {$nama}?" : 'Padam Jabatan / KK / KP ini?';
                                    })
                                    ->modalDescription('Adakah anda pasti mahu memadam Jabatan / KK / KPini? Tindakan ini tidak boleh dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Padam')
                                    ->modalCancelActionLabel('Batal');
                            }),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }

    protected static function clearRepeaterAktiviti(Get $get, Set $set): void
    {
        $units = $get('units') ?? [];

        if (! is_array($units)) {
            return;
        }

        foreach ($units as $key => $unit) {
            if (is_array($unit)) {
                $units[$key]['aktiviti_ids'] = [];
            }
        }

        $set('units', $units);
    }

    protected static function programsLabelForPtj(?int $ptjId): string
    {
        if ($ptjId === null) {
            return '';
        }

        return Ptj::query()
            ->whereKey($ptjId)
            ->first()
            ?->programs()
            ->orderBy('nama_program')
            ->pluck('nama_program')
            ->filter()
            ->implode(', ') ?: '-';
    }

    /**
     * @return array<int|string, string>
     */
    protected static function aktivitiOptions(Get $get, $record): array
    {
        $ptjId = $get('../../ptj_id');

        if (blank($ptjId) && $record) {
            $ptjId = $record->ptj_id;
        }

        if (blank($ptjId) && filled($get('id'))) {
            $ptjId = Unit::find($get('id'))?->ptj_id;
        }

        if (blank($ptjId)) {
            return [];
        }

        return Ptj::query()->find($ptjId)?->aktivitiSelectOptions() ?? [];
    }
}
