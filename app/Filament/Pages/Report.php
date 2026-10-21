<?php

namespace App\Filament\Pages;

use App\Models\Jawatan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
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

                            default => [],
                        };

                    })

                    ->action(function ($record, array $data) {

                        return match ($record['id']) {

                            1 => redirect()->route('export.dataKeseluruhan'),

                            2 => redirect()->route('export.dataKontrak'),

                            3 => redirect()->route('export.l3'),

                            4 => redirect()->route('export.l4'),

                            5 => redirect()->route('export.jikByJawatan', [
                                'jawatan_id' => $data['jawatan_id'],
                            ]),

                            6 => redirect()->route('export.l6'),

                            7 => redirect()->route('export.l7'),

                            8 => redirect()->route('export.l8'),

                        };

                    }),

            ]);
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
