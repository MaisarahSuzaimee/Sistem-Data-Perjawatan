<?php

use App\Filament\Resources\Pencens\Schemas\PencenForm;
use App\Models\LetakJawatan;
use App\Models\Pegawai;
use App\Models\Pencen;
use App\Models\Ptj;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('pencens');
    Schema::dropIfExists('letak_jawatans');
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('ptjs');
    Schema::dropIfExists('users');

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama');
        $table->string('nokp')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('pencens', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->timestamps();
    });

    Schema::create('letak_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->tinyInteger('role')->default(3);
        $table->timestamps();
    });
});

it('shows only pegawai from the same ptj for role 3 users creating a pencen', function () {
    $ptjA = Ptj::create(['nama_ptj' => 'PTJ A']);
    $ptjB = Ptj::create(['nama_ptj' => 'PTJ B']);

    $samePtj = Pegawai::create([
        'ptj_id' => $ptjA->id,
        'nama' => 'Pegawai Sama PTJ',
        'nokp' => '760101010101',
    ]);

    $otherPtj = Pegawai::create([
        'ptj_id' => $ptjB->id,
        'nama' => 'Pegawai Lain PTJ',
        'nokp' => '760202020202',
    ]);

    $user = User::create([
        'name' => 'Role 3 User',
        'email' => 'role3@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ptjA->id,
        'role' => 3,
    ]);

    auth()->login($user);

    $options = PencenForm::pegawaiOptionsForCurrentUser();

    expect($options)->toHaveKey($samePtj->id)
        ->and($options[$samePtj->id])->toBe('Pegawai Sama PTJ (760101010101)')
        ->and($options)->not->toHaveKey($otherPtj->id);
});

it('hides soft-deleted pegawai and pegawai who already exist in letak jawatan', function () {
    $ptj = Ptj::create(['nama_ptj' => 'PTJ A']);

    $active = Pegawai::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Pegawai Aktif',
        'nokp' => '800101010101',
    ]);

    $trashed = Pegawai::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Pegawai Dipadam',
        'nokp' => '800202020202',
    ]);
    $trashed->delete();

    $resigned = Pegawai::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Pegawai Letak Jawatan',
        'nokp' => '800303030303',
    ]);

    LetakJawatan::create([
        'ptj_id' => $ptj->id,
        'nama' => $resigned->nama,
        'nokp' => $resigned->nokp,
    ]);

    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ptj->id,
        'role' => 1,
    ]);

    auth()->login($user);

    $options = PencenForm::pegawaiOptionsForCurrentUser();

    expect($options)->toHaveKey($active->id)
        ->and($options)->not->toHaveKey($trashed->id)
        ->and($options)->not->toHaveKey($resigned->id);
});

it('hides pegawai whose name or nokp already exists in pencens', function () {
    $ptj = Ptj::create(['nama_ptj' => 'PTJ A']);

    $available = Pegawai::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Pegawai Baharu',
        'nokp' => '810101010101',
    ]);

    $sameName = Pegawai::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Pegawai Sudah Pencen',
        'nokp' => '810202020202',
    ]);

    $sameNokp = Pegawai::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Nama Berbeza',
        'nokp' => '810303030303',
    ]);

    Pencen::create([
        'ptj_id' => $ptj->id,
        'nama' => $sameName->nama,
        'nokp' => '999999999999',
    ]);

    Pencen::create([
        'ptj_id' => $ptj->id,
        'nama' => 'Rekod Lama',
        'nokp' => $sameNokp->nokp,
    ]);

    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin-pencen@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => $ptj->id,
        'role' => 1,
    ]);

    auth()->login($user);

    $options = PencenForm::pegawaiOptionsForCurrentUser();

    expect($options)->toHaveKey($available->id)
        ->and($options)->not->toHaveKey($sameName->id)
        ->and($options)->not->toHaveKey($sameNokp->id);
});
