<?php

use App\Exports\JikByJawatanExport;
use App\Models\Aktiviti;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach ([
        'waran_jawatans',
        'pegawais',
        'aktivitis',
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
        $table->timestamps();
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
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('jik by jawatan columns come from waran jawatan gred combinations not jawatan_gred', function () {
    $jawatan = Jawatan::query()->create([
        'kod_jawatan' => 'N',
        'desc_jawatan' => 'PEMBANTU TADBIR (PERKERANIAN/OPERASI)',
    ]);

    $n4 = Gred::query()->create(['kod_gred' => 'N4', 'desc_gred' => 'N4']);
    $n3 = Gred::query()->create(['kod_gred' => 'N3', 'desc_gred' => 'N3']);
    $n2 = Gred::query()->create(['kod_gred' => 'N2', 'desc_gred' => 'N2']);
    $n1 = Gred::query()->create(['kod_gred' => 'N1', 'desc_gred' => 'N1']);
    $unused = Gred::query()->create(['kod_gred' => 'N5', 'desc_gred' => 'N5']);

    // All five greds linked in jawatan_gred — report must ignore unused N5.
    foreach ([$unused, $n4, $n3, $n2, $n1] as $gred) {
        Schema::getConnection()->table('jawatan__greds')->insert([
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
        ]);
    }

    $ptj = Ptj::query()->create(['nama_ptj' => 'JABATAN KESIHATAN NEGERI KEDAH']);
    $program = Program::query()->create([
        'nama_program' => 'PROGRAM 1',
        'desc_program' => 'PROGRAM PENGURUSAN',
    ]);
    $aktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'nama_aktiviti' => 'Aktiviti 1',
    ]);
    $pegawai = Pegawai::query()->create([
        'is_tetap' => true,
        'is_kontrak_interim' => false,
    ]);

    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$n4->id],
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'pegawai_id' => $pegawai->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$n3->id],
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'aktiviti_id' => $aktiviti->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$n1->id, $n2->id, $n3->id],
        'status' => 'active',
    ]);

    $export = new JikByJawatanExport($jawatan->id);
    $rows = $export->collection();

    expect($export->greds)->toHaveCount(3)
        ->and(collect($export->greds)->pluck('label')->all())->toBe(['N4', 'N3', 'N1/N2/N3']);

    $header = $rows[3];
    expect($header[2])->toBe(strtoupper($jawatan->desc_jawatan.' N4'))
        ->and($header[5])->toBe(strtoupper($jawatan->desc_jawatan.' N3'))
        ->and($header[8])->toBe(strtoupper($jawatan->desc_jawatan.' N1/N2/N3'))
        ->and(implode('|', $header))->not->toContain('N5');

    // PTJ row: N4 J=1 I=0 K=1 | N3 J=1 I=1 K=0 | N1/N2/N3 J=1 I=0 K=1 | total J=3 I=1 K=2
    $ptjRow = $rows[$export->ptjRows[0] - 1];
    expect($ptjRow)->toBe([
        1,
        'JABATAN KESIHATAN NEGERI KEDAH',
        1, 0, 1,
        1, 1, 0,
        1, 0, 1,
        3, 1, 2,
    ]);
});
