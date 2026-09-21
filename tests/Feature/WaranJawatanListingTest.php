<?php

use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');

    Schema::create('warans', function (Blueprint $table) {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->string('jenis')->nullable();
        $table->integer('jik')->nullable();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->string('butiran')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('deleted waran jawatan is excluded from the list even when a pegawai is assigned', function () {
    $deletedId = Schema::getConnection()->table('waran_jawatans')->insertGetId([
        'pegawai_id' => 88,
        'butiran' => '157',
        'status' => 'active',
        'deleted_at' => now(),
    ]);
    $activeId = Schema::getConnection()->table('waran_jawatans')->insertGetId([
        'pegawai_id' => 87,
        'butiran' => '144',
        'status' => 'active',
    ]);

    $ids = WaranJawatan::query()->listed()->pluck('id');

    expect($ids->all())->toContain($activeId)
        ->and($ids->all())->not->toContain($deletedId);
});

test('waran table butiran list excludes a deleted waran jawatan', function () {
    $waranId = Schema::getConnection()->table('warans')->insertGetId([
        'no_waran' => 'WP 45/2025',
        'jenis' => 'Tambah',
    ]);

    Schema::getConnection()->table('waran_jawatans')->insert([
        'waran_id' => $waranId,
        'butiran' => '144',
        'status' => 'active',
    ]);
    Schema::getConnection()->table('waran_jawatans')->insert([
        'waran_id' => $waranId,
        'butiran' => '157',
        'status' => 'active',
        'deleted_at' => now(),
    ]);

    $butiran = Waran::query()->find($waranId)->butiran_list;

    expect($butiran)->toContain('144')
        ->and($butiran)->not->toContain('157');
});
