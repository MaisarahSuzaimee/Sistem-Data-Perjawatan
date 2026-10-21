<?php

use App\Filament\Resources\Pegawais\Schemas\PegawaiForm;
use App\Models\Pegawai;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('pegawais');

    Schema::create('pegawais', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->string('jantina')->nullable();
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak')->default(false);
        $table->boolean('is_kontrak_interim')->default(false);
        $table->boolean('is_kontrak_isi_tetap')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });
});

test('nokpAlreadyTaken ignores the pegawai being edited', function () {
    $pegawai = Pegawai::query()->create([
        'nama' => 'PEGAWAI KONTRAK',
        'nokp' => '567890123456',
        'is_kontrak' => true,
    ]);

    expect(PegawaiForm::nokpAlreadyTaken('567890123456', $pegawai->id))->toBeFalse()
        ->and(PegawaiForm::nokpAlreadyTaken('567890123456', null))->toBeTrue();
});

test('nokpAlreadyTaken detects another pegawai with the same nokp', function () {
    Pegawai::query()->create([
        'nama' => 'PEGAWAI LAMA',
        'nokp' => '678901234567',
        'is_kontrak' => true,
    ]);

    $other = Pegawai::query()->create([
        'nama' => 'PEGAWAI BARU',
        'nokp' => '789012345678',
        'is_kontrak' => true,
    ]);

    expect(PegawaiForm::nokpAlreadyTaken('678901234567', $other->id))->toBeTrue()
        ->and(PegawaiForm::nokpAlreadyTaken('789012345678', $other->id))->toBeFalse();
});

test('nokpAlreadyTaken ignores soft-deleted duplicates of the same nokp', function () {
    Pegawai::query()->create([
        'nama' => 'PEGAWAI LAMA',
        'nokp' => '970304025045',
        'is_kontrak' => true,
    ])->delete();

    Pegawai::query()->create([
        'nama' => 'PEGAWAI LAMA',
        'nokp' => '970304025045',
        'is_kontrak' => true,
    ])->delete();

    $active = Pegawai::query()->create([
        'nama' => 'AMMAR BIN MOHAMMAD AZHAR',
        'nokp' => '970304025045',
        'is_kontrak' => true,
    ]);

    expect(PegawaiForm::nokpAlreadyTaken('970304025045', $active->id))->toBeFalse()
        ->and(PegawaiForm::nokpAlreadyTaken('970304025045', null))->toBeTrue();
});
