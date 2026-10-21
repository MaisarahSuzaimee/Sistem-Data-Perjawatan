<?php

namespace App\Exports;

use App\Models\Ptj;
use App\Models\Subunit;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FasilitiByParlimenExport implements FromCollection, WithCustomStartCell, WithEvents
{
    /**
     * @var array<int, array{start: int, end: int}>
     */
    public array $parlimenMergeRanges = [];

    /**
     * @var array<int, array{start: int, end: int}>
     */
    public array $dunMergeRanges = [];

    /**
     * @var array<int, array{start: int, end: int}>
     */
    public array $bilMergeRanges = [];

    public function startCell(): string
    {
        return 'A1';
    }

    public function collection(): Collection
    {
        $facilities = $this->facilityRows();

        $rows = collect([
            ['Senarai Fasiliti Mengikut Parlimen dan Dun', '', '', ''],
            ['Bil', 'Parlimen', 'Dun', 'Fasiliti (PTJ / Unit / Subunit)'],
        ]);

        $excelRow = 3;
        $bil = 0;
        $currentParlimenId = null;
        $currentDunId = null;
        $parlimenStartRow = null;
        $dunStartRow = null;
        $bilStartRow = null;

        foreach ($facilities as $facility) {
            $parlimenId = $facility['parlimen_id'];
            $dunId = $facility['dun_id'];

            if ($parlimenId !== $currentParlimenId) {
                if ($currentDunId !== null && $dunStartRow !== null && $excelRow - 1 > $dunStartRow) {
                    $this->dunMergeRanges[] = ['start' => $dunStartRow, 'end' => $excelRow - 1];
                }

                if ($currentParlimenId !== null && $parlimenStartRow !== null && $excelRow - 1 > $parlimenStartRow) {
                    $this->parlimenMergeRanges[] = ['start' => $parlimenStartRow, 'end' => $excelRow - 1];
                    $this->bilMergeRanges[] = ['start' => $bilStartRow, 'end' => $excelRow - 1];
                }

                $currentParlimenId = $parlimenId;
                $currentDunId = $dunId;
                $parlimenStartRow = $excelRow;
                $dunStartRow = $excelRow;
                $bilStartRow = $excelRow;
                $bil++;
                $showBil = (string) $bil;
                $showParlimen = $facility['parlimen'];
                $showDun = $facility['dun'];
            } elseif ($dunId !== $currentDunId) {
                if ($currentDunId !== null && $dunStartRow !== null && $excelRow - 1 > $dunStartRow) {
                    $this->dunMergeRanges[] = ['start' => $dunStartRow, 'end' => $excelRow - 1];
                }

                $currentDunId = $dunId;
                $dunStartRow = $excelRow;
                $showBil = '';
                $showParlimen = '';
                $showDun = $facility['dun'];
            } else {
                $showBil = '';
                $showParlimen = '';
                $showDun = '';
            }

            $rows->push([
                $showBil,
                $showParlimen,
                $showDun,
                $facility['fasiliti'],
            ]);

            $excelRow++;
        }

        if ($currentDunId !== null && $dunStartRow !== null && $excelRow - 1 > $dunStartRow) {
            $this->dunMergeRanges[] = ['start' => $dunStartRow, 'end' => $excelRow - 1];
        }

        if ($currentParlimenId !== null && $parlimenStartRow !== null && $excelRow - 1 > $parlimenStartRow) {
            $this->parlimenMergeRanges[] = ['start' => $parlimenStartRow, 'end' => $excelRow - 1];
            $this->bilMergeRanges[] = ['start' => $bilStartRow, 'end' => $excelRow - 1];
        }

        return $rows;
    }

    /**
     * @return Collection<int, array{
     *     parlimen_id: int,
     *     dun_id: int,
     *     parlimen: string,
     *     dun: string,
     *     fasiliti: string,
     *     sort_type: int,
     *     sort_name: string
     * }>
     */
    public function facilityRows(): Collection
    {
        $rows = collect();

        $ptjs = Ptj::query()
            ->with(['parlimen', 'dun'])
            ->whereNotNull('parlimen_id')
            ->whereNotNull('dun_id')
            ->orderBy('nama_ptj')
            ->get();

        foreach ($ptjs as $ptj) {
            $rows->push([
                'parlimen_id' => (int) $ptj->parlimen_id,
                'dun_id' => (int) $ptj->dun_id,
                'parlimen' => (string) ($ptj->parlimen?->nama_parlimen ?? ''),
                'dun' => (string) ($ptj->dun?->nama_dun ?? ''),
                'fasiliti' => $ptj->nama_ptj,
                'sort_type' => 1,
                'sort_name' => $ptj->nama_ptj,
            ]);
        }

        $units = Unit::query()
            ->with(['parlimen', 'dun', 'ptj'])
            ->whereNotNull('parlimen_id')
            ->whereNotNull('dun_id')
            ->whereHas('ptj', fn ($query) => $query->where('is_jkn', false))
            ->orderBy('nama_unit')
            ->get();

        foreach ($units as $unit) {
            $rows->push([
                'parlimen_id' => (int) $unit->parlimen_id,
                'dun_id' => (int) $unit->dun_id,
                'parlimen' => (string) ($unit->parlimen?->nama_parlimen ?? ''),
                'dun' => (string) ($unit->dun?->nama_dun ?? ''),
                'fasiliti' => $unit->nama_unit,
                'sort_type' => 2,
                'sort_name' => $unit->nama_unit,
            ]);
        }

        $subunits = Subunit::query()
            ->with(['parlimen', 'dun', 'unit.ptj'])
            ->whereNotNull('parlimen_id')
            ->whereNotNull('dun_id')
            ->whereHas('unit.ptj', fn ($query) => $query->where('is_jkn', false))
            ->orderBy('nama_subunit')
            ->get();

        foreach ($subunits as $subunit) {
            $rows->push([
                'parlimen_id' => (int) $subunit->parlimen_id,
                'dun_id' => (int) $subunit->dun_id,
                'parlimen' => (string) ($subunit->parlimen?->nama_parlimen ?? ''),
                'dun' => (string) ($subunit->dun?->nama_dun ?? ''),
                'fasiliti' => $subunit->nama_subunit,
                'sort_type' => 3,
                'sort_name' => $subunit->nama_subunit,
            ]);
        }

        return $rows
            ->sortBy([
                ['parlimen', 'asc'],
                ['dun', 'asc'],
                ['sort_type', 'asc'],
                ['sort_name', 'asc'],
            ])
            ->values();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max($sheet->getHighestRow(), 2);

                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E79'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                foreach ($this->parlimenMergeRanges as $range) {
                    $sheet->mergeCells("B{$range['start']}:B{$range['end']}");
                }

                foreach ($this->dunMergeRanges as $range) {
                    $sheet->mergeCells("C{$range['start']}:C{$range['end']}");
                }

                foreach ($this->bilMergeRanges as $range) {
                    $sheet->mergeCells("A{$range['start']}:A{$range['end']}");
                }

                $sheet->getStyle("A2:D{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A3:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B3:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach (range('A', 'D') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(30);
            },
        ];
    }
}
