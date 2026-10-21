<?php

namespace App\Filament\Resources\Subunits\Schemas;

use App\Models\Bahagian;
use App\Models\Dun;
use App\Models\Parlimen;
use App\Models\Ptj;
use App\Models\Subunit;
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

class SubunitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat KD / KKIA / Wad / Klinik')
                    ->schema([
                        Select::make('ptj_id')
                            ->label('PTJ')
                            ->required()
                            ->options(
                                Ptj::query()
                                    ->orderBy('nama_ptj')
                                    ->pluck('nama_ptj', 'id')
                            )
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state(
                                    $record?->unit?->ptj_id
                                );
                            })
                            ->live()
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $set('bahagian_id', null);
                                $set('unit_id', null);

                                $ptjId = $get('ptj_id');
                                $set('programs_for_ptj', static::programsLabelForPtj(
                                    filled($ptjId) ? (int) $ptjId : null
                                ));

                                static::clearRepeaterAktiviti($get, $set);
                            })
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
                            ->live()
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->required(fn (Get $get): bool => Ptj::usesBahagianHierarchyFor(
                                filled($get('ptj_id')) ? (int) $get('ptj_id') : null
                            ))
                            ->visible(fn (Get $get, $record): bool => $record === null && Ptj::usesBahagianHierarchyFor(
                                filled($get('ptj_id')) ? (int) $get('ptj_id') : null
                            ))
                            ->afterStateUpdated(fn (Set $set) => $set('unit_id', null))
                            ->columnSpanFull(),

                        TextInput::make('ptj_display')
                            ->label('PTJ')
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state(
                                    $record?->unit?->ptj?->nama_ptj
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
                        //             $record?->unit?->ptj?->programs?->pluck('nama_program')->filter()->implode(', ')
                        //         );
                        //     })
                        //     ->readOnly()
                        //     ->dehydrated(false)
                        //     ->visible(fn ($record) => $record !== null)
                        //     ->columnSpanFull(),

                        TextInput::make('bahagian_display')
                            ->label('Bahagian')
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state($record?->unit?->bahagian?->nama_bahagian);
                            })
                            ->readOnly()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null && filled($record?->unit?->bahagian_id))
                            ->columnSpanFull(),

                        Select::make('unit_id')
                            ->label('Unit')
                            ->required()
                            ->options(function (Get $get): array {
                                $ptjId = $get('ptj_id');

                                if (! $ptjId) {
                                    return [];
                                }

                                $query = Unit::query()->orderBy('nama_unit');

                                if (Ptj::usesBahagianHierarchyFor((int) $ptjId)) {
                                    $bahagianId = $get('bahagian_id');

                                    if (! $bahagianId) {
                                        return [];
                                    }

                                    return $query->where('bahagian_id', $bahagianId)
                                        ->pluck('nama_unit', 'id')
                                        ->toArray();
                                }

                                return $query->where('ptj_id', $ptjId)
                                    ->pluck('nama_unit', 'id')
                                    ->toArray();
                            })
                            ->default(fn ($record) => $record?->unit_id)
                            ->visible(fn ($record) => $record === null)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, $livewire): void {
                                if (
                                    filled($state)
                                    && method_exists($livewire, 'redirectToEditIfUnitHasSubunits')
                                ) {
                                    $livewire->redirectToEditIfUnitHasSubunits($state);
                                }
                            })
                            ->helperText('Jika Unit sudah ada KD / KKIA / Wad / Klinik, anda akan diarah ke halaman kemaskini.'),

                        TextInput::make('unit_display')
                            ->label('Unit')
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state(
                                    $record?->unit?->nama_unit
                                );
                            })
                            ->readOnly()
                            ->visible(fn ($record) => $record !== null)
                            ->dehydrated(false),

                        Repeater::make('subunits')
                            ->label('Senarai KD / KKIA / Wad / Klinik')
                            ->columnSpanFull()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah KD / KKIA / Wad / Klinik')
                            ->addAction(fn (Action $action) => $action->color('info')->icon('heroicon-m-plus'))
                            ->columns(2)
                            ->afterStateHydrated(function ($component, ?array $state, $record): void {
                                if ($record && blank($state)) {
                                    $items = Subunit::where('unit_id', $record->unit_id)
                                        ->with('aktivitis')
                                        ->orderBy('nama_subunit')
                                        ->get()
                                        ->map(fn (Subunit $s): array => [
                                            'id' => $s->id,
                                            'nama_subunit' => $s->nama_subunit,
                                            'aktiviti_ids' => $s->aktivitis->pluck('id')->all(),
                                            'parlimen_id' => $s->parlimen_id,
                                            'dun_id' => $s->dun_id,
                                        ])
                                        ->toArray();
                                    $component->state($items);
                                }
                            })
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('nama_subunit')
                                    ->label('KD / KKIA / Wad / Klinik')
                                    ->required()
                                    ->distinct()
                                    ->unique(
                                        table: 'subunits',
                                        column: 'nama_subunit',
                                        ignorable: fn (Get $get) => filled($get('id')) ? Subunit::find($get('id')) : null,
                                        modifyRuleUsing: function (Unique $rule, Get $get, Component $component): Unique {
                                            $unitId = $get('../../unit_id');

                                            if (blank($unitId)) {
                                                $record = $component->getRecord();

                                                if ($record) {
                                                    $unitId = $record->unit_id;
                                                }
                                            }

                                            if (blank($unitId)) {
                                                $id = $get('id');
                                                if (filled($id)) {
                                                    $unitId = Subunit::find($id)?->unit_id;
                                                }
                                            }

                                            if (filled($unitId)) {
                                                $rule->where('unit_id', $unitId);
                                            }

                                            return $rule;
                                        }
                                    )
                                    ->validationMessages([
                                        'distinct' => 'Nama KD / KKIA / Wad / Klinik tidak boleh duplikat dalam senarai ini.',
                                        'unique' => 'Nama KD / KKIA / Wad / Klinik telah wujud untuk Unit ini.',
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
                                    // ->required()
                                    ->options(fn (): array => Parlimen::query()->orderBy('nama_parlimen')->pluck('nama_parlimen', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('dun_id', null)),
                                Select::make('dun_id')
                                    ->label('DUN')
                                    // ->required()
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
                            ->itemLabel(fn (array $state): ?string => filled($state['nama_subunit'] ?? null) ? strtoupper($state['nama_subunit']) : 'KD / KKIA / Wad / Klinik baharu')
                            ->collapsed()
                            ->collapsible()
                            ->deleteAction(function (Action $action): Action {
                                return $action
                                    ->requiresConfirmation()
                                    ->modalHeading(function (array $arguments, Repeater $component): string {
                                        $items = $component->getRawState();
                                        $item = $items[$arguments['item']] ?? [];
                                        $nama = trim((string) ($item['nama_subunit'] ?? ''));

                                        return $nama !== '' ? "Padam {$nama}?" : 'Padam KD / KKIA / Wad / Klinik ini?';
                                    })
                                    ->modalDescription('Adakah anda pasti mahu memadam KD / KKIA / Wad / Klinik ini? Tindakan ini tidak boleh dibatalkan.')
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
        $subunits = $get('subunits') ?? [];

        if (! is_array($subunits)) {
            return;
        }

        foreach ($subunits as $key => $item) {
            if (is_array($item)) {
                $subunits[$key]['aktiviti_ids'] = [];
            }
        }

        $set('subunits', $subunits);
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
            $ptjId = $record->unit?->ptj_id;
        }

        if (blank($ptjId) && filled($get('id'))) {
            $ptjId = Subunit::find($get('id'))?->unit?->ptj_id;
        }

        if (blank($ptjId)) {
            return [];
        }

        return Ptj::query()->find($ptjId)?->aktivitiSelectOptions() ?? [];
    }
}
