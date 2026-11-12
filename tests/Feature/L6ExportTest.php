<?php

use App\Exports\L6Export;
use App\Filament\Pages\Report;
use App\Models\Aktiviti;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    foreach ([
        'waran_jawatans',
        'pegawais',
        'aktivitis',
        'program_ptj',
        'programs',
        'ptjs',
        'jawatan__greds',
        'greds',
        'jawatans',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('jawatans', function (Blueprint $table) {
        $table->id();
        $table->string('desc_jawatan')->nullable();
        $table->timestamps();
    });

    Schema::create('greds', function (Blueprint $table) {
        $table->id();
        $table->string('kod_gred')->nullable();
        $table->string('desc_gred')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatan__greds', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('jawatan_id');
        $table->unsignedBigInteger('gred_id');
        $table->unsignedBigInteger('kumpulan_id')->nullable();
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table) {
        $table->id();
        $table->string('nama_ptj')->nullable();
        $table->timestamps();
    });

    Schema::create('programs', function (Blueprint $table) {
        $table->id();
        $table->string('nama_program')->nullable();
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('program_ptj', function (Blueprint $table) {
        $table->unsignedBigInteger('program_id');
        $table->unsignedBigInteger('ptj_id');
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table) {
        $table->id();
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('waran_jawatans', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('l6 report uses the exact waran grade combination for the selected jawatan', function () {
    $selectedJawatan = Jawatan::query()->create(['desc_jawatan' => 'JURURAWAT']);
    $otherJawatan = Jawatan::query()->create(['desc_jawatan' => 'PEMBANTU TADBIR']);
    $gredU9 = Gred::query()->create(['kod_gred' => 'U9']);
    $gredU10 = Gred::query()->create(['kod_gred' => 'U10']);
    $ptj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL BALING']);
    $program = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PENGURUSAN HOSPITAL',
    ]);
    $ptj->programs()->attach($program->id);
    $emptyProgram = Program::query()->create([
        'nama_program' => 'PROGRAM 3',
        'desc_program' => 'NO MATCHING POSTS',
    ]);
    $emptyPtj = Ptj::query()->create(['nama_ptj' => 'EMPTY PTJ']);
    $emptyPtj->programs()->attach($emptyProgram->id);
    $aktiviti = Aktiviti::query()->create(['program_id' => $program->id]);
    $pegawai = Pegawai::query()->create(['is_tetap' => true]);

    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'jawatan_ids' => [$selectedJawatan->id],
        'gred_ids' => [$gredU9->id],
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'pegawai_id' => $pegawai->id,
        'jawatan_ids' => [$selectedJawatan->id],
        'gred_ids' => [$gredU10->id],
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'pegawai_id' => $pegawai->id,
        'jawatan_ids' => [$selectedJawatan->id],
        'gred_ids' => [$gredU9->id, $gredU10->id],
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'jawatan_ids' => [$otherJawatan->id],
        'gred_ids' => [$gredU9->id],
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'jawatan_ids' => [$selectedJawatan->id],
        'gred_ids' => [$gredU9->id],
        'status' => 'removed',
    ]);

    $report = (new ReflectionClass(Report::class))->newInstanceWithoutConstructor();
    $gradeOptionsMethod = new ReflectionMethod(Report::class, 'gredCombinationOptionsForJawatan');
    $gradeOptions = $gradeOptionsMethod->invoke($report, $selectedJawatan->id);

    expect($gradeOptions)->toHaveKey(implode(',', [$gredU9->id, $gredU10->id]), 'U9/U10');

    $export = new L6Export($selectedJawatan->id, [$gredU9->id, $gredU10->id]);
    $rows = $export->collection();

    expect($rows[0][0])->toBe('PROGRAM 2 : PENGURUSAN HOSPITAL')
        ->and($rows[1])->toBe([1, 'HOSPITAL BALING', 1, 1, 0])
        ->and($rows[2])->toBe(['JUMLAH PROGRAM 2', '', 1, 1, 0])
        ->and($rows[3])->toBe(['JUMLAH KESELURUHAN', '', 1, 1, 0])
        ->and($rows->flatten()->all())->not->toContain('PROGRAM 3 : NO MATCHING POSTS');

    $xlsx = Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
    expect($xlsx)->not->toBeEmpty();

    $temporaryFile = tempnam(sys_get_temp_dir(), 'l6-export-');

    try {
        file_put_contents($temporaryFile, $xlsx);
        $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('MAKLUMAT PERJAWATAN')
            ->and($sheet->getCell('D1')->getValue())->toBe('JURURAWAT, GRED U9/U10')
            ->and($sheet->getCell('A2')->getValue())->toBe('DATA SEHINGGA')
            ->and($sheet->getCell('D2')->getValue())->not->toBeEmpty();
    } finally {
        if (is_file($temporaryFile)) {
            unlink($temporaryFile);
        }
    }
});
