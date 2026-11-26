<?php

namespace App\Exports;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\WaranJawatan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class L7Export extends DefaultValueBinder implements FromCollection, WithCustomValueBinder, WithEvents
{
    /** @var list<int> */
    public array $programRows = [];

    /** @var list<int> */
    public array $dataRows = [];

    /** @var list<int> */
    public array $jumlahRows = [];

    /** @var list<int> */
    public array $programSummaryRows = [];

    public int $grandTotalRow = 0;

    public int $lastRow = 4;

    public const LAST_COL = 'T';

    public const COLUMN_COUNT = 20;

    protected string $titleLine1 = '';

    protected string $titleLine2 = '';

    public function __construct(
        private int $jawatanId,
        /** @var list<int> */
        private array $gredIds,
    ) {}

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        Carbon::setLocale('ms');
        $this->programRows = [];
        $this->dataRows = [];
        $this->jumlahRows = [];
        $this->programSummaryRows = [];
        $this->grandTotalRow = 0;

        $jawatan = Jawatan::query()->findOrFail($this->jawatanId);
        $gredLabels = Gred::query()
            ->whereIn('id', $this->gredIds)
            ->get()
            ->sortBy(fn (Gred $gred): int => (int) substr($gred->kod_gred, 1))
            ->pluck('kod_gred')
            ->implode('/');
        $this->titleLine1 = 'MAKLUMAT PERJAWATAN DAN PENYANDANG BAGI JAWATAN '
            .mb_strtoupper(trim((string) $jawatan->desc_jawatan))
            .' GRED '.mb_strtoupper($gredLabels);
        $this->titleLine2 = 'DI '.$this->headerLocationName()
            .' SEHINGGA '.strtoupper(Carbon::now()->translatedFormat('d F Y'));

        $waranQuery = WaranJawatan::query()
            ->listed()
            ->whereJsonContains('jawatan_ids', $this->jawatanId);

        foreach ($this->gredIds as $gredId) {
            $waranQuery->whereJsonContains('gred_ids', $gredId);
        }

        $user = auth()->user();
        if ($user && $user->isUser() && filled($user->ptj_id)) {
            $waranQuery->where(function ($query) use ($user): void {
                $query->where('ptj_id', $user->ptj_id)
                    ->orWhereHas('pegawai', fn ($pegawaiQuery) => $pegawaiQuery
                        ->where('ptj_id', $user->ptj_id));
            });
        }

        $waranJawatans = $waranQuery
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'removed');
            })
            ->with([
                'waran',
                'aktiviti.program',
                'ptj',
                'bahagian',
                'unit.dun',
                'unit.parlimen',
                'subunit.dun',
                'subunit.parlimen',
                'pegawai.jawatan_gred.jawatan',
                'pegawai.jawatan_gred.gred',
                'pegawai.opsyenPencen',
                'pegawai.ptj',
                'pegawai.bahagian',
                'pegawai.unit.dun',
                'pegawai.unit.parlimen',
                'pegawai.subunit.dun',
                'pegawai.subunit.parlimen',
                'jawatanGred.jawatan',
                'jawatanGred.gred',
            ])
            ->get()
            ->sortBy(function (WaranJawatan $waran): string {
                $program = $waran->aktiviti?->program;
                $programName = $program?->nama_program ?? '';
                $activity = $waran->aktiviti?->no_aktivit ?? '';
                $butiran = $waran->butiran ?? '';

                return strtolower(implode('|', [$programName, $activity, $butiran]));
            }, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $jawatanIds = $waranJawatans
            ->flatMap(fn (WaranJawatan $waran): array => $this->normalizedIds($waran->jawatan_ids))
            ->unique()
            ->values();
        $gredIds = $waranJawatans
            ->flatMap(fn (WaranJawatan $waran): array => $this->normalizedIds($waran->gred_ids))
            ->unique()
            ->values();

        $jawatanNames = Jawatan::query()->whereIn('id', $jawatanIds)->pluck('desc_jawatan', 'id');
        $gredNames = Gred::query()->whereIn('id', $gredIds)->pluck('kod_gred', 'id');

        $rows = collect([
            $this->padRow([$this->titleLine1]),
            $this->padRow([$this->titleLine2]),
            $this->headerRow(),
        ]);

        $programGroups = $waranJawatans->groupBy(function (WaranJawatan $waran): string {
            $program = $waran->aktiviti?->program;

            return $program
                ? (string) $program->id
                : '0';
        });

        $grand = ['J' => 0, 'I' => 0, 'K' => 0];
        $programSummaries = [];

        foreach ($programGroups as $programId => $items) {
            $program = $items->first()?->aktiviti?->program;
            $programName = trim((string) ($program?->nama_program ?? 'TANPA PROGRAM'));
            $programLabel = $programId !== '0'
                ? trim($programName.($program?->desc_program ? ' : '.$program->desc_program : ''))
                : 'TANPA PROGRAM';

            $rows->push($this->padRow([$programLabel]));
            $this->programRows[] = $rows->count();
            $programSubtotal = ['J' => 0, 'I' => 0, 'K' => 0];
            $bil = 0;

            $ptjGroups = $items->groupBy(fn (WaranJawatan $waran): int => (int) ($waran->ptj_id ?? 0));

            foreach ($ptjGroups as $ptjId => $ptjItems) {
                $ptjSubtotal = ['J' => 0, 'I' => 0, 'K' => 0];
                $ptjName = trim((string) ($ptjItems->first()?->ptj?->nama_ptj ?? ''));

                foreach ($ptjItems as $waran) {
                    $bil++;
                    $pegawai = $waran->pegawai;
                    $jawatan = $this->jawatanLabel($waran, $jawatanNames);
                    $gredWaran = $this->gredLabel($waran, $gredNames);
                    $gredPegawai = trim((string) ($pegawai?->jawatan_gred?->gred?->kod_gred ?? ''));
                    $isi = $pegawai ? 1 : 0;

                    $placement = $pegawai
                        ? $this->placementLabel([
                            $pegawai->ptj?->nama_ptj,
                            $pegawai->bahagian?->nama_bahagian,
                            $pegawai->unit?->nama_unit,
                            $pegawai->subunit?->nama_subunit,
                        ])
                        : $this->placementLabel([
                            $waran->ptj?->nama_ptj,
                            $waran->bahagian?->nama_bahagian,
                            $waran->unit?->nama_unit,
                            $waran->subunit?->nama_subunit,
                        ]);

                    $locationSource = $pegawai ?? $waran;
                    [$dun, $parlimen] = $this->locationLabels($locationSource);

                    $opsyen = trim((string) ($pegawai?->opsyenPencen?->opsyen ?? ''));

                    $rows->push([
                        $bil,
                        trim((string) ($waran->waran?->no_waran ?? '')),
                        trim((string) ($waran->aktiviti?->no_aktivit ?? '')),
                        trim((string) ($waran->butiran ?? '')),
                        $jawatan,
                        $gredWaran,
                        $gredPegawai,
                        1,
                        $isi,
                        1 - $isi,
                        trim((string) ($pegawai?->nama ?? '')),
                        trim((string) ($pegawai?->nokp ?? '')),
                        $placement,
                        $dun,
                        $parlimen,
                        $this->dateLabel($pegawai?->tarikh_lantikan),
                        $this->dateLabel($pegawai?->tarikh_sah_jawatan),
                        $opsyen,
                        $this->dateLabel($pegawai?->tarikh_pencen),
                        trim((string) ($waran->catatan_jawatan ?? '')),
                    ]);
                    $this->dataRows[] = $rows->count();

                    foreach (['J', 'I', 'K'] as $key) {
                        $value = $key === 'J' ? 1 : ($key === 'I' ? $isi : 1 - $isi);
                        $ptjSubtotal[$key] += $value;
                        $programSubtotal[$key] += $value;
                        $grand[$key] += $value;
                    }
                }

                $ptjLabel = $ptjName !== ''
                    ? 'JUMLAH DI '.$ptjName
                    : 'JUMLAH DI PTJ';
                $rows->push($this->subtotalRow($ptjLabel, $ptjSubtotal));
                $this->jumlahRows[] = $rows->count();
            }

            $rows->push($this->subtotalRow('JUMLAH '.mb_strtoupper($programName), $programSubtotal));
            $this->jumlahRows[] = $rows->count();

            $programSummaries[] = [
                'label' => $programId !== '0' ? $programName : 'TANPA PROGRAM',
                'counts' => $programSubtotal,
            ];
        }

        // Gap, then jumlah by program, then grand total.
        $rows->push($this->padRow(['']));

        foreach ($programSummaries as $summary) {
            $rows->push($this->subtotalRow($summary['label'], $summary['counts']));
            $this->programSummaryRows[] = $rows->count();
        }

        $rows->push($this->subtotalRow('JUMLAH KESELURUHAN', $grand));
        $this->grandTotalRow = $rows->count();
        $this->lastRow = $rows->count();

        return $rows;
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getRow() >= 4 && $cell->getColumn() === 'L' && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
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

    /**
     * @param  Collection<int, string>  $jawatanNames
     */
    private function jawatanLabel(WaranJawatan $waran, Collection $jawatanNames): string
    {
        $paparan = trim((string) $waran->paparan_jawatan);
        if ($paparan !== '') {
            return $paparan;
        }

        $label = collect($this->normalizedIds($waran->jawatan_ids))
            ->map(fn (int $id): ?string => $jawatanNames->get($id)
                ?? ((int) $waran->jawatanGred?->jawatan_id === $id
                    ? $waran->jawatanGred?->jawatan?->desc_jawatan
                    : null))
            ->filter()
            ->unique()
            ->implode(', ');

        return $label ?: trim((string) ($waran->jawatanGred?->jawatan?->desc_jawatan ?? ''));
    }

    /**
     * @param  Collection<int, string>  $gredNames
     */
    private function gredLabel(WaranJawatan $waran, Collection $gredNames): string
    {
        $label = collect($this->normalizedIds($waran->gred_ids))
            ->map(fn (int $id): ?string => $gredNames->get($id)
                ?? ((int) $waran->jawatanGred?->gred_id === $id
                    ? $waran->jawatanGred?->gred?->kod_gred
                    : null))
            ->filter()
            ->unique()
            ->implode('/');

        return $label ?: trim((string) ($waran->jawatanGred?->gred?->kod_gred ?? ''));
    }

    /**
     * @param  array<int, string|null>  $segments
     */
    private function placementLabel(array $segments): string
    {
        return collect($segments)
            ->filter(fn ($segment): bool => filled($segment))
            ->implode(' / ');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function locationLabels($source): array
    {
        $subunit = $source->subunit ?? null;
        $unit = $source->unit ?? null;
        $bahagian = $source->bahagian ?? null;
        $ptj = $source->ptj ?? null;

        $dun = $subunit?->dun ?? $unit?->dun ?? $bahagian?->dun ?? $ptj?->dun ?? null;
        $parlimen = $subunit?->parlimen ?? $unit?->parlimen ?? $bahagian?->parlimen ?? $ptj?->parlimen ?? null;

        return [
            trim((string) ($dun?->nama_dun ?? '')),
            trim((string) ($parlimen?->nama_parlimen ?? '')),
        ];
    }

    private function dateLabel(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->translatedFormat('d.m.Y');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function headerLocationName(): string
    {
        $user = auth()->user();

        if ($user && $user->isUser() && $user->ptj_id) {
            $ptjName = trim((string) ($user->ptj?->nama_ptj ?? ''));

            if ($ptjName !== '') {
                return mb_strtoupper($ptjName);
            }
        }

        return 'JABATAN KESIHATAN NEGERI KEDAH';
    }

    /**
     * @param  list<mixed>  $row
     * @return list<mixed>
     */
    private function padRow(array $row): array
    {
        $row = array_values($row);

        while (count($row) < self::COLUMN_COUNT) {
            $row[] = '';
        }

        return array_slice($row, 0, self::COLUMN_COUNT);
    }

    /**
     * @return list<string>
     */
    private function headerRow(): array
    {
        return [
            'BIL',
            'PUNCA KUASA',
            'AKT.',
            'BUT.',
            'JAWATAN',
            'GRED WARAN',
            'GRED PEGAWAI',
            'JUM. JWT',
            'ISI',
            'KSG',
            'NAMA PENYANDANG',
            'NO. K/P / PASSPORT',
            'PENEMPATAN (UNIT)',
            'DUN',
            'PARLIMEN',
            'TARIKH LANTIK',
            'TARIKH SAH',
            'TARIKH BERSARA WAJIB (OPSYEN PENCEN 55/56/58/60)',
            'TARIKH DIJANGKA BERSARA',
            'Catatan',
        ];
    }

    /**
     * @param  array{J: int, I: int, K: int}  $totals
     * @return list<mixed>
     */
    private function subtotalRow(string $label, array $totals): array
    {
        $row = array_fill(0, self::COLUMN_COUNT, '');
        $row[0] = $label;
        $row[7] = $totals['J'];
        $row[8] = $totals['I'];
        $row[9] = $totals['K'];

        return $row;
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

                $lastCol = self::LAST_COL;

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A1', $this->titleLine1);
                $sheet->setCellValue('A2', $this->titleLine2);

                $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(42);

                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 8],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => ['allBorders' => $thin],
                ]);

                foreach ($this->programRows as $row) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7F5A36']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                foreach ($this->dataRows as $row) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 8],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getStyle("A{$row}:J{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("N{$row}:S{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension($row)->setRowHeight(42);
                }

                foreach ($this->jumlahRows as $row) {
                    $sheet->mergeCells("A{$row}:G{$row}");
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'bold' => true, 'size' => 8],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BF9000']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                $summaryStyle = [
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'size' => 8,
                        'color' => ['rgb' => 'FFFF00'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '595959'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => ['allBorders' => $thin],
                ];

                foreach ($this->programSummaryRows as $row) {
                    $sheet->mergeCells("A{$row}:G{$row}");
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($summaryStyle);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                if ($this->grandTotalRow > 0) {
                    $sheet->mergeCells("A{$this->grandTotalRow}:G{$this->grandTotalRow}");
                    $sheet->getStyle("A{$this->grandTotalRow}:{$lastCol}{$this->grandTotalRow}")
                        ->applyFromArray($summaryStyle);
                    $sheet->getRowDimension($this->grandTotalRow)->setRowHeight(22);
                }

                $widths = [
                    'A' => 8, 'B' => 15, 'C' => 9, 'D' => 10, 'E' => 18,
                    'F' => 12, 'G' => 13, 'H' => 10, 'I' => 8, 'J' => 8,
                    'K' => 28, 'L' => 20, 'M' => 28, 'N' => 14, 'O' => 16,
                    'P' => 14, 'Q' => 14, 'R' => 18, 'S' => 16, 'T' => 22,
                ];
                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->freezePane('A4');
                $sheet->setShowGridlines(false);
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A3)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$this->lastRow}");
            },
        ];
    }
}
