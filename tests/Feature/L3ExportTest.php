<?php

use App\Exports\L3Export;
use App\Models\Aktiviti;
use App\Models\Bahagian;
use App\Models\Jawatan;
use App\Models\Jawatan_Gred;
use App\Models\Kumpulan;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\User;
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
        'bahagians',
        'ptjs',
        'jawatan__greds',
        'jawatans',
        'kumpulans',
        'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->tinyInteger('role')->default(3);
        $table->tinyInteger('status')->default(1);
        $table->timestamps();
    });

    Schema::create('kumpulans', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_kumpulan')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatans', function (Blueprint $table): void {
        $table->id();
        $table->string('desc_jawatan')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatan__greds', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('jawatan_id')->nullable();
        $table->unsignedBigInteger('gred_id')->nullable();
        $table->unsignedBigInteger('kumpulan_id')->nullable();
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj')->nullable();
        $table->boolean('is_jkn')->default(false);
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama_bahagian')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('programs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_program')->nullable();
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('program_ptj', function (Blueprint $table): void {
        $table->unsignedBigInteger('program_id');
        $table->unsignedBigInteger('ptj_id');
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->string('nama_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
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

test('l3 export returns only the grand total row when empty', function () {
    $export = new L3Export;
    $rows = $export->collection();

    expect($rows)->toHaveCount(1)
        ->and($rows[0][0])->toBe('JUMLAH KESELURUHAN')
        ->and($export->dataRows)->toBeEmpty();

    Excel::fake();

    Excel::download($export, 'L3.xlsx');

    Excel::assertDownloaded('L3.xlsx');
});

test('role 3 l3 export only includes the user ptj', function () {
    $kumpulan = Kumpulan::query()->create(['nama_kumpulan' => 'Pengurusan']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI TADBIR']);
    $jg = Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => 1,
        'kumpulan_id' => $kumpulan->id,
    ]);

    $ownPtj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL JITRA']);
    $otherPtj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL SULTANAH BAHIYAH']);
    $program = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PENGURUSAN HOSPITAL',
    ]);
    $program->ptjs()->attach([$ownPtj->id, $otherPtj->id]);

    $aktiviti = Aktiviti::query()->create([
        'program_id' => $program->id,
        'nama_aktiviti' => 'Aktiviti Hospital',
    ]);

    foreach ([$ownPtj, $otherPtj] as $ptj) {
        WaranJawatan::query()->create([
            'ptj_id' => $ptj->id,
            'aktiviti_id' => $aktiviti->id,
            'jawatan_gred_id' => $jg->id,
            'status' => 'active',
        ]);
    }

    $user = User::query()->create([
        'name' => 'PTJ User',
        'email' => 'l3-ptj-user@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ownPtj->id,
        'role' => 3,
        'status' => 1,
    ]);

    $this->actingAs($user);

    $export = new L3Export;
    $rows = $export->collection();

    // collection() rows are 0-indexed relative to startCell A4, while dataRows
    // track absolute spreadsheet row numbers starting at 4.
    $labels = collect($export->dataRows)
        ->map(function (int $absoluteRow) use ($rows) {
            $index = $absoluteRow - 4;

            return $rows[$index][1] ?? null;
        })
        ->filter()
        ->values()
        ->all();

    expect($labels)->toBe(['HOSPITAL JITRA'])
        ->and($labels)->not->toContain('HOSPITAL SULTANAH BAHIYAH');
});

test('l3 export puts ibu pejabat after all programs and applies template colors', function () {
    $kumpulan = Kumpulan::query()->create(['nama_kumpulan' => 'Pengurusan']);
    $jawatan = Jawatan::query()->create(['desc_jawatan' => 'PEGAWAI TADBIR']);
    $jg = Jawatan_Gred::query()->create([
        'jawatan_id' => $jawatan->id,
        'gred_id' => 1,
        'kumpulan_id' => $kumpulan->id,
    ]);

    $jkn = Ptj::query()->create(['nama_ptj' => 'JKN KEDAH', 'is_jkn' => true]);
    $hospital = Ptj::query()->create(['nama_ptj' => 'HOSPITAL JITRA', 'is_jkn' => false]);
    $bahagian = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PENGURUSAN',
    ]);

    $program1 = Program::query()->create([
        'nama_program' => 'PROGRAM 1',
        'desc_program' => 'PENGURUSAN',
    ]);
    $program2 = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PERUBATAN',
    ]);
    $program1->ptjs()->attach([$jkn->id]);
    $program2->ptjs()->attach([$hospital->id]);

    $aktiviti1 = Aktiviti::query()->create([
        'program_id' => $program1->id,
        'nama_aktiviti' => 'Aktiviti HQ',
    ]);
    $aktiviti2 = Aktiviti::query()->create([
        'program_id' => $program2->id,
        'nama_aktiviti' => 'Aktiviti Hospital',
    ]);

    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagian->id,
        'aktiviti_id' => $aktiviti1->id,
        'jawatan_gred_id' => $jg->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $hospital->id,
        'aktiviti_id' => $aktiviti2->id,
        'jawatan_gred_id' => $jg->id,
        'status' => 'active',
    ]);

    $export = new L3Export;
    Excel::store($export, 'framework/testing/l3-style.xlsx', 'local');

    $stored = storage_path('app/private/framework/testing/l3-style.xlsx');
    if (! file_exists($stored)) {
        $stored = storage_path('app/framework/testing/l3-style.xlsx');
    }

    $sheet = IOFactory::load($stored)->getActiveSheet();

    $sectionLabels = collect($export->programRows)
        ->map(fn (int $row) => $sheet->getCell('A'.$row)->getValue())
        ->all();

    expect($sectionLabels)->toBe([
        'PROGRAM 2 : PERUBATAN',
        'IBU PEJABAT JKN',
    ])
        ->and(strtoupper($sheet->getStyle('A2')->getFill()->getStartColor()->getRGB()))->toBe('999999')
        ->and(strtoupper($sheet->getStyle('A'.$export->programRows[0])->getFill()->getStartColor()->getRGB()))->toBe('6FA7DB')
        ->and(strtoupper($sheet->getStyle('A'.$export->jumlahRows[0])->getFill()->getStartColor()->getRGB()))->toBe('F4CCCC')
        ->and($sheet->getRowDimension($export->dataRows[0])->getRowHeight())->toBe(34.0)
        ->and($sheet->getFreezePane())->toBe('A4');
});
