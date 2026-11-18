<?php

namespace App\Exports;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\Kumpulan;
use App\Models\WaranJawatan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class L4Export implements FromCollection, WithEvents
{
    public function __construct(
        private int $kumpulanId,
    ) {}

    /** @var list<int> */
    public array $schemeRows = [];

    /** @var list<int> */
    public array $dataRows = [];

    /** @var list<int> */
    public array $openPostRows = [];

    /** @var list<int> */
    public array $totalRows = [];

    public int $grandTotalRow = 0;

    public int $lastRow = 2;

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        Carbon::setLocale('ms');
        $this->schemeRows = [];
        $this->dataRows = [];
        $this->openPostRows = [];
        $this->totalRows = [];
        $this->grandTotalRow = 0;

        $kumpulan = Kumpulan::query()->findOrFail($this->kumpulanId);
        $allowedPairs = [];
        $allowedJawatanGredIds = [];

        foreach (Jawatan_Gred::query()
            ->where('kumpulan_id', $this->kumpulanId)
            ->get(['id', 'jawatan_id', 'gred_id']) as $jawatanGred) {
            $jawatanId = (int) $jawatanGred->jawatan_id;
            $gredId = (int) $jawatanGred->gred_id;
            $allowedPairs[$jawatanId.':'.$gredId] = [$jawatanId, $gredId];
            $allowedJawatanGredIds[(int) $jawatanGred->id] = [$jawatanId, $gredId];
        }

        $warans = WaranJawatan::query()
            ->with(['pegawai', 'jawatanGred.jawatan', 'jawatanGred.gred'])
            ->get()
            ->filter(fn (WaranJawatan $waran): bool => ($waran->status ?? 'active') !== 'removed');

        $jawatanIds = $warans
            ->flatMap(fn (WaranJawatan $waran): array => $this->normalizedIds($waran->jawatan_ids))
            ->unique()
            ->values()
            ->all();
        $gredIds = $warans
            ->flatMap(fn (WaranJawatan $waran): array => $this->normalizedIds($waran->gred_ids))
            ->unique()
            ->values()
            ->all();

        $jawatanNames = Jawatan::query()->whereIn('id', $jawatanIds)->pluck('desc_jawatan', 'id');
        $gredNames = Gred::query()->whereIn('id', $gredIds)->pluck('kod_gred', 'id');
        $schemes = [];

        foreach ($warans as $waran) {
            $positionIds = $this->normalizedIds($waran->jawatan_ids);
            $gradeIds = $this->normalizedIds($waran->gred_ids);

            $matchingPairs = [];
            if ($positionIds !== [] && $gradeIds !== []) {
                foreach ($positionIds as $positionId) {
                    foreach ($gradeIds as $gradeId) {
                        $pairKey = $positionId.':'.$gradeId;

                        if (isset($allowedPairs[$pairKey])) {
                            $matchingPairs[$pairKey] = $allowedPairs[$pairKey];
                        }
                    }
                }
            } elseif (isset($allowedJawatanGredIds[(int) $waran->jawatan_gred_id])) {
                $pair = $allowedJawatanGredIds[(int) $waran->jawatan_gred_id];
                $matchingPairs[$pair[0].':'.$pair[1]] = $pair;
            }

            if ($matchingPairs === []) {
                continue;
            }

            $positionIds = collect($matchingPairs)->pluck(0)->unique()->values()->all();
            $gradeIds = collect($matchingPairs)->pluck(1)->unique()->values()->all();
            $positionNames = collect($positionIds)
                ->map(fn (int $id): ?string => $jawatanNames->get($id) ?? (
                    (int) $waran->jawatanGred?->jawatan_id === $id
                        ? $waran->jawatanGred?->jawatan?->desc_jawatan
                        : null
                ))
                ->filter(fn ($name): bool => filled($name))
                ->values();
            $schemeName = $positionNames->first() ?? 'TANPA SKIM';
            $positionName = trim((string) $waran->paparan_jawatan);

            if ($positionName === '') {
                $positionName = $positionNames->isNotEmpty()
                    ? $positionNames->implode(', ')
                    : (string) ($waran->jawatanGred?->jawatan?->desc_jawatan ?? 'TANPA NAMA JAWATAN');
            }

            $gradeNames = collect($gradeIds)
                ->map(fn (int $id): ?string => $gredNames->get($id) ?? (
                    (int) $waran->jawatanGred?->gred_id === $id
                        ? $waran->jawatanGred?->gred?->kod_gred
                        : null
                ))
                ->filter(fn ($name): bool => filled($name))
                ->unique()
                ->values();
            $gradeLabel = $gradeNames->implode('/');

            if ($gradeLabel !== '' && ! str_contains(mb_strtolower($positionName), mb_strtolower($gradeLabel))) {
                $positionName .= ', Gred '.$gradeLabel;
            }

            $schemeKey = $positionIds !== [] ? implode(',', $positionIds) : mb_strtolower($schemeName);
            $itemKey = implode('|', [
                $schemeKey,
                implode(',', $gradeIds),
                mb_strtolower($positionName),
            ]);

            $isFilled = $waran->pegawai
                && ($waran->pegawai->is_tetap || $waran->pegawai->is_kontrak_interim);

            $schemes[$schemeKey] ??= [
                'name' => $schemeName,
                'sort' => $positionIds[0] ?? PHP_INT_MAX,
                'items' => [],
            ];
            $schemes[$schemeKey]['items'][$itemKey] ??= [
                'label' => $positionName,
                'grade' => $gradeLabel,
                'is_open_post' => str_contains(mb_strtolower($positionName), '(open post)'),
                'jawatan' => 0,
                'isi' => 0,
            ];
            $schemes[$schemeKey]['items'][$itemKey]['jawatan']++;
            $schemes[$schemeKey]['items'][$itemKey]['isi'] += $isFilled ? 1 : 0;
        }

        $preferredScheme = 'PENGARAH KESIHATAN NEGERI, PEGAWAI PERUBATAN, GRED UTAMA B';
        $isPegawaiPerubatan = mb_strtoupper(trim($kumpulan->nama_kumpulan)) === 'PEGAWAI PERUBATAN';

        uasort($schemes, function (array $a, array $b) use ($isPegawaiPerubatan, $preferredScheme): int {
            $aIsPreferred = $isPegawaiPerubatan
                && mb_strtoupper(trim($a['name'])) === $preferredScheme;
            $bIsPreferred = $isPegawaiPerubatan
                && mb_strtoupper(trim($b['name'])) === $preferredScheme;

            if ($aIsPreferred !== $bIsPreferred) {
                return $aIsPreferred ? -1 : 1;
            }

            return ($a['sort'] <=> $b['sort'])
                ?: strnatcasecmp($a['name'], $b['name']);
        });

        $rows = collect([[
            'DATA PERJAWATAN '.mb_strtoupper($kumpulan->nama_kumpulan)
                .' MENGIKUT SKIM PERKHIDMATAN DI JKN KEDAH SEHINGGA '
                .strtoupper(Carbon::now()->translatedFormat('d F Y')),
            '', '', '', '', '', '',
        ], [
            'BIL',
            'SKIM PERKHIDMATAN/NAMA & GRED JAWATAN',
            '',
            'JAWATAN',
            'ISI',
            'KOSONG',
            'PERATUS',
        ]]);

        $schemeNumber = 0;
        $grandTotal = ['jawatan' => 0, 'isi' => 0];

        foreach ($schemes as $scheme) {
            $schemeNumber++;
            $schemeStartRow = $rows->count() + 1;
            $rows->push([$schemeNumber, mb_strtoupper($scheme['name']), '', '', '', '', '']);

            $items = array_values($scheme['items']);
            usort($items, function (array $a, array $b): int {
                return strnatcasecmp($b['grade'], $a['grade'])
                    ?: ($b['is_open_post'] <=> $a['is_open_post'])
                    ?: strnatcasecmp($a['label'], $b['label']);
            });

            $subtotal = ['jawatan' => 0, 'isi' => 0];

            foreach ($items as $index => $item) {
                $kosong = $item['jawatan'] - $item['isi'];
                $rows->push([
                    '',
                    $index + 1,
                    $item['label'],
                    $item['jawatan'],
                    $item['isi'],
                    $kosong,
                    $this->percentage($item['isi'], $item['jawatan']),
                ]);
                $this->dataRows[] = $rows->count();
                if ($item['is_open_post']) {
                    $this->openPostRows[] = $rows->count();
                }
                $subtotal['jawatan'] += $item['jawatan'];
                $subtotal['isi'] += $item['isi'];
            }

            $rows->push([
                '',
                'Jumlah',
                '',
                $subtotal['jawatan'],
                $subtotal['isi'],
                $subtotal['jawatan'] - $subtotal['isi'],
                $this->percentage($subtotal['isi'], $subtotal['jawatan']),
            ]);
            $this->schemeRows[] = $schemeStartRow;
            $this->totalRows[] = $rows->count();
            $grandTotal['jawatan'] += $subtotal['jawatan'];
            $grandTotal['isi'] += $subtotal['isi'];
        }

        $rows->push([
            'JUMLAH KESELURUHAN',
            '',
            '',
            $grandTotal['jawatan'],
            $grandTotal['isi'],
            $grandTotal['jawatan'] - $grandTotal['isi'],
            $this->percentage($grandTotal['isi'], $grandTotal['jawatan']),
        ]);
        $this->grandTotalRow = $rows->count();
        $this->lastRow = $rows->count();

        return $rows;
    }

    /**
     * @param  array<int, int|string>|null  $ids
     * @return list<int>
     */
    private function normalizedIds(?array $ids): array
    {
        return collect($ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function percentage(int $filled, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        $percentage = round(($filled / $total) * 100, 2);

        if ($percentage === 0.0 || $percentage === 100.0) {
            return number_format($percentage, 0).'%';
        }

        return number_format($percentage, 2).'%';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $thin = [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ];

                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('B2:C2');
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '4472C4'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'A6A6A6'],
                    ],
                    'borders' => ['allBorders' => $thin],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(34);

                foreach ([
                    'A' => 8,
                    'B' => 8,
                    'C' => 64,
                    'D' => 12,
                    'E' => 12,
                    'F' => 12,
                    'G' => 14,
                ] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                foreach ($this->schemeRows as $index => $row) {
                    $lastSchemeRow = $this->totalRows[$index];
                    $sheet->mergeCells("B{$row}:G{$row}");
                    $sheet->mergeCells("A{$row}:A{$lastSchemeRow}");
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'bold' => true, 'size' => 10],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getStyle("A{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->dataRows as $row) {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 10],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getStyle("B{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$row}:G{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->openPostRows as $row) {
                    $label = (string) $sheet->getCell("C{$row}")->getValue();
                    $phrase = '(Open Post)';
                    $position = stripos($label, $phrase);

                    if ($position === false) {
                        continue;
                    }

                    $richText = new RichText;
                    $richText->createText(substr($label, 0, $position));
                    $openPost = $richText->createTextRun(substr($label, $position, strlen($phrase)));
                    $openPost->getFont()->setColor(new Color('FFFF0000'));
                    $richText->createText(substr($label, $position + strlen($phrase)));
                    $sheet->setCellValue("C{$row}", $richText);
                }

                foreach ($this->totalRows as $row) {
                    $sheet->mergeCells("B{$row}:C{$row}");
                    $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'bold' => true, 'size' => 10],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F4B1D0'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(21);
                }

                $sheet->mergeCells("A{$this->grandTotalRow}:C{$this->grandTotalRow}");
                $sheet->getStyle("A{$this->grandTotalRow}:G{$this->grandTotalRow}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B0F0'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => ['allBorders' => $thin],
                ]);
                $sheet->getRowDimension($this->grandTotalRow)->setRowHeight(21);

                $sheet->getPageSetup()->setOrientation('landscape');
                $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
                $sheet->getPageSetup()->setPrintArea("A1:G{$this->lastRow}");
                $sheet->freezePane('D3');
            },
        ];
    }
}
