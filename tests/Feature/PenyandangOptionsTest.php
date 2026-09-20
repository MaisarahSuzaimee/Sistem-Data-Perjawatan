<?php

use App\Models\Pegawai;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('jawatan__greds');

    Schema::create('jawatan__greds', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('jawatan_id')->nullable();
        $table->unsignedBigInteger('gred_id')->nullable();
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table) {
        $table->id();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->boolean('is_kontrak')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('waran_jawatans', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('nama penyandang lists only unassigned pegawai with the same jawatan_gred', function () {
    $matching = Schema::getConnection()->table('jawatan__greds')->insertGetId([
        'jawatan_id' => 1,
        'gred_id' => 41,
    ]);
    $sameGredOtherJawatan = Schema::getConnection()->table('jawatan__greds')->insertGetId([
        'jawatan_id' => 2,
        'gred_id' => 41,
    ]);
    $otherGred = Schema::getConnection()->table('jawatan__greds')->insertGetId([
        'jawatan_id' => 1,
        'gred_id' => 54,
    ]);

    $availableId = Schema::getConnection()->table('pegawais')->insertGetId([
        'nama' => 'Ahmad',
        'nokp' => '800101010001',
        'jawatan_gred_id' => $matching,
        'is_kontrak' => false,
    ]);
    $assignedId = Schema::getConnection()->table('pegawais')->insertGetId([
        'nama' => 'Siti',
        'nokp' => '800101010002',
        'jawatan_gred_id' => $matching,
        'is_kontrak' => false,
    ]);
    $wrongJawatanId = Schema::getConnection()->table('pegawais')->insertGetId([
        'nama' => 'Ali',
        'nokp' => '800101010003',
        'jawatan_gred_id' => $sameGredOtherJawatan,
        'is_kontrak' => false,
    ]);
    $wrongGredId = Schema::getConnection()->table('pegawais')->insertGetId([
        'nama' => 'Rina',
        'nokp' => '800101010005',
        'jawatan_gred_id' => $otherGred,
        'is_kontrak' => false,
    ]);
    $currentId = Schema::getConnection()->table('pegawais')->insertGetId([
        'nama' => 'Murni',
        'nokp' => '800101010004',
        'jawatan_gred_id' => $matching,
        'is_kontrak' => false,
    ]);

    $currentWaranId = Schema::getConnection()->table('waran_jawatans')->insertGetId([
        'pegawai_id' => $currentId,
        'status' => 'active',
    ]);
    Schema::getConnection()->table('waran_jawatans')->insert([
        'pegawai_id' => $assignedId,
        'status' => 'active',
    ]);

    $options = Pegawai::penyandangOptions([1], [41], $currentWaranId, $currentId);

    expect(array_keys($options))->toContain($availableId, $currentId)
        ->and($options)->not->toHaveKey($assignedId)
        ->and($options)->not->toHaveKey($wrongJawatanId)
        ->and($options)->not->toHaveKey($wrongGredId);
});
