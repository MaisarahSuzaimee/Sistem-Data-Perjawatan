<?php

use App\Exports\L8Export;
use App\Filament\Pages\Report;
use App\Models\Aktiviti;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\Pegawai;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\WaranJawatan;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    foreach ([
        'waran_jawatans',
        'pegawais',
        'jawatan__greds',
        'jawatans',
        'greds',
        'aktivitis',
        'programs',
        'units',
        'bahagians',
        'ptjs',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('programs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_program');
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->string('no_aktivit')->nullable();
        $table->string('nama_aktiviti')->nullable();
        $table->string('desc_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->string('kod_ptj')->nullable();
        $table->boolean('is_jkn')->default(false);
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama_bahagian')->nullable();
        $table->timestamps();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('subunits', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->string('nama_subunit')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatans', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_jawatan')->nullable();
        $table->string('desc_jawatan')->nullable();
        $table->timestamps();
    });

    Schema::create('greds', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_gred')->nullable();
        $table->string('desc_gred')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatan__greds', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('jawatan_id');
        $table->unsignedBigInteger('gred_id');
        $table->unsignedBigInteger('kumpulan_id')->nullable();
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->unsignedBigInteger('subunit_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->unsignedBigInteger('opsyen_pencen_id')->nullable();
        $table->string('nama');
        $table->string('nokp');
        $table->string('jantina');
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->boolean('is_kontrak_isi_tetap')->default(false);
        $table->date('tarikh_pinjam')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->default(1);
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->unsignedBigInteger('subunit_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->string('butiran')->nullable();
        $table->string('paparan_jawatan')->nullable();
        $table->boolean('is_kup')->default(false);
        $table->text('catatan_jawatan')->nullable();
        $table->string('status')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

it('exports active waran rows with counts, linked pegawai data, and grouped headers', function () {
    $program = Program::query()->create(['nama_program' => 'PROGRAM 1']);
    $aktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'no_aktivit' => '01',
        'nama_aktiviti' => 'PENGURUSAN HOSPITAL',
    ]);
    $otherAktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'no_aktivit' => '02',
        'nama_aktiviti' => 'PENGURUSAN KLINIK',
    ]);
    $otherProgram = Program::query()->create(['nama_program' => 'PROGRAM 2']);
    $otherProgramAktiviti = Aktiviti::query()->create([
        'program_id' => $otherProgram->id,
        'no_aktivit' => '01',
        'nama_aktiviti' => 'PENGURUSAN FARMASI',
    ]);
    $sourcePtj = Ptj::query()->create(['nama_ptj' => 'PTJ ASAL']);
    $waranPtj = Ptj::query()->create(['nama_ptj' => 'PTJ WARAN']);
    $sourceUnit = Unit::query()->create([
        'ptj_id' => $sourcePtj->id,
        'nama_unit' => 'UNIT ASAL',
    ]);
    $waranUnit = Unit::query()->create([
        'ptj_id' => $waranPtj->id,
        'nama_unit' => 'UNIT WARAN',
    ]);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'JURURAWAT']);
    $gred = Gred::query()->create(['kod_gred' => 'U9']);
    $jawatanGred = Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
    ]);
    $pegawai = Pegawai::query()->create([
        'ptj_id' => $sourcePtj->id,
        'unit_id' => $sourceUnit->id,
        'jawatan_gred_id' => $jawatanGred->id,
        'nama' => 'NAMA PEGAWAI',
        'nokp' => '001234567890',
        'jantina' => 'Perempuan',
        'is_kontrak_interim' => true,
    ]);

    WaranJawatan::query()->create([
        'aktiviti_id' => $aktiviti->id,
        'ptj_id' => $waranPtj->id,
        'unit_id' => $waranUnit->id,
        'pegawai_id' => $pegawai->id,
        'jawatan_gred_id' => $jawatanGred->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
        'butiran' => 'A - DIISI',
        'catatan_jawatan' => 'CATATAN WARAN',
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'aktiviti_id' => $aktiviti->id,
        'ptj_id' => $waranPtj->id,
        'unit_id' => $waranUnit->id,
        'jawatan_gred_id' => $jawatanGred->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
        'butiran' => 'B - KOSONG',
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'aktiviti_id' => $aktiviti->id,
        'ptj_id' => $waranPtj->id,
        'butiran' => 'REMOVED',
        'status' => 'removed',
    ]);
    WaranJawatan::query()->create([
        'aktiviti_id' => $aktiviti->id,
        'ptj_id' => $waranPtj->id,
        'butiran' => 'DELETED',
        'status' => 'active',
    ])->delete();
    WaranJawatan::query()->create([
        'aktiviti_id' => $otherAktiviti->id,
        'ptj_id' => $waranPtj->id,
        'butiran' => 'OTHER ACTIVITY',
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'aktiviti_id' => $otherProgramAktiviti->id,
        'ptj_id' => $waranPtj->id,
        'butiran' => 'OTHER PROGRAM',
        'status' => 'active',
    ]);

    $export = new L8Export($aktiviti->id);
    $rows = $export->collection();
    $report = (new ReflectionClass(Report::class))->newInstanceWithoutConstructor();
    $programOptionsMethod = new ReflectionMethod(Report::class, 'l8ProgramOptions');
    $programOptionsMethod->setAccessible(true);
    $programOptions = $programOptionsMethod->invoke($report);
    $activityOptionsMethod = new ReflectionMethod(Report::class, 'l8ActivityOptions');
    $activityOptionsMethod->setAccessible(true);
    $activityOptions = $activityOptionsMethod->invoke($report, $program->id);

    expect($rows)->toHaveCount(2)
        ->and($programOptions)->toBe([
            $program->id => 'PROGRAM 1',
            $otherProgram->id => 'PROGRAM 2',
        ])
        ->and($activityOptions)->toBe([
            $aktiviti->id => '01 - PENGURUSAN HOSPITAL',
            $otherAktiviti->id => '02 - PENGURUSAN KLINIK',
        ])
        ->and($rows[0])->toBe([
            'PROGRAM 1 / 01 - PENGURUSAN HOSPITAL',
            'A - DIISI',
            'JURURAWAT',
            'U9',
            'U9',
            1,
            1,
            0,
            '',
            1,
            'NAMA PEGAWAI',
            '001234567890',
            'Perempuan',
            'PTJ ASAL / UNIT ASAL',
            'PROGRAM 1 / 01 - PENGURUSAN HOSPITAL',
            'PTJ WARAN / UNIT WARAN',
            'CATATAN WARAN',
        ])
        ->and($rows[1][5])->toBe(1)
        ->and($rows[1][6])->toBe(0)
        ->and($rows[1][7])->toBe(1)
        ->and($rows[1][8])->toBe('')
        ->and($rows[1][9])->toBe('');

    $xlsx = Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
    $temporaryFile = tempnam(sys_get_temp_dir(), 'l8-export-');

    try {
        file_put_contents($temporaryFile, $xlsx);
        $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())
            ->toBe('KEDUDUKAN PERJAWATAN DAN PENGISIAN MENGIKUT PROGRAM / AKTIVITI')
            ->and($sheet->getCell('B3')->getValue())->toBe('baca dari')
            ->and($sheet->getCell('A6')->getValue())->toBe('PROGRAM 1 / 01 - PENGURUSAN HOSPITAL')
            ->and($sheet->getCell('F6')->getValue())->toBe(1)
            ->and($sheet->getCell('G6')->getValue())->toBe(1)
            ->and($sheet->getCell('L6')->getValue())->toBe('001234567890')
            ->and($sheet->getCell('L6')->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell('H7')->getValue())->toBe(1)
            ->and($sheet->getMergeCells())->toHaveKeys(['A1:Q1', 'A2:Q2', 'I4:J4', 'O4:P4']);
    } finally {
        if (is_file($temporaryFile)) {
            unlink($temporaryFile);
        }
    }
});
