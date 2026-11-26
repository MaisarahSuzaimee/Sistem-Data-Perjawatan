<?php

use App\Exports\L7Export;
use App\Models\Aktiviti;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\OpsyenPencen;
use App\Models\Pegawai;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    foreach ([
        'waran_jawatans',
        'warans',
        'pegawais',
        'opsyen_pencens',
        'jawatan__greds',
        'jawatans',
        'greds',
        'aktivitis',
        'programs',
        'ptjs',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('programs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_program')->nullable();
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->string('no_aktivit')->nullable();
        $table->string('nama_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatans', function (Blueprint $table): void {
        $table->id();
        $table->string('desc_jawatan')->nullable();
        $table->timestamps();
    });

    Schema::create('greds', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_gred')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatan__greds', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('jawatan_id');
        $table->unsignedBigInteger('gred_id');
        $table->timestamps();
    });

    Schema::create('opsyen_pencens', function (Blueprint $table): void {
        $table->id();
        $table->string('opsyen')->nullable();
        $table->timestamps();
    });

    Schema::create('warans', function (Blueprint $table): void {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->unsignedBigInteger('opsyen_pencen_id')->nullable();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->date('tarikh_lantikan')->nullable();
        $table->date('tarikh_sah_jawatan')->nullable();
        $table->date('tarikh_pencen')->nullable();
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->string('butiran')->nullable();
        $table->string('paparan_jawatan')->nullable();
        $table->text('catatan_jawatan')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('l7 splits title, renames headers, and adds bersara/catatan columns', function () {
    $program = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PERUBATAN',
    ]);
    $aktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'no_aktivit' => '01',
    ]);
    $ptj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL JITRA']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'JURURAWAT']);
    $gred = Gred::query()->create(['kod_gred' => 'U6']);
    $jg = Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
    ]);
    $opsyen = OpsyenPencen::query()->create(['opsyen' => '60']);
    $waran = Waran::query()->create(['no_waran' => 'W-001']);
    $pegawai = Pegawai::query()->create([
        'ptj_id' => $ptj->id,
        'jawatan_gred_id' => $jg->id,
        'opsyen_pencen_id' => $opsyen->id,
        'nama' => 'SITI AMINAH',
        'nokp' => '900101015678',
        'tarikh_lantikan' => '2015-01-15',
        'tarikh_sah_jawatan' => '2016-01-15',
        'tarikh_pencen' => '2050-01-01',
        'is_tetap' => true,
    ]);

    WaranJawatan::query()->create([
        'waran_id' => $waran->id,
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'pegawai_id' => $pegawai->id,
        'jawatan_gred_id' => $jg->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
        'butiran' => '001',
        'catatan_jawatan' => 'CATATAN L7',
        'status' => 'active',
    ]);

    $export = new L7Export($jawatan->id, [$gred->id]);
    $rows = $export->collection();

    expect($rows[0][0])->toBe('MAKLUMAT PERJAWATAN DAN PENYANDANG BAGI JAWATAN JURURAWAT GRED U6')
        ->and($rows[0][0])->not->toContain('DI JABATAN')
        ->and($rows[1][0])->toStartWith('DI JABATAN KESIHATAN NEGERI KEDAH SEHINGGA ')
        ->and($rows[2][6])->toBe('GRED PEGAWAI')
        ->and($rows[2][7])->toBe('JUM. JWT')
        ->and($rows[2][15])->toBe('TARIKH LANTIK')
        ->and($rows[2][16])->toBe('TARIKH SAH')
        ->and($rows[2][17])->toBe('TARIKH BERSARA WAJIB (OPSYEN PENCEN 55/56/58/60)')
        ->and($rows[2][18])->toBe('TARIKH DIJANGKA BERSARA')
        ->and($rows[2][19])->toBe('Catatan')
        ->and($rows[$export->programRows[0] - 1][0])->toBe('PROGRAM 2 : PERUBATAN')
        ->and($export->programSummaryRows)->toHaveCount(1)
        ->and($rows[$export->programSummaryRows[0] - 1][0])->toBe('PROGRAM 2')
        ->and($rows[$export->programSummaryRows[0] - 1][7])->toBe(1)
        ->and($rows[$export->programSummaryRows[0] - 1][8])->toBe(1)
        ->and($rows[$export->programSummaryRows[0] - 1][9])->toBe(0)
        ->and($rows[$export->grandTotalRow - 1][0])->toBe('JUMLAH KESELURUHAN')
        ->and($rows[$export->grandTotalRow - 1][7])->toBe(1);

    $dataRow = $rows[$export->dataRows[0] - 1];
    expect($dataRow[17])->toBe('60')
        ->and($dataRow[18])->toBe('01.01.2050')
        ->and($dataRow[19])->toBe('CATATAN L7');

    $xlsx = Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
    $temporaryFile = tempnam(sys_get_temp_dir(), 'l7-export-');

    try {
        file_put_contents($temporaryFile, $xlsx);
        $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())
            ->toBe('MAKLUMAT PERJAWATAN DAN PENYANDANG BAGI JAWATAN JURURAWAT GRED U6')
            ->and($sheet->getCell('A2')->getValue())->toStartWith('DI JABATAN KESIHATAN NEGERI KEDAH SEHINGGA ')
            ->and($sheet->getStyle('A1')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getStyle('A2')->getAlignment()->getHorizontal())->toBe('center')
            ->and($sheet->getCell('G3')->getValue())->toBe('GRED PEGAWAI')
            ->and($sheet->getCell('H3')->getValue())->toBe('JUM. JWT')
            ->and($sheet->getCell('P3')->getValue())->toBe('TARIKH LANTIK')
            ->and($sheet->getCell('Q3')->getValue())->toBe('TARIKH SAH')
            ->and($sheet->getCell('R3')->getValue())->toBe('TARIKH BERSARA WAJIB (OPSYEN PENCEN 55/56/58/60)')
            ->and($sheet->getCell('S3')->getValue())->toBe('TARIKH DIJANGKA BERSARA')
            ->and($sheet->getCell('T3')->getValue())->toBe('Catatan')
            ->and($sheet->getMergeCells())->toHaveKey('A1:T1')
            ->and($sheet->getMergeCells())->toHaveKey('A2:T2');
    } finally {
        if (is_file($temporaryFile)) {
            unlink($temporaryFile);
        }
    }
});
