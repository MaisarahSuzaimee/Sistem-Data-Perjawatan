<?php

namespace App\Filament\Resources\Programs\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Program')
                    ->schema([
                        TextInput::make('nama_program')
                            ->label('No Program')
                            ->required()
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state))
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->columnSpan(1)
                            ->unique(),
                        TextInput::make('desc_program')
                            ->label('Nama Program')
                            // ->required()
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state))
                            ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                            ->columnSpan(1)
                            ->unique(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Aktiviti')
                    ->schema([
                        Repeater::make('aktiviti')
                            ->label('Aktiviti')
                            ->relationship(
                                name: 'aktiviti',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $user = auth()->user();

                                    if ($user?->isUser() && filled($user->ptj_id)) {
                                        $query->assignedToPtj((int) $user->ptj_id);
                                    }

                                    return $query;
                                },
                            )
                            ->columnSpanFull()
                            ->columns(2)
                            ->addActionLabel('Tambah Aktiviti')
                            ->addable(fn (): bool => ! auth()->user()?->isUser())
                            ->deletable(fn (): bool => ! auth()->user()?->isUser())
                            ->addAction(function (Action $action) {
                                return $action
                                    ->color('info')
                                    ->icon('heroicon-m-plus');
                            })
                            ->required()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->schema([
                                TextInput::make('no_aktivit')
                                    ->label('No Aktiviti')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, callable $set): void {
                                        $set('no_aktivit', filled($state) ? strtoupper($state) : null);
                                    })
                                    ->dehydrateStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                                    ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                                    ->distinct()
                                    ->validationMessages([
                                        'distinct' => 'No aktiviti ini sudah wujud dalam program yang sama.',
                                    ]),
                                TextInput::make('nama_aktiviti')
                                    ->label('Nama Aktiviti')
                                    ->dehydrateStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                                    ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                                Select::make('ptjs')
                                    ->label('PTJ')
                                    ->relationship(
                                        name: 'ptjs',
                                        titleAttribute: 'nama_ptj',
                                        modifyQueryUsing: function (Builder $query): Builder {
                                            $query->orderBy('nama_ptj');

                                            $user = auth()->user();

                                            if ($user?->isUser() && filled($user->ptj_id)) {
                                                $query->where('ptjs.id', $user->ptj_id);
                                            }

                                            return $query;
                                        },
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(
                                fn (array $state): ?string => filled($state['no_aktivit'] ?? null) || filled($state['nama_aktiviti'] ?? null)
                                ? ($state['no_aktivit'] ?? '').' - '.($state['nama_aktiviti'] ?? '')
                                : 'Tambah Aktiviti'
                            )->collapsed(),
                    ])
                    ->columnSpanFull(),

            ]);

    }
}
