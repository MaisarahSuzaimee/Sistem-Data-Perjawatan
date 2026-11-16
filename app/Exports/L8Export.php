<?php

namespace App\Exports;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\WaranJawatan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
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

class L8Export extends DefaultValueBinder implements FromCollection, WithCustomStartCell, WithCustomValueBinder, WithEvents
{
    public function __construct(
        private int $aktivitiId,
    ) {}

    private int $lastRow = 5;

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        $waranJawatans = WaranJawatan::query()
            ->listed()
            ->where(function (Builder $query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'removed');
            })
            ->where('aktiviti_id', $this->aktivitiId)
            ->with([
                'aktiviti.program',
                'ptj',
                'bahagian',
                'unit',
                'subunit',
                'pegawai.ptj',
                'pegawai.bahagian',
                'pegawai.unit',
                'pegawai.subunit',
                'pegawai.jawatan_gred.gred',
                'jawatanGred.jawatan',
                'jawatanGred.gred',
            ])
            ->get()
            ->sortBy(
                fn (WaranJawatan $waranJawatan): string => strtolower(implode(' ', [
                    $waranJawatan->aktiviti?->program?->nama_program ?? '',
                    $waranJawatan->aktiviti?->no_aktivit ?? '',
                    $waranJawatan->aktiviti?->nama_aktiviti ?? '',
                    $waranJawatan->butiran ?? '',
                ])),
                SORT_NATURAL | SORT_FLAG_CASE,
            )
            ->values();

        $jawatanIds = $waranJawatans
            ->flatMap(fn (WaranJawatan $waranJawatan): array => $waranJawatan->jawatan_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
        $gredIds = $waranJawatans
            ->flatMap(fn (WaranJawatan $waranJawatan): array => $waranJawatan->gred_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $jawatanNames = Jawatan::query()
            ->whereIn('id', $jawatanIds)
            ->pluck('desc_jawatan', 'id');
        $gredNames = Gred::query()
            ->whereIn('id', $gredIds)
            ->pluck('kod_gred', 'id');

        $rows = $waranJawatans->map(function (WaranJawatan $waranJawatan) use ($jawatanNames, $gredNames): array {
            $pegawai = $waranJawatan->pegawai;
            $assigned = $waranJawatan->pegawai_id !== null;
            $jawatan = trim((string) $waranJawatan->paparan_jawatan);

            if ($jawatan === '') {
                $jawatan = $this->labelsForIds($waranJawatan->jawatan_ids, $jawatanNames);
                $jawatan = $jawatan ?: (string) $waranJawatan->jawatanGred?->jawatan?->desc_jawatan;
            }

            $gredWaran = $this->labelsForIds($waranJawatan->gred_ids, $gredNames, '/');
            $gredWaran = $gredWaran ?: (string) $waranJawatan->jawatanGred?->gred?->kod_gred;

            $isBorrowed = $pegawai
                && $pegawai->ptj_id !== null
                && $waranJawatan->ptj_id !== null
                && (int) $pegawai->ptj_id !== (int) $waranJawatan->ptj_id;

            $activity = $waranJawatan->aktiviti;
            $activityLabel = trim(implode(' - ', array_filter([
                $activity?->no_aktivit,
                $activity?->nama_aktiviti,
            ])));
            $programName = $activity?->program?->nama_program;

            if (filled($programName) && filled($activityLabel)) {
                $activityLabel = $programName.' / '.$activityLabel;
            } elseif (filled($programName)) {
                $activityLabel = $programName;
            }

            $isContract = $pegawai && (
                $pegawai->is_kontrak
                || $pegawai->is_kontrak_interim
                || $pegawai->is_kontrak_isi_tetap
            );

            return [
                $activityLabel,
                $waranJawatan->butiran,
                $jawatan,
                $gredWaran,
                $pegawai?->jawatan_gred?->gred?->kod_gred,
                1,
                $assigned ? 1 : 0,
                $assigned ? 0 : 1,
                $pegawai?->is_tetap ? 1 : '',
                $isContract ? 1 : '',
                $pegawai?->nama,
                $pegawai?->nokp,
                $pegawai?->jantina,
                $pegawai ? $this->placementLabel([
                    $pegawai->ptj?->nama_ptj,
                    $pegawai->bahagian?->nama_bahagian,
                    $pegawai->unit?->nama_unit,
                    $pegawai->subunit?->nama_subunit,
                ]) : '',
                $isBorrowed ? $activityLabel : '',
                $isBorrowed ? $waranJawatan->penempatanLabel : '',
                $waranJawatan->catatan_jawatan,
            ];
        });

        $this->lastRow = 5 + $rows->count();

        return $rows;
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'L' && $cell->getRow() >= 6 && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function startCell(): string
    {
        return 'A6';
    }

    /**
     * @param  array<int, int|string>|null  $ids
     */
    private function labelsForIds(?array $ids, Collection $labels, string $separator = ', '): string
    {
        return collect($ids ?? [])
            ->map(fn ($id) => $labels->get((int) $id))
            ->filter(fn ($label): bool => filled($label))
            ->implode($separator);
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $thin = [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ];

                $sheet->mergeCells('A1:Q1');
                $sheet->setCellValue('A1', 'KEDUDUKAN PERJAWATAN DAN PENGISIAN MENGIKUT PROGRAM / AKTIVITI');
                $sheet->mergeCells('A2:Q2');
                $sheet->setCellValue('A2', 'JABATAN KESIHATAN NEGERI KEDAH DARUL AMAN (BAHAGIAN PENGURUSAN)');
                $sheet->getStyle('A1:Q2')->applyFromArray([
                    'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 12],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(20);

                $verticalHeaders = [
                    'A' => 'AKTIVITI',
                    'B' => 'BUTIRAN',
                    'C' => 'JAWATAN',
                    'D' => 'GRED WARAN',
                    'E' => 'GRED PEGAWAI',
                    'F' => 'JAWATAN',
                    'G' => 'ISI',
                    'H' => 'KSG (J - I)',
                    'K' => 'NAMA PENYANDANG',
                    'L' => 'NO KAD PENGENALAN',
                    'M' => 'JANTINA (L/P)',
                    'N' => 'PENEMPATAN (UNIT)',
                    'Q' => 'CATATAN',
                ];

                foreach ($verticalHeaders as $column => $label) {
                    $sheet->mergeCells("{$column}4:{$column}5");
                    $sheet->setCellValue("{$column}4", $label);
                }

                $sheet->mergeCells('I4:J4');
                $sheet->setCellValue('I4', 'STATUS PENGISIAN');
                $sheet->setCellValue('I5', 'TETAP');
                $sheet->setCellValue('J5', 'KONTRAK');
                $sheet->mergeCells('O4:P4');
                $sheet->setCellValue('O4', 'PINJAM KE');
                $sheet->setCellValue('O5', 'AKTIVITI');
                $sheet->setCellValue('P5', 'PENEMPATAN');

                $sheet->getStyle('A4:G5')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6E0F5']],
                ]);
                $sheet->getStyle('H4:Q5')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A6A6A6']],
                ]);
                $sheet->getStyle('A4:Q5')->applyFromArray([
                    'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => ['allBorders' => $thin],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(30);
                $sheet->getRowDimension(5)->setRowHeight(30);

                $widths = [
                    'A' => 24, 'B' => 20, 'C' => 32, 'D' => 14, 'E' => 14,
                    'F' => 10, 'G' => 8, 'H' => 12, 'I' => 10, 'J' => 12,
                    'K' => 30, 'L' => 20, 'M' => 14, 'N' => 30, 'O' => 26,
                    'P' => 30, 'Q' => 30,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                if ($this->lastRow >= 6) {
                    $sheet->getStyle("A6:Q{$this->lastRow}")->applyFromArray([
                        'font' => ['name' => 'Calibri', 'size' => 10],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getStyle("F6:J{$this->lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->freezePane('A6');
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setPrintArea("A1:Q{$this->lastRow}");
                $sheet->setShowGridlines(false);
            },
        ];
    }
}
