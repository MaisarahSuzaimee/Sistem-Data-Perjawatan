<?php

namespace App\Filament\Pages;

use App\Models\Aktiviti;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Kumpulan;
use App\Models\Program;
use App\Models\WaranJawatan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Report extends Page implements HasTable
{
    use InteractsWithTable;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user !== null && in_array((int) $user->role, [1, 2, 3], true);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && in_array((int) $user->role, [1, 2, 3], true);
    }

    protected string $view = 'filament.pages.report';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $navigationLabel = 'Senarai Laporan';

    protected static ?string $title = 'Senarai Laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 15;

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('Bil')
                    ->extraHeaderAttributes(['style' => 'width: 60px'])
                    ->extraCellAttributes(['style' => 'width: 60px']),

                TextColumn::make('name')
                    ->label('Nama Laporan')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
            ])
            ->records(function () {

                $search = $this->getTableSearch();

                $data = [
                    [
                        'id' => 1,
                        'name' => 'L1: Data Keseluruhan Mengikut PTJ',
                        // 'description' => 'Senarai semua pegawai dalam sistem',
                    ],
                    [
                        'id' => 2,
                        'name' => 'L2: Data Perjawatan Kontrak',
                        // 'description' => 'Laporan waran jawatan terkini',
                    ],
                    [
                        'id' => 3,
                        'name' => 'L3: Data Mengikut Kumpulan Mengikut PTJ',
                        // 'description' => 'Senarai jawatan kosong',
                    ],
                    [
                        'id' => 4,
                        'name' => 'L4: Data Keseluruhan Mengikut Jawatan',
                        // 'description' => 'Senarai jawatan kosong',
                    ],
                    [
                        'id' => 5,
                        'name' => 'L5: Laporan JIK Mengikut Jawatan',
                        // 'description' => 'Senarai jawatan kosong',
                    ],
                    [
                        'id' => 6,
                        'name' => 'L6: Laporan JIK Mengikut Gred',
                        // 'description' => 'Senarai jawatan kosong',
                    ],
                    [
                        'id' => 7,
                        'name' => 'L7: Laporan Maklumat Perjawatan Dan Penyandang Mengikut Jawatan',
                        // 'description' => 'Senarai jawatan kosong',
                    ],
                    [
                        'id' => 8,
                        'name' => 'L8: Laporan Kedudukan Perjawatan Dan Pengisian Mengikut Program / Aktiviti ',
                        // 'description' => 'Senarai jawatan kosong',
                    ],
                ];

                if (auth()->user()?->isUser()) {
                    $data = array_values(array_filter(
                        $data,
                        fn (array $item): bool => in_array((int) $item['id'], [1, 2, 3], true),
                    ));
                }

                if (! $search) {
                    return $data;
                }

                return collect($data)
                    ->filter(function ($item) use ($search) {
                        return str_contains(strtolower($item['name']), strtolower($search));
                        // || str_contains(strtolower($item['description']), strtolower($search));
                    })
                    ->values()
                    ->toArray();
            })
            ->actions([

                Action::make('download')
                    ->label('Muat Turun')
                    ->icon('heroicon-o-arrow-down-tray')

                    ->form(function ($record) {

                        return match ($record['id']) {

                            4 => [
                                Select::make('kumpulan_id')
                                    ->label('Pilih SKIM PERKHIDMATAN (Kumpulan)')
                                    ->options(
                                        Kumpulan::query()
                                            ->orderBy('id')
                                            ->pluck('nama_kumpulan', 'id')
                                    )
                                    ->searchable()
                                    ->required(),
                            ],

                            5 => [
                                Select::make('jawatan_id')
                                    ->label('Pilih Jawatan')
                                    ->options(
                                        Jawatan::orderBy('desc_jawatan')
                                            ->pluck('desc_jawatan', 'id')
                                    )
                                    ->searchable()
                                    ->required(),
                            ],

                            6 => [
                                Select::make('jawatan_id')
                                    ->label('Pilih Jawatan')
                                    ->options(
                                        Jawatan::orderBy('desc_jawatan')
                                            ->pluck('desc_jawatan', 'id')
                                    )
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('gred_combination', null))
                                    ->required(),
                                // L5-style exact waran gred_ids combinations, e.g. U9/U10.
                                Select::make('gred_combination')
                                    ->label('Pilih Kombinasi Gred')
                                    ->options(fn (Get $get): array => $this->gredCombinationOptionsForJawatan($get('jawatan_id')))
                                    ->searchable()
                                    ->required(),
                            ],

                            8 => [
                                Select::make('program_id')
                                    ->label('Pilih Program')
                                    ->options(fn (): array => $this->l8ProgramOptions())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('aktiviti_id', null))
                                    ->required(),
                                Select::make('aktiviti_id')
                                    ->label('Pilih Aktiviti')
                                    ->options(fn (Get $get): array => $this->l8ActivityOptions($get('program_id')))
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => filled($get('program_id')))
                                    ->required(fn (Get $get): bool => filled($get('program_id'))),
                            ],

                            default => [],
                        };

                    })

                    ->action(function ($record, array $data) {
                        if (auth()->user()?->isUser() && ! in_array((int) $record['id'], [1, 2, 3], true)) {
                            abort(403);
                        }

                        return match ($record['id']) {

                            1 => redirect()->route('export.dataKeseluruhan'),

                            2 => redirect()->route('export.dataKontrak'),

                            3 => redirect()->route('export.l3'),

                            4 => redirect()->route('export.l4', [
                                'kumpulan_id' => $data['kumpulan_id'],
                            ]),

                            5 => redirect()->route('export.jikByJawatan', [
                                'jawatan_id' => $data['jawatan_id'],
                            ]),

                            6 => redirect()->route('export.l6', [
                                'jawatan_id' => $data['jawatan_id'],
                                'gred_combination' => $data['gred_combination'],
                            ]),

                            7 => redirect()->route('export.l7'),

                            8 => redirect()->route('export.l8', [
                                'program_id' => $data['program_id'],
                                'aktiviti_id' => $data['aktiviti_id'],
                            ]),

                        };

                    }),

            ]);
    }

    /**
     * @return array<int, string>
     */
    protected function gredCombinationOptionsForJawatan(mixed $jawatanId): array
    {
        if (blank($jawatanId)) {
            return [];
        }

        $warans = WaranJawatan::query()
            ->whereJsonContains('jawatan_ids', (int) $jawatanId)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'removed'))
            ->get(['gred_ids', 'jawatan_gred_id']);

        $combinations = [];

        foreach ($warans as $waran) {
            $gredIds = array_values(array_unique(array_map('intval', $waran->gred_ids ?? [])));
            sort($gredIds);

            if ($gredIds !== []) {
                $combinations[implode(',', $gredIds)] = $gredIds;
            }
        }

        if ($combinations === []) {
            return [];
        }

        $greds = Gred::query()
            ->whereIn('id', collect($combinations)->flatten()->unique()->all())
            ->get()
            ->keyBy('id');

        $options = [];

        foreach ($combinations as $key => $gredIds) {
            $gredCodes = collect($gredIds)
                ->map(fn (int $gredId) => $greds->get($gredId))
                ->filter()
                ->sortBy(fn (Gred $gred) => (int) substr($gred->kod_gred, 1))
                ->pluck('kod_gred');

            if ($gredCodes->isEmpty()) {
                continue;
            }

            $options[$key] = $gredCodes->implode('/');
        }

        return collect($options)
            ->sortByDesc(fn (string $label) => collect(explode('/', $label))
                ->map(fn (string $code) => (int) substr($code, 1))
                ->max())
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function l8ProgramOptions(): array
    {
        $activityIds = $this->l8ActiveActivityIds();

        if ($activityIds === []) {
            return [];
        }

        return Program::query()
            ->whereHas('aktiviti', fn ($query) => $query->whereIn('id', $activityIds))
            ->orderBy('nama_program')
            ->get()
            ->mapWithKeys(fn (Program $program): array => [
                $program->id => $program->display_name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function l8ActivityOptions(mixed $programId): array
    {
        if (blank($programId)) {
            return [];
        }

        return Aktiviti::query()
            ->with('program')
            ->where('program_id', (int) $programId)
            ->whereIn('id', $this->l8ActiveActivityIds())
            ->get()
            ->sortBy(
                fn (Aktiviti $aktiviti): string => strtolower(implode(' ', [
                    $aktiviti->program?->nama_program ?? '',
                    $aktiviti->no_aktivit ?? '',
                    $aktiviti->nama_aktiviti ?? '',
                ])),
                SORT_NATURAL | SORT_FLAG_CASE,
            )
            ->mapWithKeys(function (Aktiviti $aktiviti): array {
                $label = trim(implode(' - ', array_filter([
                    $aktiviti->no_aktivit,
                    $aktiviti->nama_aktiviti,
                ])));

                return [$aktiviti->id => ($label ?: "Aktiviti {$aktiviti->id}")];
            })
            ->all();
    }

    /**
     * @return list<int>
     */
    protected function l8ActiveActivityIds(): array
    {
        return WaranJawatan::query()
            ->listed()
            ->whereNotNull('aktiviti_id')
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'removed'))
            ->distinct()
            ->pluck('aktiviti_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function getBreadcrumbs(): array
    {
        // The back button (see getHeading()) replaces the need for a
        // breadcrumb trail on this page.
        return [];
    }

    public function getHeading(): string|Htmlable
    {
        return new HtmlString(
            '<a href="'.e(Dashboard::getUrl()).'" class="mystaff-back-btn" aria-label="Kembali ke Dashboard">'.
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>'.
            '</a>'.
            '<span>'.e($this->getTitle()).'</span>'
        );
    }
}
