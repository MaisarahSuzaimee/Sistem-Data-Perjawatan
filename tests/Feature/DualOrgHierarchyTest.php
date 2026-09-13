<?php

use App\Models\Bahagian;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\Unit;
use App\Services\PrestasiService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('units');
    Schema::dropIfExists('bahagians');
    Schema::dropIfExists('ptjs');
    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->text('alamat')->nullable();
        $table->string('pengarah')->nullable();
        $table->tinyInteger('is_jkn')->default(0);
        $table->string('rujukan_surat', 35)->nullable();
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id');
        $table->string('nama_bahagian');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit');
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->unsignedBigInteger('subunit_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->string('nama');
        $table->string('nokp');
        $table->string('jantina')->nullable();
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->boolean('is_kontrak_isi_tetap')->default(false);
        $table->boolean('is_kup')->default(false);
        $table->boolean('is_kupj')->default(false);
        $table->boolean('is_jtw')->default(false);
        $table->boolean('ada_unit')->default(false);
        $table->boolean('ada_subunit')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('warans', function (Blueprint $table): void {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () {
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('units');
    Schema::dropIfExists('bahagians');
    Schema::dropIfExists('ptjs');
    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');
});

function makePtj(string $nama, bool $isJkn = false): Ptj
{
    return Ptj::query()->create([
        'nama_ptj' => $nama,
        'kod_ptj' => random_int(10000, 99999),
        'alamat' => 'Alamat ujian',
        'pengarah' => 'Pengarah',
        'is_jkn' => $isJkn ? 1 : 0,
    ]);
}

function assignWaran(Pegawai $pegawai): void
{
    $waranId = DB::table('warans')->insertGetId([
        'no_waran' => 'W-'.$pegawai->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('waran_jawatans')->insert([
        'waran_id' => $waranId,
        'ptj_id' => $pegawai->ptj_id,
        'pegawai_id' => $pegawai->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('flags bahagian hierarchy only for is_jkn PTJs including VEKTOR', function () {
    $jkn = makePtj('JABATAN KESIHATAN NEGERI KEDAH', true);
    $vektor = makePtj('JABATAN KESIHATAN NEGERI KEDAH (VEKTOR)', true);
    $hospital = makePtj('HOSPITAL BALING', false);

    expect($jkn->usesBahagianHierarchy())->toBeTrue()
        ->and($vektor->usesBahagianHierarchy())->toBeTrue()
        ->and($hospital->usesBahagianHierarchy())->toBeFalse()
        ->and(Ptj::usesBahagianHierarchyFor($jkn->id))->toBeTrue()
        ->and(Ptj::usesBahagianHierarchyFor($hospital->id))->toBeFalse()
        ->and(Ptj::usesBahagianHierarchyFor(null))->toBeFalse();
});

it('requires bahagian, unit/subunit, and waran for lengkap status', function () {
    $jkn = makePtj('JKN KEDAH', true);
    $hospital = makePtj('HOSPITAL X', false);

    $bahagian = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN A',
    ]);

    $unitJkn = Unit::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagian->id,
        'nama_unit' => 'UNIT JKN',
    ]);

    $unitHospital = Unit::query()->create([
        'ptj_id' => $hospital->id,
        'bahagian_id' => null,
        'nama_unit' => 'UNIT HOSPITAL',
    ]);

    $pegawaiJknMissingBahagian = Pegawai::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => null,
        'unit_id' => $unitJkn->id,
        'subunit_id' => 1,
        'ada_unit' => 0,
        'ada_subunit' => 0,
        'nama' => 'JKN TANPA BAHAGIAN',
        'nokp' => '111111111111',
        'jantina' => 'Lelaki',
        'is_jtw' => 0,
        'is_kontrak' => 0,
    ]);
    assignWaran($pegawaiJknMissingBahagian);

    $pegawaiJknCompleteNoWaran = Pegawai::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagian->id,
        'unit_id' => $unitJkn->id,
        'subunit_id' => 1,
        'ada_unit' => 0,
        'ada_subunit' => 0,
        'nama' => 'JKN TANPA WARAN',
        'nokp' => '222222222222',
        'jantina' => 'Perempuan',
        'is_jtw' => 0,
        'is_kontrak' => 0,
    ]);

    $pegawaiJknLengkap = Pegawai::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagian->id,
        'unit_id' => $unitJkn->id,
        'subunit_id' => 1,
        'ada_unit' => 0,
        'ada_subunit' => 0,
        'nama' => 'JKN LENGKAP',
        'nokp' => '333333333333',
        'jantina' => 'Perempuan',
        'is_jtw' => 0,
        'is_kontrak' => 0,
    ]);
    assignWaran($pegawaiJknLengkap);

    $pegawaiMissingUnit = Pegawai::query()->create([
        'ptj_id' => $hospital->id,
        'bahagian_id' => null,
        'unit_id' => null,
        'subunit_id' => null,
        'ada_unit' => 0,
        'ada_subunit' => 0,
        'nama' => 'HOSPITAL TIADA UNIT',
        'nokp' => '444444444444',
        'jantina' => 'Lelaki',
        'is_jtw' => 0,
        'is_kontrak' => 0,
    ]);
    assignWaran($pegawaiMissingUnit);

    $pegawaiHospitalTiadaUnitSubunit = Pegawai::query()->create([
        'ptj_id' => $hospital->id,
        'bahagian_id' => null,
        'unit_id' => null,
        'subunit_id' => null,
        'ada_unit' => 1,
        'ada_subunit' => 1,
        'nama' => 'HOSPITAL TIADA UNIT SUBUNIT',
        'nokp' => '555555555555',
        'jantina' => 'Lelaki',
        'is_jtw' => 0,
        'is_kontrak' => 0,
    ]);
    assignWaran($pegawaiHospitalTiadaUnitSubunit);

    $pegawaiHospitalWithUnit = Pegawai::query()->create([
        'ptj_id' => $hospital->id,
        'bahagian_id' => null,
        'unit_id' => $unitHospital->id,
        'subunit_id' => 1,
        'ada_unit' => 0,
        'ada_subunit' => 0,
        'nama' => 'HOSPITAL LENGKAP',
        'nokp' => '666666666666',
        'jantina' => 'Lelaki',
        'is_jtw' => 0,
        'is_kontrak' => 0,
    ]);
    assignWaran($pegawaiHospitalWithUnit);

    expect($pegawaiJknMissingBahagian->fresh(['ptj'])->isTidakLengkap())->toBeTrue()
        ->and($pegawaiJknCompleteNoWaran->fresh(['ptj'])->isTidakLengkap())->toBeTrue()
        ->and($pegawaiJknLengkap->fresh(['ptj'])->isTidakLengkap())->toBeFalse()
        ->and($pegawaiMissingUnit->fresh(['ptj'])->isTidakLengkap())->toBeTrue()
        ->and($pegawaiHospitalTiadaUnitSubunit->fresh(['ptj'])->isTidakLengkap())->toBeFalse()
        ->and($pegawaiHospitalWithUnit->fresh(['ptj'])->isTidakLengkap())->toBeFalse()
        ->and(Pegawai::query()->tidakLengkap()->pluck('id'))->toContain($pegawaiJknMissingBahagian->id)
        ->and(Pegawai::query()->tidakLengkap()->pluck('id'))->toContain($pegawaiJknCompleteNoWaran->id)
        ->and(Pegawai::query()->tidakLengkap()->pluck('id'))->toContain($pegawaiMissingUnit->id)
        ->and(Pegawai::query()->lengkap()->pluck('id'))->toContain($pegawaiJknLengkap->id)
        ->and(Pegawai::query()->lengkap()->pluck('id'))->toContain($pegawaiHospitalTiadaUnitSubunit->id)
        ->and(Pegawai::query()->lengkap()->pluck('id'))->toContain($pegawaiHospitalWithUnit->id);
});

it('resolves bahagian from prestasi only for jkn ptj', function () {
    $jkn = makePtj('JKN PRESTASI', true);
    $hospital = makePtj('HOSPITAL PRESTASI', false);

    Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'BAHAGIAN PRESTASI',
    ]);

    Bahagian::query()->create([
        'ptj_id' => $hospital->id,
        'nama_bahagian' => 'BAHAGIAN HOSPITAL',
    ]);

    expect(PrestasiService::resolveBahagianId('BAHAGIAN PRESTASI', $jkn->id))->not->toBeNull()
        ->and(PrestasiService::resolveBahagianId('BAHAGIAN HOSPITAL', $hospital->id))->toBeNull();
});

it('retires non-jkn bahagian and reparents units to ptj', function () {
    $jkn = makePtj('JKN KEEP', true);
    $hospital = makePtj('HOSPITAL RETIRE', false);

    $bahagianJkn = Bahagian::query()->create([
        'ptj_id' => $jkn->id,
        'nama_bahagian' => 'JKN BAHAGIAN',
    ]);

    $bahagianHospital = Bahagian::query()->create([
        'ptj_id' => $hospital->id,
        'nama_bahagian' => 'HOSPITAL BAHAGIAN',
    ]);

    $unitJkn = Unit::query()->create([
        'ptj_id' => $jkn->id,
        'bahagian_id' => $bahagianJkn->id,
        'nama_unit' => 'UNIT KEEP',
    ]);

    $unitHospital = Unit::query()->create([
        'ptj_id' => null,
        'bahagian_id' => $bahagianHospital->id,
        'nama_unit' => 'UNIT REPARENT',
    ]);

    Pegawai::query()->create([
        'ptj_id' => $hospital->id,
        'bahagian_id' => $bahagianHospital->id,
        'unit_id' => $unitHospital->id,
        'nama' => 'PEGAWAI HOSPITAL',
        'nokp' => '444444444444',
        'jantina' => 'Lelaki',
        'is_jtw' => 1,
        'ada_unit' => 0,
        'ada_subunit' => 1,
    ]);

    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_09_09_082830_retire_non_jkn_bahagian_hierarchy.php');
    $migration->up();

    expect(Bahagian::query()->find($bahagianJkn->id))->not->toBeNull()
        ->and(Bahagian::withTrashed()->find($bahagianHospital->id)?->trashed())->toBeTrue()
        ->and($unitJkn->fresh()->bahagian_id)->toBe($bahagianJkn->id)
        ->and($unitHospital->fresh()->ptj_id)->toBe($hospital->id)
        ->and($unitHospital->fresh()->bahagian_id)->toBeNull()
        ->and(DB::table('pegawais')->where('nokp', '444444444444')->value('bahagian_id'))->toBeNull();
});
