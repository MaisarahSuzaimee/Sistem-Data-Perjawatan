<?php

namespace App\Exports;

use App\Models\Bahagian;
use App\Models\Pegawai;
use App\Models\WaranJawatan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DataKeseluruhanExport implements FromCollection, WithCustomStartCell, WithEvents
{
    /**
     * @return Collection
     */
    public array $mergeRows = [];

    public function collection()
    {
        $this->mergeRows = [];

        $user = auth()->user();
        $scopedPtjId = $user && $user->isPtjScoped()
            ? (int) $user->ptj_id
            : null;

        $warans = WaranJawatan::with([
            'aktiviti.program',
            'ptj',
            'bahagian',
            'pegawai',
        ])
            ->when(
                $scopedPtjId !== null,
                fn ($query) => $query->where('ptj_id', $scopedPtjId),
            )
            ->get();

        $jknWarans = $warans->filter(fn ($item) => $this->isJkn($item->ptj));
        $otherWarans = $warans->reject(fn ($item) => $this->isJkn($item->ptj));

        $rows = collect();
        $currentRow = 5; // because startCell = A5

        $programs = $otherWarans
            ->groupBy(fn ($item) => $item->aktiviti?->program?->nama_program)
            ->sortBy(function (Collection $items, $key) {
                if (preg_match('/(\d+)/', (string) $key, $matches)) {
                    return (int) $matches[1];
                }

                return PHP_INT_MAX;
            });

        foreach ($programs as $programKey => $items) {
            $program = $items->first()?->aktiviti?->program;
            $namaProgram = $program
                ? $program->nama_program.': '.$program->desc_program
                : ($programKey ?: 'TANPA PROGRAM');

            $unitGroups = $items->groupBy('ptj_id')->map(function (Collection $ptjItems) {
                $ptj = $ptjItems->first()?->ptj;

                return [
                    'label' => $ptj?->nama_ptj,
                    'items' => $ptjItems,
                    'kontrak' => Pegawai::query()
                        ->where('ptj_id', $ptj?->id)
                        ->where('is_kontrak', true)
                        ->count() ?: '0',
                ];
            })->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();

            $currentRow = $this->appendSection(
                $rows,
                $currentRow,
                $namaProgram,
                $unitGroups,
                Pegawai::query()
                    ->whereIn('ptj_id', $items->pluck('ptj_id')->unique()->filter()->all())
                    ->where('is_kontrak', true)
                    ->count() ?: '0',
            );
        }

        // is_jkn PTJs: all bahagian under one trailing Ibu Pejabat section only.
        $jknPtjIds = $warans
            ->filter(fn ($item) => $this->isJkn($item->ptj))
            ->pluck('ptj_id')
            ->unique()
            ->filter()
            ->values();

        $bahagians = Bahagian::query()
            ->whereIn('ptj_id', $jknPtjIds->isNotEmpty() ? $jknPtjIds->all() : [0])
            ->orderBy('id')
            ->get();

        if ($bahagians->isNotEmpty()) {
            $waransByBahagian = $jknWarans->groupBy('bahagian_id');

            $unitGroups = $bahagians->map(function (Bahagian $bahagian) use ($waransByBahagian) {
                return [
                    'label' => $bahagian->nama_bahagian,
                    'items' => $waransByBahagian->get($bahagian->id, collect()),
                    'kontrak' => Pegawai::query()
                        ->where('bahagian_id', $bahagian->id)
                        ->where('is_kontrak', true)
                        ->count() ?: '0',
                ];
            });

            $currentRow = $this->appendSection(
                $rows,
                $currentRow,
                'IBU PEJABAT JKN',
                $unitGroups,
                Pegawai::query()
                    ->whereIn('ptj_id', $jknPtjIds->isNotEmpty() ? $jknPtjIds->all() : [0])
                    ->where('is_kontrak', true)
                    ->count() ?: '0',
                [
                    'perjawatan' => $jknWarans->count(),
                    'pengisian' => $this->pengisianTetapCount($jknWarans),
                ],
                isIbuPejabat: true,
            );
        }

        $jkPerjawatanTetap = $warans->count();
        $jkIsiTetap = $this->pengisianTetapCount($warans);
        $jkKosongTetap = ($jkPerjawatanTetap - $jkIsiTetap) ?: '0';
        $jkPeratusPengisianTetap = $jkPerjawatanTetap > 0
            ? round(($jkIsiTetap / $jkPerjawatanTetap) * 100, 2).'%'
            : '0%';

        $ptjIds = $warans->pluck('ptj_id')->unique()->filter()->values()->all();

        $jkIsiKontrak = Pegawai::query()
            ->whereIn('ptj_id', $ptjIds !== [] ? $ptjIds : [0])
            ->where('is_kontrak', true)
            ->count() ?: '0';

        $rows->push([
            'JUMLAH KESELURUHAN',
            '',
            $jkPerjawatanTetap,
            $jkIsiTetap,
            $jkKosongTetap,
            $jkPeratusPengisianTetap,
            $jkIsiKontrak,
            $jkIsiTetap + $jkIsiKontrak,
        ]);

        return $rows;
    }

    protected function isJkn($ptj): bool
    {
        return (bool) ($ptj && ($ptj->is_jkn || $ptj->nama_ptj === 'JABATAN KESIHATAN NEGERI KEDAH'));
    }

    protected function pengisianTetapCount(Collection $items): int
    {
        return $items->filter(function ($item) {
            $pegawai = $item->pegawai;

            return $pegawai && ($pegawai->is_tetap || $pegawai->is_kontrak_interim);
        })->count();
    }

    /**
     * @param  Collection<int, array{label: ?string, items: Collection, kontrak: int|string}>  $unitGroups
     * @param  array{perjawatan?: int, pengisian?: int}|null  $totalOverride
     */
    protected function appendSection(
        Collection $rows,
        int $currentRow,
        string $sectionLabel,
        Collection $unitGroups,
        int|string $sectionKontrak,
        ?array $totalOverride = null,
        bool $isIbuPejabat = false,
    ): int {
        $programRow = $currentRow;
        $rows->push([
            $sectionLabel,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);
        $currentRow++;

        $unitRows = [];
        $index = 1;
        $jumlahPerjawatanTetap = 0;
        $jumlahPengisianTetap = 0;

        foreach ($unitGroups as $unit) {
            /** @var Collection $items */
            $items = $unit['items'];
            $perjawatanTetap = $items->count() ?: '0';
            $pengisianTetap = $this->pengisianTetapCount($items) ?: '0';
            $kosongTetap = ((int) $perjawatanTetap - (int) $pengisianTetap) ?: '0';
            $peratusPengisianTetap = (int) $perjawatanTetap > 0
                ? round(((int) $pengisianTetap / (int) $perjawatanTetap) * 100, 2).'%'
                : '0%';
            $kontrak = $unit['kontrak'];
            $jumlahPengisian = ((int) $pengisianTetap + (int) $kontrak) ?: '0';

            $unitRows[] = $currentRow;
            $rows->push([
                $index,
                $unit['label'],
                $perjawatanTetap,
                $pengisianTetap,
                $kosongTetap,
                $peratusPengisianTetap,
                $kontrak,
                $jumlahPengisian,
            ]);
            $currentRow++;
            $index++;

            $jumlahPerjawatanTetap += (int) $perjawatanTetap;
            $jumlahPengisianTetap += (int) $pengisianTetap;
        }

        if ($totalOverride !== null) {
            $jumlahPerjawatanTetap = $totalOverride['perjawatan'] ?? $jumlahPerjawatanTetap;
            $jumlahPengisianTetap = $totalOverride['pengisian'] ?? $jumlahPengisianTetap;
        }

        $jumlahKosongTetap = ($jumlahPerjawatanTetap - $jumlahPengisianTetap) ?: '0';
        $jumlahPeratusPengisianTetap = $jumlahPerjawatanTetap > 0
            ? round(($jumlahPengisianTetap / $jumlahPerjawatanTetap) * 100, 2).'%'
            : '0%';

        $jumlahRow = $currentRow;
        $rows->push([
            'JUMLAH',
            '',
            $jumlahPerjawatanTetap,
            $jumlahPengisianTetap,
            $jumlahKosongTetap,
            $jumlahPeratusPengisianTetap,
            $sectionKontrak,
            $jumlahPengisianTetap + (int) $sectionKontrak,
        ]);
        $currentRow++;

        $this->mergeRows[] = [
            'program_row' => $programRow,
            'ptj_rows' => $unitRows,
            'jumlah_row' => $jumlahRow,
            'is_ibu_pejabat' => $isIbuPejabat,
        ];

        return $currentRow;
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', 'DATA PERJAWATAN MANUAL JKN KEDAH SEHINGGA 28 FEBRUARI 2026');
                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells('A2:H2');

                $sheet->setCellValue('A2', '(Jumlah perjawatan tetap termasuk jawatan tanpa waran kod 11000)');

                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 9,
                        'italic' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(30);

                $sheet->mergeCells('A3:A4');
                $sheet->setCellValue('A3', 'BIL');
                $sheet->getStyle('A3:A4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension('A')->setWidth(10);
                $sheet->getRowDimension('3')->setRowHeight(30);
                $sheet->getRowDimension('4')->setRowHeight(30);

                $sheet->mergeCells('B3:B4');
                $sheet->setCellValue('B3', 'PUSAT TANGGUNGJAWAB');
                $sheet->getStyle('B3:B4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension('B')->setWidth(40);

                $sheet->mergeCells('C3:F3');
                $sheet->setCellValue('C3', 'TETAP');
                $sheet->getStyle('C3:F3')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->setCellValue('C4', 'Perjawatan');
                $sheet->getStyle('C4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('C')->setWidth(13);

                $sheet->setCellValue('D4', 'Pengisian');
                $sheet->getStyle('D4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('D')->setWidth(13);

                $sheet->setCellValue('E4', 'Kosong Perjawatan');
                $sheet->getStyle('E4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('E')->setWidth(15);

                $sheet->setCellValue('F4', 'Peratus Pengisian');
                $sheet->getStyle('F4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('F')->setWidth(13);

                $sheet->setCellValue('G3', 'KONTRAK');
                $sheet->getStyle('G3')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('G')->setWidth(15);

                $sheet->setCellValue('G4', 'Pengisian');
                $sheet->getStyle('G4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->setCellValue('H3', 'JUMLAH');
                $sheet->getStyle('H3')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension('H')->setWidth(18);

                $sheet->setCellValue('H4', 'Pengisian (Tetap + Kontrak)');
                $sheet->getStyle('H4')->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A3:H4')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '989898',
                        ],
                    ],
                ]);

                $export = $event->getConcernable();

                $sheet = $event->sheet->getDelegate();

                foreach ($export->mergeRows as $row) {
                    $sectionFill = ! empty($row['is_ibu_pejabat']) ? '808080' : '9bc0e2';

                    // PROGRAM / IBU PEJABAT ROW (A to H merge)
                    $sheet->mergeCells("A{$row['program_row']}:H{$row['program_row']}");
                    $sheet->setCellValue("A{$row['program_row']}", $sheet->getCell("A{$row['program_row']}")->getValue());
                    $sheet->getStyle("A{$row['program_row']}:H{$row['program_row']}")->applyFromArray([
                        'font' => [
                            'name' => 'Calibri',
                            'bold' => true,
                            'size' => 11,
                            'color' => ['rgb' => '000000'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => $sectionFill,
                            ],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getRowDimension($row['program_row'])->setRowHeight(30);

                    foreach ($row['ptj_rows'] as $ptjRow) {

                        $sheet->getStyle("A{$ptjRow}:H{$ptjRow}")->applyFromArray([
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => true,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);

                        $sheet->getStyle("C{$ptjRow}:H{$ptjRow}")->applyFromArray([
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'wrapText' => true,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);

                        $sheet->getStyle("A{$ptjRow}")->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        $sheet->getRowDimension($ptjRow)->setRowHeight(30);
                    }

                    // JUMLAH ROW (A to B merge)
                    $sheet->mergeCells("A{$row['jumlah_row']}:B{$row['jumlah_row']}");
                    $sheet->setCellValue("A{$row['jumlah_row']}", $sheet->getCell("A{$row['jumlah_row']}")->getValue());
                    $sheet->getStyle("A{$row['jumlah_row']}:H{$row['jumlah_row']}")->applyFromArray([
                        'font' => [
                            'name' => 'Calibri',
                            'bold' => true,
                            'size' => 11,
                            'color' => ['rgb' => '000000'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => 'ca9eb3',
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);

                    $sheet->getRowDimension($row['jumlah_row'])->setRowHeight(30);
                }

                $sheet->mergeCells("A{$lastRow}:B{$lastRow}");
                $sheet->getStyle("A{$lastRow}:H{$lastRow}")->applyFromArray([
                    'font' => [
                        'name' => 'Calibri',
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '00b0f0',
                        ],
                    ],
                ]);
                $sheet->getRowDimension($lastRow)->setRowHeight(30);
            },
        ];
    }
}
