<?php

use App\Exports\DataKontrakExport;
use App\Models\Aktiviti;
use App\Models\Bahagian;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\Pegawai;
use App\Models\PegawaiKontrak;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach ([
        'waran_jawatans',
        'pegawai_kontraks',
        'pegawais',
        'aktivitis',
        'programs',
        'bahagians',
        'ptjs',
        'jawatan__greds',
        'greds',
        'jawatans',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('jawatans', function (Blueprint $table) {
        $table->id();
        $table->string('kod_jawatan')->nullable();
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
        $table->boolean('is_jkn')->default(false);
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama_bahagian')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('programs', function (Blueprint $table) {
        $table->id();
        $table->string('nama_program')->nullable();
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->string('nama_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->boolean('is_kontrak')->default(false);
        $table->boolean('is_tetap')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('pegawai_kontraks', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('green columns come only from kontrak pegawai jawatan and gred', function () {
    $perubatan = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI PERUBATAN']);
    $jururawat = Jawatan::query()->create(['desc_jawatan' => 'JURURAWAT']);
    $farmasi = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI FARMASI']);

    $ud41 = Gred::query()->create(['kod_gred' => 'UD9', 'desc_gred' => 'UD41']);
    $ud48 = Gred::query()->create(['kod_gred' => 'UD48', 'desc_gred' => 'UD48']);
    $u32 = Gred::query()->create(['kod_gred' => 'U6', 'desc_gred' => 'U32']);
    $u41 = Gred::query()->create(['kod_gred' => 'U9', 'desc_gred' => 'U41']);

    $jgUd41 = Jawatan_Gred::query()->create(['jawatan_id' => $perubatan->id, 'gred_id' => $ud41->id]);
    $jgUd48 = Jawatan_Gred::query()->create(['jawatan_id' => $perubatan->id, 'gred_id' => $ud48->id]);
    $jgJururawat = Jawatan_Gred::query()->create(['jawatan_id' => $jururawat->id, 'gred_id' => $u32->id]);
    // Farmasi exists in jawatan_gred but no kontrak pegawai — must not appear
    Jawatan_Gred::query()->create(['jawatan_id' => $farmasi->id, 'gred_id' => $u41->id]);

    $hospital = Ptj::query()->create([
        'nama_ptj' => 'HOSPITAL SULTANAH BAHIYAH',
        'is_jkn' => false,
    ]);
    $program = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PENGURUSAN HOSPITAL',
    ]);
    $aktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'nama_aktiviti' => 'Aktiviti Hospital',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $hospital->id,
        'aktiviti_id' => $aktiviti->id,
        'status' => 'active',
    ]);

    foreach ([$jgUd41, $jgUd48, $jgJururawat] as $jg) {
        $pegawai = Pegawai::query()->create([
            'ptj_id' => $hospital->id,
            'jawatan_gred_id' => $jg->id,
            'is_kontrak' => true,
        ]);
        PegawaiKontrak::query()->create([
            'pegawai_id' => $pegawai->id,
            'program_id' => $program->id,
        ]);
    }

    // Tetap must not create a column or count
    Pegawai::query()->create([
        'ptj_id' => $hospital->id,
        'jawatan_gred_id' => $jgUd41->id,
        'is_kontrak' => false,
        'is_tetap' => true,
    ]);

    $export = new DataKontrakExport;
    $rows = $export->collection();

    expect(collect($export->columns)->pluck('label')->all())->toBe([
        'JURURAWAT U6',
        'PEGAWAI PERUBATAN UD48',
        'PEGAWAI PERUBATAN UD9',
    ]);

    expect($rows[0][0])->toStartWith('DATA PERJAWATAN KONTRAK JKN KEDAH SEHINGGA');
    expect($rows[1])->toContain('BIL')
        ->and($rows[1])->toContain('PUSAT TANGGUNGJAWAB')
        ->and($rows[1])->toContain('JUMLAH JAWATAN')
        ->and($rows[1])->toContain('JURURAWAT U6')
        ->and($rows[1])->not->toContain('PEGAWAI FARMASI U9');

    $dataRow = $rows[$export->dataRows[0] - 1];
    expect($dataRow[1])->toBe('HOSPITAL SULTANAH BAHIYAH')
        ->and($dataRow[2])->toBe(3)
        ->and($dataRow[3])->toBe(1) // JURURAWAT U6
        ->and($dataRow[4])->toBe(1) // UD48
        ->and($dataRow[5])->toBe(1); // UD9

    $totalRow = $rows[$export->totalRow - 1];
    expect($totalRow[0])->toBe('JUMLAH KESELURUHAN')
        ->and($totalRow[2])->toBe(3);
});

test('data kontrak export uses bahagian rows for jkn under ibu pejabat', function () {
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI PERUBATAN']);
    $ud41 = Gred::query()->create(['kod_gred' => 'UD9', 'desc_gred' => 'UD41']);
    $jg = Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => $ud41->id,
    ]);

    $jkn = Ptj::query()->create([
        'nama_ptj' => 'JABATAN KESIHATAN NEGERI KEDAH',
        'is_jkn' => true,
    ]);
    $bahagian = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PENGURUSAN',
    ]);
    $program = Program::query()->create([
        'nama_program' => 'PROGRAM 1',
        'desc_program' => 'PROGRAM PENGURUSAN',
    ]);
    $aktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'nama_aktiviti' => 'Aktiviti HQ',
    ]);

    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagian->id,
        'aktiviti_id' => $aktiviti->id,
        'status' => 'active',
    ]);

    $pegawai = Pegawai::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagian->id,
        'jawatan_gred_id' => $jg->id,
        'is_kontrak' => true,
    ]);
    PegawaiKontrak::query()->create([
        'pegawai_id' => $pegawai->id,
        'program_id' => $program->id,
    ]);

    $export = new DataKontrakExport;
    $rows = $export->collection();

    expect(collect($export->columns)->pluck('label')->all())->toBe([
        'PEGAWAI PERUBATAN UD9',
    ]);

    $section = $rows[$export->sectionRows[0] - 1];
    expect($section[0])->toBe('IBU PEJABAT JKN');

    $dataRow = $rows[$export->dataRows[0] - 1];
    expect($dataRow[1])->toBe('BAHAGIAN PENGURUSAN')
        ->and($dataRow[2])->toBe(1)
        ->and($dataRow[3])->toBe(1);
});
