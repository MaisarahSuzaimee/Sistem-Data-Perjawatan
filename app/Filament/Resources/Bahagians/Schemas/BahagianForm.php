<?php

namespace App\Filament\Resources\Bahagians\Schemas;

use App\Models\Bahagian;
use App\Models\Program;
use App\Models\Ptj;
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

class BahagianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Bahagian')
                    ->schema([
                        // Select::make('program_id')
                        //     ->label('Program')
                        //     ->options(
                        //         Program::query()
                        //             ->orderBy('nama_program')
                        //             ->pluck('nama_program', 'id')
                        //     )
                        //     ->live()
                        //     ->searchable()
                        //     ->preload()
                        //     ->dehydrated(false)
                        //     ->afterStateUpdated(fn (Set $set) => $set('ptj_id', null))
                        //     ->visible(fn ($record) => $record === null)
                        //     ->columnSpanFull(),

                        Select::make('ptj_id')
                            ->label('PTJ')
                            ->options(function (Get $get): array {
                                $programId = $get('program_id');

                                $query = Ptj::query()
                                    ->where('is_jkn', true)
                                    ->orderBy('nama_ptj');

                                if (filled($programId)) {
                                    $query->whereHas('programs', fn ($q) => $q->whereKey($programId));
                                }

                                return $query->pluck('nama_ptj', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, $livewire): void {
                                if (method_exists($livewire, 'redirectToEditIfPtjHasBahagian')) {
                                    $livewire->redirectToEditIfPtjHasBahagian($state);
                                }
                            })
                            ->helperText('Hanya PTJ JKN (termasuk VEKTOR) sahaja. Jika PTJ sudah ada bahagian, anda akan diarah ke halaman kemaskini.')
                            ->visible(fn ($record) => $record === null)
                            ->columnSpanFull(),

                        TextInput::make('ptj_display')
                            ->label('PTJ')
                            ->afterStateHydrated(function ($component, $state, $record): void {
                                $component->state($record?->ptj?->nama_ptj);
                            })
                            ->readOnly()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),

                        Repeater::make('bahagians')
                            ->label('Senarai Bahagian')
                            ->columnSpanFull()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Bahagian')
                            ->addAction(fn (Action $action) => $action->color('info')->icon('heroicon-m-plus'))
                            ->afterStateHydrated(function ($component, ?array $state, $record): void {
                                if ($record && blank($state)) {
                                    $items = Bahagian::where('ptj_id', $record->ptj_id)
                                        ->orderBy('nama_bahagian')
                                        ->get()
                                        ->map(fn (Bahagian $b): array => [
                                            'id' => $b->id,
                                            'nama_bahagian' => $b->nama_bahagian,
                                        ])
                                        ->toArray();
                                    $component->state($items);
                                }
                            })
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('nama_bahagian')
                                    ->label('Nama Bahagian')
                                    ->required()
                                    ->distinct()
                                    ->unique(
                                        table: 'bahagians',
                                        column: 'nama_bahagian',
                                        ignorable: fn (Get $get) => filled($get('id')) ? Bahagian::find($get('id')) : null,
                                        modifyRuleUsing: function (Unique $rule, Get $get, Component $component): Unique {
                                            $ptjId = $get('../../ptj_id');

                                            if (blank($ptjId)) {
                                                $record = $component->getRecord();

                                                if ($record) {
                                                    $ptjId = $record->ptj_id;
                                                }
                                            }

                                            if (blank($ptjId)) {
                                                $id = $get('id');
                                                if (filled($id)) {
                                                    $ptjId = Bahagian::find($id)?->ptj_id;
                                                }
                                            }

                                            if (filled($ptjId)) {
                                                $rule->where('ptj_id', $ptjId);
                                            }

                                            $rule->whereNull('deleted_at');

                                            return $rule;
                                        }
                                    )
                                    ->validationMessages([
                                        'distinct' => 'Nama bahagian tidak boleh duplikat dalam senarai ini.',
                                        'unique' => 'Nama bahagian telah wujud untuk PTJ ini.',
                                    ])
                                    ->dehydrateStateUsing(fn (?string $state): string => $state ? strtoupper($state) : '')
                                    ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                            ])
                            ->itemLabel(fn (array $state): ?string => filled($state['nama_bahagian'] ?? null) ? strtoupper($state['nama_bahagian']) : 'Bahagian baharu')
                            ->collapsed()
                            ->collapsible()
                            ->deleteAction(function (Action $action): Action {
                                return $action
                                    ->requiresConfirmation()
                                    ->modalHeading(function (array $arguments, Repeater $component): string {
                                        $items = $component->getRawState();
                                        $item = $items[$arguments['item']] ?? [];
                                        $nama = trim((string) ($item['nama_bahagian'] ?? ''));

                                        return $nama !== '' ? "Padam {$nama}?" : 'Padam bahagian ini?';
                                    })
                                    ->modalDescription('Adakah anda pasti mahu memadam bahagian ini? Tindakan ini tidak boleh dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Padam')
                                    ->modalCancelActionLabel('Batal');
                            }),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }
}
