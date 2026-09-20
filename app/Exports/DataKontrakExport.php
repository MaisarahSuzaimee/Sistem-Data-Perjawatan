<?php

namespace App\Exports;

use App\Models\Pegawai;
use App\Models\WaranJawatan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Data Perjawatan Kontrak.
 *
 * Structure (matches the approved Excel layout):
 *   Row 1 : DATA PERJAWATAN KONTRAK JKN KEDAH SEHINGGA <tarikh> (title, merged)
 *   Row 2 : BIL | PUSAT TANGGUNGJAWAB | JUMLAH JAWATAN | <satu lajur per jawatan/gred>
 *   Rows 3+ : per program: section header, unit (PTJ / Bahagian JKN) rows,
 *             JUMLAH subtotal row; then a blank row and JUMLAH KESELURUHAN
 *
 * Hanya pegawai dengan is_kontrak = 1 dikira. Lajur hijau (jawatan) dibina
 * secara dinamik: setiap gabungan jawatan + gred yang wujud pada pegawai
 * kontrak dalam pangkalan data menjadi satu lajur (label: desc_jawatan + kod_gred).
 */
class DataKontrakExport implements FromCollection, WithEvents, WithStrictNullComparison
{
    /**
     * Green header columns: one per distinct jawatan+gred on kontrak pegawai.
     *
     * @var list<array{label: string, jawatanGredIds: list<int>}>
     */
    public array $columns = [];

    /** @var Collection<int, Pegawai>|null */
    protected ?Collection $kontrakPegawai = null;

    /** All rows (1-indexed positions start at A1). */
    public array $rows = [];

    /** Row numbers of program section header rows. */
    public array $sectionRows = [];

    /** Row numbers of unit (PTJ) data rows. */
    public array $dataRows = [];

    /** Row numbers of JUMLAH subtotal rows. */
    public array $jumlahRows = [];

    /** Row number of the JUMLAH KESELURUHAN row. */
    public int $totalRow = 0;

    /** Last column index (1-based). */
    public int $lastColumnIndex = 0;

    public function collection()
    {
        Carbon::setLocale('ms');
        $today = strtoupper(Carbon::now()->translatedFormat('d F Y'));

        $this->kontrakPegawai = $this->loadKontrakPegawai();
        $this->columns = $this->buildColumnsFromKontrak($this->kontrakPegawai);
        $this->lastColumnIndex = 3 + count($this->columns);

        $units = $this->buildUnits();
        $counts = $this->countKontrak($units);

        $rows = collect();

        // Row 1 – title
        $row = array_fill(0, $this->lastColumnIndex, '');
        $row[0] = "DATA PERJAWATAN KONTRAK JKN KEDAH SEHINGGA {$today}";
        $rows->push($row);

        // Row 2 – header
        $header = ['BIL', 'PUSAT TANGGUNGJAWAB', 'JUMLAH JAWATAN'];
        foreach ($this->columns as $column) {
            $header[] = $column['label'];
        }
        $rows->push($header);

        // Group units by program (sorted by program name, TANPA PROGRAM last)
        $groups = $units
            ->groupBy(fn ($unit) => $unit['programSort'])
            ->sortKeys(SORT_NATURAL)
            ->map(fn ($items) => $items->sortBy('label')->values());

        $grand = $this->emptyCounts();

        foreach ($groups as $items) {
            $first = $items->first();

            // Section header row (merged across all columns)
            $row = array_fill(0, $this->lastColumnIndex, '');
            $row[0] = $first['programLabel'];
            $rows->push($row);
            $this->sectionRows[] = $rows->count();

            $programCounts = $this->emptyCounts();
            $bil = 0;

            foreach ($items as $unit) {
                $bil++;
                $unitCounts = $counts[$unit['key']] ?? $this->emptyCounts();

                $row = array_fill(0, $this->lastColumnIndex, '');
                $row[0] = $bil;
                $row[1] = $unit['label'];
                $row[2] = $unitCounts['total'];
                for ($i = 0; $i < count($this->columns); $i++) {
                    $row[3 + $i] = $unitCounts['cols'][$i];
                }

                $rows->push($row);
                $this->dataRows[] = $rows->count();

                $this->addCounts($programCounts, $unitCounts);
                $this->addCounts($grand, $unitCounts);
            }

            // JUMLAH subtotal row (A:B merged)
            $row = array_fill(0, $this->lastColumnIndex, '');
            $row[0] = 'JUMLAH';
            $row[2] = $programCounts['total'];
            for ($i = 0; $i < count($this->columns); $i++) {
                $row[3 + $i] = $programCounts['cols'][$i];
            }
            $rows->push($row);
            $this->jumlahRows[] = $rows->count();
        }

        // Blank row, then grand total row
        $rows->push(array_fill(0, $this->lastColumnIndex, ''));

        $row = array_fill(0, $this->lastColumnIndex, '');
        $row[0] = 'JUMLAH KESELURUHAN';
        $row[2] = $grand['total'];
        for ($i = 0; $i < count($this->columns); $i++) {
            $row[3 + $i] = $grand['cols'][$i];
        }
        $rows->push($row);
        $this->totalRow = $rows->count();

        $this->rows = $rows->toArray();

        return $rows;
    }

    /**
     * @return Collection<int, Pegawai>
     */
    protected function loadKontrakPegawai(): Collection
    {
        return Pegawai::query()
            ->with([
                'ptj',
                'bahagian',
                'pegawaiKontrak.program',
                'jawatan_gred.jawatan',
                'jawatan_gred.gred',
            ])
            ->where('is_kontrak', 1)
            ->get();
    }

    /**
     * Green columns = distinct jawatan + gred on kontrak pegawai only.
     *
     * @param  Collection<int, Pegawai>  $pegawai
     * @return list<array{label: string, jawatanGredIds: list<int>}>
     */
    protected function buildColumnsFromKontrak(Collection $pegawai): array
    {
        $byJawatanGred = [];

        foreach ($pegawai as $p) {
            $jg = $p->jawatan_gred;
            $jawatan = $jg?->jawatan;
            $gred = $jg?->gred;

            if (! $jg || ! $jawatan || ! $gred) {
                continue;
            }

            $jgId = (int) $jg->id;
            $gredLabel = $gred->kod_gred;

            if (! filled($gredLabel)) {
                continue;
            }

            $byJawatanGred[$jgId] ??= [
                'label' => strtoupper(trim($jawatan->desc_jawatan.' '.$gredLabel)),
                'jawatanGredIds' => [$jgId],
                'jawatanSort' => mb_strtoupper($jawatan->desc_jawatan),
                'gredSort' => (int) preg_replace('/\D+/', '', $gredLabel) ?: 0,
            ];
        }

        return collect($byJawatanGred)
            ->sortBy([
                ['jawatanSort', 'asc'],
                ['gredSort', 'desc'],
            ])
            ->map(fn (array $column) => [
                'label' => $column['label'],
                'jawatanGredIds' => $column['jawatanGredIds'],
            ])
            ->values()
            ->all();
    }

    /**
     * Org units from waran_jawatans (plus any unit that has kontrak pegawai).
     * Non-JKN units are per PTJ; JKN units are per Bahagian.
     */
    protected function buildUnits()
    {
        $units = collect();

        $warans = WaranJawatan::with(['ptj', 'bahagian', 'aktiviti.program'])->get();
        foreach ($warans as $waran) {
            $unit = $this->unitFor($waran->ptj, $waran->bahagian);
            if ($unit && ! $units->has($unit['key'])) {
                $program = $waran->aktiviti?->program;
                $unit['programId'] = $program?->id;
                $unit['programLabel'] = $this->programLabel($program);
                $unit['programSort'] = $this->programSortKey($program);
                $units->put($unit['key'], $unit);
            }
        }

        return $units;
    }

    protected function isJkn($ptj): bool
    {
        return (bool) ($ptj && ($ptj->is_jkn || $ptj->nama_ptj === 'JABATAN KESIHATAN NEGERI KEDAH'));
    }

    protected function unitFor($ptj, $bahagian = null): ?array
    {
        if (! $ptj) {
            return null;
        }

        if ($this->isJkn($ptj)) {
            if (! $bahagian) {
                return null;
            }

            return [
                'key' => 'b'.$bahagian->id,
                'label' => $bahagian->nama_bahagian,
                'programId' => null,
                'programLabel' => 'TANPA PROGRAM',
                'programSort' => 'ZZZ',
            ];
        }

        return [
            'key' => 'p'.$ptj->id,
            'label' => $ptj->nama_ptj,
            'programId' => null,
            'programLabel' => 'TANPA PROGRAM',
            'programSort' => 'ZZZ',
        ];
    }

    protected function programLabel($program): string
    {
        if (! $program) {
            return 'TANPA PROGRAM';
        }

        // Same convention as DataKeseluruhanExport: PROGRAM 1 = ibu pejabat JKN
        if ($program->nama_program === 'PROGRAM 1') {
            return 'IBU PEJABAT JKN';
        }

        return "{$program->nama_program} : {$program->desc_program}";
    }

    protected function programSortKey($program): string
    {
        if (! $program) {
            return 'ZZZ';
        }

        return $program->nama_program;
    }

    /**
     * Count kontrak pegawai (is_kontrak = 1) per unit, per resolved column.
     */
    protected function countKontrak($units)
    {
        $counts = [];
        $pegawai = $this->kontrakPegawai ?? $this->loadKontrakPegawai();

        foreach ($pegawai as $p) {
            $unit = $this->unitFor($p->ptj, $p->bahagian);
            if (! $unit) {
                continue;
            }

            $program = $p->pegawaiKontrak?->program;
            if (! $units->has($unit['key'])) {
                $unit['programId'] = $program?->id;
                $unit['programLabel'] = $this->programLabel($program);
                $unit['programSort'] = $this->programSortKey($program);
                $units->put($unit['key'], $unit);
            } elseif ($program && ($units[$unit['key']]['programId'] ?? null) === null) {
                $existing = $units->get($unit['key']);
                $existing['programId'] = $program->id;
                $existing['programLabel'] = $this->programLabel($program);
                $existing['programSort'] = $this->programSortKey($program);
                $units->put($unit['key'], $existing);
            }

            $counts[$unit['key']] ??= $this->emptyCounts();
            $counts[$unit['key']]['total']++;

            $pJgId = (int) ($p->jawatan_gred_id ?? 0);
            if (! $pJgId) {
                continue;
            }

            foreach ($this->columns as $i => $column) {
                if (in_array($pJgId, $column['jawatanGredIds'], true)) {
                    $counts[$unit['key']]['cols'][$i]++;
                    break;
                }
            }
        }

        return $counts;
    }

    protected function emptyCounts(): array
    {
        return [
            'total' => 0,
            'cols' => array_fill(0, count($this->columns), 0),
        ];
    }

    protected function addCounts(array &$target, array $source): void
    {
        $target['total'] += $source['total'];
        foreach ($source['cols'] as $i => $value) {
            $target['cols'][$i] += $value;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastCol = Coordinate::stringFromColumnIndex(max(1, $this->lastColumnIndex));

                $this->styleTitle($sheet, $lastCol);
                $this->styleHeader($sheet, $lastCol);

                foreach ($this->sectionRows as $row) {
                    $this->styleSectionRow($sheet, $row, $lastCol);
                }

                foreach ($this->dataRows as $row) {
                    $this->styleDataRow($sheet, $row, $lastCol);
                }

                foreach ($this->jumlahRows as $row) {
                    $this->styleJumlahRow($sheet, $row, $lastCol);
                }

                $this->styleTotalRow($sheet, $this->totalRow, $lastCol);
            },
        ];
    }

    protected function styleTitle($sheet, string $lastCol): void
    {
        $sheet->mergeCells("A1:{$lastCol}1");

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(34);

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getColumnDimension('C')->setWidth(16);

        for ($column = 4; $column <= $this->lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(14);
        }
    }

    protected function styleHeader($sheet, string $lastCol): void
    {
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension(2)->setRowHeight(34);
    }

    protected function styleSectionRow($sheet, int $row, string $lastCol): void
    {
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9BC0E2'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    protected function styleDataRow($sheet, int $row, string $lastCol): void
    {
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle("C{$row}:{$lastCol}{$row}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$row}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    protected function styleJumlahRow($sheet, int $row, string $lastCol): void
    {
        $sheet->mergeCells("A{$row}:B{$row}");

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
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
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CA9EB3'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    protected function styleTotalRow($sheet, int $row, string $lastCol): void
    {
        $sheet->mergeCells("A{$row}:B{$row}");

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(24);
    }
}
