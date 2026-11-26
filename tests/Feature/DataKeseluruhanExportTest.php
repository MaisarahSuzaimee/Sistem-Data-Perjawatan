<?php

use App\Exports\DataKeseluruhanExport;
use App\Models\Aktiviti;
use App\Models\Bahagian;
use App\Models\Pegawai;
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
        'programs',
        'bahagians',
        'users',
        'ptjs',
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
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->boolean('is_kontrak')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('data keseluruhan export puts is_jkn bahagian under trailing ibu pejabat only', function () {
    $jkn = Ptj::query()->create([
        'nama_ptj' => 'JABATAN KESIHATAN NEGERI KEDAH',
        'is_jkn' => true,
    ]);
    $hospital = Ptj::query()->create([
        'nama_ptj' => 'HOSPITAL JITRA',
        'is_jkn' => false,
    ]);

    $bahagianPengurusan = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PENGURUSAN',
    ]);
    $bahagianPerubatan = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PERUBATAN',
    ]);
    Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN KEWANGAN',
    ]);

    $program1 = Program::query()->create([
        'nama_program' => 'PROGRAM 1',
        'desc_program' => 'PROGRAM PENGURUSAN',
    ]);
    $program2 = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PENGURUSAN HOSPITAL',
    ]);
    $aktiviti1 = Aktiviti::query()->create([
        'program_id' => $program1->id,
        'nama_aktiviti' => 'Aktiviti HQ',
    ]);
    $aktiviti2 = Aktiviti::query()->create([
        'program_id' => $program2->id,
        'nama_aktiviti' => 'Aktiviti Hospital',
    ]);

    $tetap = Pegawai::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianPengurusan->id,
        'is_tetap' => true,
    ]);

    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianPengurusan->id,
        'aktiviti_id' => $aktiviti1->id,
        'pegawai_id' => $tetap->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianPerubatan->id,
        'aktiviti_id' => $aktiviti2->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $hospital->id,
        'aktiviti_id' => $aktiviti2->id,
        'status' => 'active',
    ]);

    $export = new DataKeseluruhanExport;
    $rows = $export->collection();

    $sectionLabels = collect($export->mergeRows)
        ->map(fn (array $section) => $rows[$section['program_row'] - 5][0])
        ->all();

    expect($sectionLabels)->toBe([
        'PROGRAM 2: PENGURUSAN HOSPITAL',
        'IBU PEJABAT JKN',
    ]);

    $program2Labels = collect($export->mergeRows[0]['ptj_rows'])
        ->map(fn (int $rowNumber) => $rows[$rowNumber - 5][1])
        ->all();

    expect($program2Labels)->toBe(['HOSPITAL JITRA'])
        ->and($program2Labels)->not->toContain('JABATAN KESIHATAN NEGERI KEDAH')
        ->and($program2Labels)->not->toContain('BAHAGIAN PENGURUSAN')
        ->and($program2Labels)->not->toContain('BAHAGIAN PERUBATAN');

    $ibuPejabatLabels = collect($export->mergeRows[1]['ptj_rows'])
        ->map(fn (int $rowNumber) => $rows[$rowNumber - 5][1])
        ->all();

    expect($ibuPejabatLabels)->toBe([
        'BAHAGIAN PENGURUSAN',
        'BAHAGIAN PERUBATAN',
        'BAHAGIAN KEWANGAN',
    ])->and($export->mergeRows[1]['is_ibu_pejabat'])->toBeTrue()
        ->and($export->mergeRows[0]['is_ibu_pejabat'])->toBeFalse();

    $pengurusanRow = $rows[$export->mergeRows[1]['ptj_rows'][0] - 5];
    expect($pengurusanRow[1])->toBe('BAHAGIAN PENGURUSAN')
        ->and($pengurusanRow[2])->toBe(1)
        ->and($pengurusanRow[3])->toBe(1);

    $perubatanRow = $rows[$export->mergeRows[1]['ptj_rows'][1] - 5];
    expect($perubatanRow[1])->toBe('BAHAGIAN PERUBATAN')
        ->and($perubatanRow[2])->toBe(1)
        ->and($perubatanRow[3])->toBe('0');
});

test('data keseluruhan export styles ibu pejabat header grey on the correct excel row', function () {
    $jkn = Ptj::query()->create([
        'nama_ptj' => 'JABATAN KESIHATAN NEGERI KEDAH',
        'is_jkn' => true,
    ]);
    $hospital = Ptj::query()->create([
        'nama_ptj' => 'HOSPITAL JITRA',
        'is_jkn' => false,
    ]);
    $pkd = Ptj::query()->create([
        'nama_ptj' => 'PEJABAT KESIHATAN DAERAH PENDANG',
        'is_jkn' => false,
    ]);

    $bahagianPengurusan = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PENGURUSAN',
    ]);
    $bahagianPerubatan = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PERUBATAN',
    ]);
    $bahagianKesihatanAwam = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN KESIHATAN AWAM',
    ]);

    $program2 = Program::query()->create([
        'nama_program' => 'PROGRAM 2',
        'desc_program' => 'PERUBATAN',
    ]);
    $program3 = Program::query()->create([
        'nama_program' => 'PROGRAM 3',
        'desc_program' => 'KESIHATAN AWAM',
    ]);
    $aktiviti2 = Aktiviti::query()->create([
        'program_id' => $program2->id,
        'nama_aktiviti' => 'Aktiviti Hospital',
    ]);
    $aktiviti3 = Aktiviti::query()->create([
        'program_id' => $program3->id,
        'nama_aktiviti' => 'Aktiviti PKD',
    ]);

    WaranJawatan::query()->create([
        'ptj_id' => $hospital->id,
        'aktiviti_id' => $aktiviti2->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $pkd->id,
        'aktiviti_id' => $aktiviti3->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianPengurusan->id,
        'aktiviti_id' => $aktiviti2->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianPerubatan->id,
        'aktiviti_id' => $aktiviti3->id,
        'status' => 'active',
    ]);
    WaranJawatan::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianKesihatanAwam->id,
        'aktiviti_id' => $aktiviti3->id,
        'status' => 'active',
    ]);

    $export = new DataKeseluruhanExport;
    Excel::store($export, 'framework/testing/l1-style-debug.xlsx', 'local');

    $stored = storage_path('app/private/framework/testing/l1-style-debug.xlsx');
    if (! file_exists($stored)) {
        $stored = storage_path('app/framework/testing/l1-style-debug.xlsx');
    }

    $sheet = IOFactory::load($stored)->getActiveSheet();

    $ibu = collect($export->mergeRows)->firstWhere('is_ibu_pejabat', true);
    $headerRow = $ibu['program_row'];
    $secondBahagianRow = $ibu['ptj_rows'][1];

    expect($sheet->getCell('A'.$headerRow)->getValue())->toBe('IBU PEJABAT JKN')
        ->and(strtoupper($sheet->getStyle('A'.$headerRow)->getFill()->getStartColor()->getRGB()))->toBe('808080')
        ->and($sheet->getCell('A'.$secondBahagianRow)->getValue())->toBe(2)
        ->and($sheet->getCell('B'.$secondBahagianRow)->getValue())->toBe('BAHAGIAN PERUBATAN')
        ->and(strtoupper($sheet->getStyle('A'.$secondBahagianRow)->getFill()->getStartColor()->getRGB()))->not->toBe('808080')
        ->and(strtoupper($sheet->getStyle('A'.$ibu['jumlah_row'])->getFill()->getStartColor()->getRGB()))->toBe('CA9EB3');
});

test('role 3 data keseluruhan export only includes the user ptj', function () {
    $ownPtj = Ptj::query()->create([
        'nama_ptj' => 'HOSPITAL JITRA',
        'is_jkn' => false,
    ]);
    $otherPtj = Ptj::query()->create([
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

    foreach ([$ownPtj, $otherPtj] as $ptj) {
        WaranJawatan::query()->create([
            'ptj_id' => $ptj->id,
            'aktiviti_id' => $aktiviti->id,
            'status' => 'active',
        ]);
    }

    $user = User::query()->create([
        'name' => 'PTJ User',
        'email' => 'l1-ptj-user@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ownPtj->id,
        'role' => 3,
        'status' => 1,
    ]);

    $this->actingAs($user);

    $export = new DataKeseluruhanExport;
    $rows = $export->collection();

    $ptjLabels = collect($export->mergeRows)
        ->flatMap(fn (array $section) => $section['ptj_rows'])
        ->map(fn (int $rowNumber) => $rows[$rowNumber - 5][1])
        ->all();

    expect($ptjLabels)->toBe(['HOSPITAL JITRA'])
        ->and($ptjLabels)->not->toContain('HOSPITAL SULTANAH BAHIYAH');
});
