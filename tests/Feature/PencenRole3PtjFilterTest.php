<?php

use App\Filament\Resources\Pencens\Schemas\PencenForm;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
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
        ->and($options)->not->toHaveKey($otherPtj->id);
});
