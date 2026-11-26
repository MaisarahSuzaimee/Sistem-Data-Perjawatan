<?php

use App\Exports\L4Export;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\Kumpulan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\User;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    foreach (['waran_jawatans', 'pegawais', 'jawatan__greds', 'greds', 'jawatans', 'kumpulans', 'users', 'ptjs'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->tinyInteger('role')->default(3);
        $table->tinyInteger('status')->default(1);
        $table->timestamps();
    });

    Schema::create('kumpulans', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_kumpulan');
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
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->string('paparan_jawatan')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('L4 exports positions grouped by scheme with filled, vacant, percentage and matching layout', function () {
    $paramedicSkim = Kumpulan::query()->create(['nama_kumpulan' => 'Paramedik & Auxiliari']);
    $otherSkim = Kumpulan::query()->create(['nama_kumpulan' => 'Pelaksana']);
    $paramedic = Jawatan::query()->create(['desc_jawatan' => 'PENOLONG PEGAWAI PERUBATAN']);
    $auxiliary = Jawatan::query()->create(['desc_jawatan' => 'PENOLONG PEGAWAI KESIHATAN PERSEKITARAN']);
    $pelaksana = Jawatan::query()->create(['desc_jawatan' => 'PEMBANTU OPERASI']);
    $u7 = Gred::query()->create(['kod_gred' => 'U7']);
    $u6 = Gred::query()->create(['kod_gred' => 'U6']);
    $u5 = Gred::query()->create(['kod_gred' => 'U5']);
    Jawatan_Gred::query()->create([
        'jawatan_id' => $paramedic->id,
        'gred_id' => $u7->id,
        'kumpulan_id' => $paramedicSkim->id,
    ]);
    Jawatan_Gred::query()->create([
        'jawatan_id' => $paramedic->id,
        'gred_id' => $u6->id,
        'kumpulan_id' => $paramedicSkim->id,
    ]);
    Jawatan_Gred::query()->create([
        'jawatan_id' => $auxiliary->id,
        'gred_id' => $u5->id,
        'kumpulan_id' => $paramedicSkim->id,
    ]);
    Jawatan_Gred::query()->create([
        'jawatan_id' => $pelaksana->id,
        'gred_id' => $u5->id,
        'kumpulan_id' => $otherSkim->id,
    ]);
    $pegawaiTetap = Pegawai::query()->create(['is_tetap' => true]);
    $pegawaiInterim = Pegawai::query()->create(['is_kontrak_interim' => true]);

    WaranJawatan::query()->create([
        'jawatan_ids' => [$paramedic->id],
        'gred_ids' => [$u7->id],
        'paparan_jawatan' => 'PENOLONG PEGAWAI PERUBATAN (Open Post)',
    ]);
    WaranJawatan::query()->create([
        'pegawai_id' => $pegawaiTetap->id,
        'jawatan_ids' => [$paramedic->id],
        'gred_ids' => [$u7->id],
        'paparan_jawatan' => 'PENOLONG PEGAWAI PERUBATAN',
    ]);
    WaranJawatan::query()->create([
        'jawatan_ids' => [$paramedic->id],
        'gred_ids' => [$u6->id],
    ]);
    WaranJawatan::query()->create([
        'pegawai_id' => $pegawaiInterim->id,
        'jawatan_ids' => [$auxiliary->id],
        'gred_ids' => [$u5->id],
    ]);
    WaranJawatan::query()->create([
        'jawatan_ids' => [$pelaksana->id],
        'gred_ids' => [$u5->id],
    ]);
    WaranJawatan::query()->create([
        'jawatan_ids' => [$paramedic->id],
        'gred_ids' => [$u7->id],
        'status' => 'removed',
    ]);
    WaranJawatan::query()->create([
        'jawatan_ids' => [$paramedic->id],
        'gred_ids' => [$u7->id],
    ])->delete();

    $export = new L4Export($paramedicSkim->id);
    $rows = $export->collection();

    expect($rows[3])->toBe(['', 1, 'PENOLONG PEGAWAI PERUBATAN, Gred U6', 1, 0, 1, '0%'])
        ->and($rows[4])->toBe(['', 2, 'PENOLONG PEGAWAI PERUBATAN (Open Post), Gred U7', 1, 0, 1, '0%'])
        ->and($rows[5])->toBe(['', 3, 'PENOLONG PEGAWAI PERUBATAN, Gred U7', 1, 1, 0, '100%'])
        ->and($rows[6])->toBe(['', 'Jumlah', '', 3, 1, 2, '33.33%'])
        ->and($rows[8])->toBe(['', 1, 'PENOLONG PEGAWAI KESIHATAN PERSEKITARAN, Gred U5', 1, 1, 0, '100%'])
        ->and($rows[9])->toBe(['', 'Jumlah', '', 1, 1, 0, '100%'])
        ->and($rows[10])->toBe(['JUMLAH KESELURUHAN', '', '', 4, 2, 2, '50.00%']);

    $xlsx = Excel::raw($export, Maatwebsite\Excel\Excel::XLSX);
    $temporaryFile = tempnam(sys_get_temp_dir(), 'l4-export-');

    try {
        file_put_contents($temporaryFile, $xlsx);
        $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())
            ->toContain('DATA PERJAWATAN PARAMEDIK & AUXILIARI MENGIKUT SKIM PERKHIDMATAN DI JKN KEDAH SEHINGGA ')
            ->and($sheet->getStyle('A1')->getFont()->getName())->toBe('Calibri')
            ->and($sheet->getStyle('A1')->getFont()->getSize())->toBe(12.0)
            ->and($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue()
            ->and($sheet->getStyle('A2')->getFont()->getName())->toBe('Calibri')
            ->and($sheet->getStyle('C4')->getFont()->getName())->toBe('Calibri')
            ->and($sheet->getCell('B2')->getValue())->toBe('SKIM PERKHIDMATAN/NAMA & GRED JAWATAN')
            ->and($sheet->getCell('C5')->getValue()->getPlainText())
            ->toBe('PENOLONG PEGAWAI PERUBATAN (Open Post), Gred U7')
            ->and($sheet->getCell('C5')->getValue()->getRichTextElements()[1]->getFont()->getColor()->getARGB())
            ->toBe('FFFF0000')
            ->and($sheet->getCell('D4')->getValue())->toBe(1)
            ->and($sheet->getCell('B7')->getValue())->toBe('Jumlah')
            ->and($sheet->getCell('G7')->getValue())->toBe('33.33%')
            ->and($sheet->getCell('B10')->getValue())->toBe('Jumlah')
            ->and($sheet->getCell('A11')->getValue())->toBe('JUMLAH KESELURUHAN')
            ->and($sheet->getCell('D11')->getValue())->toBe(4)
            ->and($sheet->getCell('E11')->getValue())->toBe(2)
            ->and($sheet->getCell('F11')->getValue())->toBe(2)
            ->and($sheet->getCell('G11')->getValue())->toBe('50.00%')
            ->and($sheet->getMergeCells())->toHaveKeys([
                'A1:G1',
                'B2:C2',
                'B3:G3',
                'A3:A7',
                'B7:C7',
                'B8:G8',
                'A8:A10',
                'B10:C10',
                'A11:C11',
            ])
            ->and($sheet->getStyle('B7')->getFill()->getStartColor()->getRGB())->toBe('F4B1D0')
            ->and($sheet->getStyle('A11')->getFill()->getStartColor()->getRGB())->toBe('00B0F0');
    } finally {
        if (is_file($temporaryFile)) {
            unlink($temporaryFile);
        }
    }
});

test('L4 places the state health director scheme first for the medical officer group', function () {
    $kumpulan = Kumpulan::query()->create(['nama_kumpulan' => 'PEGAWAI PERUBATAN']);
    $otherJawatan = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI PERUBATAN']);
    $preferredJawatan = Jawatan::query()->create([
        'desc_jawatan' => 'PENGARAH KESIHATAN NEGERI, PEGAWAI PERUBATAN, GRED UTAMA B',
    ]);
    $gred = Gred::query()->create(['kod_gred' => 'VU6']);

    foreach ([$otherJawatan, $preferredJawatan] as $jawatan) {
        Jawatan_Gred::query()->create([
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'kumpulan_id' => $kumpulan->id,
        ]);
        WaranJawatan::query()->create([
            'jawatan_ids' => [$jawatan->id],
            'gred_ids' => [$gred->id],
        ]);
    }

    $rows = (new L4Export($kumpulan->id))->collection();

    expect($rows[2])->toBe([1, 'PENGARAH KESIHATAN NEGERI, PEGAWAI PERUBATAN, GRED UTAMA B', '', '', '', '', ''])
        ->and($rows[5][0])->toBe(2)
        ->and($rows[5][1])->toBe('PEGAWAI PERUBATAN');
});

test('L4 sorts grades ascending in the position label', function () {
    $kumpulan = Kumpulan::query()->create(['nama_kumpulan' => 'PEGAWAI PERUBATAN']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI PERUBATAN']);
    $uf13 = Gred::query()->create(['kod_gred' => 'UF13']);
    $uf9 = Gred::query()->create(['kod_gred' => 'UF9']);
    $uf11 = Gred::query()->create(['kod_gred' => 'UF11']);
    $uf10 = Gred::query()->create(['kod_gred' => 'UF10']);

    foreach ([$uf13, $uf9, $uf11, $uf10] as $gred) {
        Jawatan_Gred::query()->create([
            'jawatan_id' => $jawatan->id,
            'gred_id' => $gred->id,
            'kumpulan_id' => $kumpulan->id,
        ]);
    }

    WaranJawatan::query()->create([
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$uf13->id, $uf9->id, $uf11->id, $uf10->id],
    ]);

    $rows = (new L4Export($kumpulan->id))->collection();

    expect($rows[3][2])->toBe('PEGAWAI PERUBATAN, Gred UF9/UF10/UF11/UF13')
        ->and($rows[4][1])->toBe('Jumlah');
});

test('L4 uses the role 3 user PTJ name in the Excel header', function () {
    $ptj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL BALING']);
    $user = User::query()->create([
        'name' => 'Role 3 User',
        'email' => 'role3-l4@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ptj->id,
        'role' => 3,
        'status' => 1,
    ]);
    auth()->login($user);

    $kumpulan = Kumpulan::query()->create(['nama_kumpulan' => 'Pelaksana']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'PEMBANTU OPERASI']);
    $gred = Gred::query()->create(['kod_gred' => 'N6']);

    Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'kumpulan_id' => $kumpulan->id,
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $ptj->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
    ]);

    $rows = (new L4Export($kumpulan->id))->collection();

    expect($rows[0][0])->toContain('DATA PERJAWATAN PELAKSANA MENGIKUT SKIM PERKHIDMATAN DI HOSPITAL BALING SEHINGGA ');

    auth()->logout();
});

test('role 3 L4 export only includes the user ptj', function () {
    $ownPtj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL JITRA']);
    $otherPtj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL SULTANAH BAHIYAH']);
    $kumpulan = Kumpulan::query()->create(['nama_kumpulan' => 'Pelaksana']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'PEMBANTU OPERASI']);
    $gred = Gred::query()->create(['kod_gred' => 'N6']);

    Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => $gred->id,
        'kumpulan_id' => $kumpulan->id,
    ]);

    WaranJawatan::query()->create([
        'ptj_id' => $ownPtj->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $otherPtj->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $otherPtj->id,
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
    ]);

    $user = User::query()->create([
        'name' => 'Role 3 User',
        'email' => 'role3-l4-scope@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ownPtj->id,
        'role' => 3,
        'status' => 1,
    ]);
    $this->actingAs($user);

    $rows = (new L4Export($kumpulan->id))->collection();

    expect($rows[3])->toBe(['', 1, 'PEMBANTU OPERASI, Gred N6', 1, 0, 1, '0%'])
        ->and($rows[4])->toBe(['', 'Jumlah', '', 1, 0, 1, '0%'])
        ->and($rows[5])->toBe(['JUMLAH KESELURUHAN', '', '', 1, 0, 1, '0%']);
});
