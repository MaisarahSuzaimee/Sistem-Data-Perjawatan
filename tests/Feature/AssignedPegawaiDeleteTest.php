<?php

use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');

    Schema::create('warans', function (Blueprint $table): void {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->string('jenis')->nullable();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->string('butiran')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

it('does not delete a waran jawatan that has a pegawai', function () {
    $waran = Waran::create(['no_waran' => 'W1', 'jenis' => 'Tambah']);
    $jawatan = WaranJawatan::create([
        'waran_id' => $waran->id,
        'pegawai_id' => 9,
        'butiran' => '001',
        'status' => 'active',
    ]);

    expect($jawatan->delete())->toBeFalse()
        ->and(WaranJawatan::query()->whereKey($jawatan->id)->exists())->toBeTrue();
});

it('deletes a waran jawatan that has no pegawai', function () {
    $waran = Waran::create(['no_waran' => 'W2', 'jenis' => 'Tambah']);
    $jawatan = WaranJawatan::create([
        'waran_id' => $waran->id,
        'pegawai_id' => null,
        'butiran' => '002',
        'status' => 'active',
    ]);

    expect($jawatan->delete())->toBeTrue()
        ->and(WaranJawatan::query()->whereKey($jawatan->id)->exists())->toBeFalse();
});

it('does not delete a waran when a jawatan has a pegawai', function () {
    $waran = Waran::create(['no_waran' => 'W3', 'jenis' => 'Tambah']);
    WaranJawatan::create([
        'waran_id' => $waran->id,
        'pegawai_id' => 4,
        'butiran' => '003',
        'status' => 'active',
    ]);

    expect($waran->delete())->toBeFalse()
        ->and(Waran::query()->whereKey($waran->id)->exists())->toBeTrue();
});
