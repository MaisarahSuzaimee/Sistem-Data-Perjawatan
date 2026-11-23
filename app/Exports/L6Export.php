<?php

namespace App\Exports;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Program;
use App\Models\WaranJawatan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class L6Export implements FromCollection, WithCustomStartCell, WithEvents
{
    /** @var list<int> */
    public array $programRows = [];

    /** @var list<int> */
    public array $dataRows = [];

    /** @var list<int> */
    public array $jumlahRows = [];

    public int $totalRow = 0;

    protected string $title = '';

    public function __construct(
        protected int $jawatanId,
        protected array $gredIds,
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
        $this->totalRow = 0;

        $jawatan = Jawatan::query()->findOrFail($this->jawatanId);
        $greds = Gred::query()->whereIn('id', $this->gredIds)->get();
        abort_if($greds->count() !== count($this->gredIds), 404);
        $gredLabel = $greds
            ->sortBy(fn (Gred $gred) => (int) substr($gred->kod_gred, 1))
            ->pluck('kod_gred')
            ->implode('/');
        $countsByProgram = [];

        $warans = WaranJawatan::query()
            ->with(['aktiviti.program', 'pegawai', 'ptj'])
            ->whereJsonContains('jawatan_ids', $this->jawatanId)
            ->get()
            ->filter(fn (WaranJawatan $waran) => ($waran->status ?? 'active') !== 'removed');

        foreach ($warans as $waran) {
            $programId = $waran->aktiviti?->program_id;
            $ptjId = $waran->ptj_id;

            if (! $programId || ! $ptjId) {
                continue;
            }

            $waranGredIds = $this->normalizedGredIds($waran->gred_ids ?? []);
            if ($waranGredIds !== $this->gredIds) {
                continue;
            }

            $filled = $waran->pegawai
                && ($waran->pegawai->is_tetap || $waran->pegawai->is_kontrak_interim);

            $countsByProgram[$programId][$ptjId] ??= [
                'J' => 0,
                'I' => 0,
                'ptj' => $waran->ptj,
            ];
            $countsByProgram[$programId][$ptjId]['J']++;
            $countsByProgram[$programId][$ptjId]['I'] += $filled ? 1 : 0;
        }

        $this->title = strtoupper((string) $jawatan->desc_jawatan)
            .', GRED '.strtoupper($gredLabel);

        $programs = Program::query()
            ->with('ptjs')
            ->orderBy('nama_program')
            ->get();

        $rows = collect();
        $rowNumber = 4;
        $grand = ['J' => 0, 'I' => 0, 'K' => 0];
        $user = auth()->user();
        $scopedPtjId = $user && $user->isPtjScoped()
            ? (int) $user->ptj_id
            : null;

        foreach ($programs as $program) {
            $programCounts = $countsByProgram[$program->id] ?? [];
            $ptjs = $program->ptjs->keyBy('id');

            foreach ($programCounts as $ptjId => $counts) {
                if ($counts['ptj']) {
                    $ptjs->put($ptjId, $counts['ptj']);
                }
            }

            if ($scopedPtjId !== null) {
                $ptjs = $ptjs->filter(fn ($ptj) => (int) $ptj->id === $scopedPtjId);
                $programCounts = array_intersect_key($programCounts, [$scopedPtjId => true]);
            }

            if ($programCounts === []) {
                continue;
            }

            $ptjs = $ptjs->sortBy(fn ($ptj) => strtolower((string) $ptj->nama_ptj));
            $programLabel = trim($program->nama_program.($program->desc_program ? ' : '.$program->desc_program : ''));

            $rows->push([$programLabel]);
            $this->programRows[] = $rowNumber++;

            $subtotal = ['J' => 0, 'I' => 0, 'K' => 0];
            $bil = 0;

            foreach ($ptjs as $ptj) {
                $bil++;
                $counts = $programCounts[$ptj->id] ?? ['J' => 0, 'I' => 0];
                $counts['K'] = $counts['J'] - $counts['I'];

                foreach (['J', 'I', 'K'] as $key) {
                    $subtotal[$key] += $counts[$key];
                    $grand[$key] += $counts[$key];
                }

                $rows->push([
                    $bil,
                    strtoupper((string) $ptj->nama_ptj),
                    $counts['J'] > 0 ? $counts['J'] : '',
                    $counts['J'] > 0 ? $counts['I'] : '',
                    $counts['J'] > 0 ? $counts['K'] : '',
                ]);
                $this->dataRows[] = $rowNumber++;
            }

            $rows->push(['JUMLAH '.$program->nama_program, '', $subtotal['J'], $subtotal['I'], $subtotal['K']]);
            $this->jumlahRows[] = $rowNumber++;
        }

        $rows->push(['JUMLAH KESELURUHAN', '', $grand['J'], $grand['I'], $grand['K']]);
        $this->totalRow = $rowNumber;

        return $rows;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    /**
     * @param  list<int|string>|null  $gredIds
     * @return list<int>
     */
    protected function normalizedGredIds(?array $gredIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $gredIds ?? [])));
        sort($ids);

        return array_values(array_filter($ids, fn (int $id) => $id > 0));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max($sheet->getHighestRow(), $this->totalRow);
                $thin = [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ];

                $sheet->mergeCells('A1:B1');
                $sheet->mergeCells('C1:E1');
                $sheet->setCellValue('A1', 'MAKLUMAT PERJAWATAN');
                $sheet->setCellValue('C1', ': '.$this->title);
                $sheet->mergeCells('A2:B2');
                $sheet->mergeCells('C2:E2');
                $sheet->setCellValue('A2', 'DATA SEHINGGA');
                $sheet->setCellValue('C2', ': '.strtoupper(Carbon::now()->translatedFormat('d F Y')));
                $sheet->getStyle('A1:E2')->applyFromArray([
                    'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 12],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(24);

                foreach (['BIL', 'PTJ', 'JAWATAN', 'ISI', 'KOSONG'] as $index => $label) {
                    $sheet->setCellValueByColumnAndRow($index + 1, 3, $label);
                }
                $sheet->getStyle('A3:E3')->applyFromArray([
                    'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BFBFBF']],
                    'borders' => ['allBorders' => $thin],
                ]);

                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(32);
                foreach (['C', 'D', 'E'] as $column) {
                    $sheet->getColumnDimension($column)->setWidth(14);
                }

                foreach ($this->programRows as $row) {
                    $sheet->mergeCells("A{$row}:E{$row}");
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '9BC0E2']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => $thin],
                    ]);
                }

                foreach ($this->dataRows as $row) {
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'font' => ['name' => 'Calibri', 'size' => 11],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders' => ['allBorders' => $thin],
                    ]);
                    $sheet->getStyle("A{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$row}:E{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->jumlahRows as $row) {
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CA9EB3']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => $thin],
                    ]);
                }

                if ($this->totalRow > 0) {
                    $sheet->mergeCells("A{$this->totalRow}:B{$this->totalRow}");
                    $sheet->getStyle("A{$this->totalRow}:E{$this->totalRow}")->applyFromArray([
                        'font' => ['name' => 'Calibri', 'bold' => true, 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => $thin],
                    ]);
                }

                $sheet->freezePane('C4');
                $sheet->setAutoFilter('A3:E3');
                $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
                $sheet->getPageSetup()->setPrintArea("A1:E{$lastRow}");
            },
        ];
    }
}
