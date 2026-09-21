<?php

use App\Models\Pegawai;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['waran_jawatans', 'warans', 'pegawais'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('pegawais', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->boolean('is_kontrak_isi_tetap')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('warans', function (Blueprint $table) {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('butiran')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

test('penempatan tab shows waran belum ditetapkan when tetap pegawai has no waran', function () {
    $pegawai = Pegawai::query()->create([
        'is_tetap' => true,
        'is_kontrak' => false,
    ]);

    $html = view('filament.infolists.penempatan-table', [
        'record' => $pegawai,
    ])->render();

    expect($html)->toContain('Waran belum ditetapkan')
        ->and($html)->not->toContain('No Waran');
});

test('penempatan tab shows waran details for tetap pegawai with assigned waran', function () {
    $pegawai = Pegawai::query()->create([
        'is_tetap' => true,
        'is_kontrak' => false,
    ]);
    $waran = Waran::query()->create(['no_waran' => 'WP 12/2026']);
    WaranJawatan::query()->create([
        'waran_id' => $waran->id,
        'pegawai_id' => $pegawai->id,
        'butiran' => '144',
        'status' => 'active',
    ]);

    $html = view('filament.infolists.penempatan-table', [
        'record' => $pegawai->fresh(),
    ])->render();

    expect($html)->toContain('WP 12/2026')
        ->and($html)->toContain('144')
        ->and($html)->not->toContain('Waran belum ditetapkan');
});

test('penempatan tab shows waran belum ditetapkan for kontrak interim without waran', function () {
    $pegawai = Pegawai::query()->create([
        'is_kontrak_interim' => true,
        'is_kontrak' => false,
        'is_tetap' => false,
    ]);

    $html = view('filament.infolists.penempatan-table', [
        'record' => $pegawai,
    ])->render();

    expect($html)->toContain('Waran belum ditetapkan');
});
