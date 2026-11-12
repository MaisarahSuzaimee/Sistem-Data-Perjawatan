<?php

use App\Filament\Resources\Pegawais\Schemas\PegawaiForm;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('users');

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
});

it('allows admin roles to autofill nokp data from any ptj', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin-nokp-autofill@test.com',
        'password' => bcrypt('password'),
        'role' => 2,
        'status' => 1,
    ]);

    $this->actingAs($admin);

    expect(PegawaiForm::canAutofillNokpForCurrentUser(99))->toBeTrue()
        ->and(PegawaiForm::canAutofillNokpForCurrentUser(null))->toBeTrue();
});

it('only allows role 3 users to autofill nokp data for their own ptj', function () {
    $user = User::create([
        'name' => 'User',
        'email' => 'user-nokp-autofill@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => 7,
        'role' => 3,
        'status' => 1,
    ]);

    $this->actingAs($user);

    expect(PegawaiForm::canAutofillNokpForCurrentUser(7))->toBeTrue()
        ->and(PegawaiForm::canAutofillNokpForCurrentUser(8))->toBeFalse()
        ->and(PegawaiForm::canAutofillNokpForCurrentUser(null))->toBeFalse();
});

it('blocks role 3 autofill when the user has no ptj', function () {
    $user = User::create([
        'name' => 'User No Ptj',
        'email' => 'user-nokp-no-ptj@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => null,
        'role' => 3,
        'status' => 1,
    ]);

    $this->actingAs($user);

    expect(PegawaiForm::canAutofillNokpForCurrentUser(7))->toBeFalse();
});

it('hides foreign ptj read-only labels on create and same-ptj edit for role 3', function () {
    Schema::dropIfExists('pegawais');
    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    $user = User::create([
        'name' => 'User Ptj Labels',
        'email' => 'user-ptj-labels@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => 7,
        'role' => 3,
        'status' => 1,
    ]);
    $this->actingAs($user);

    $samePtj = Pegawai::query()->create([
        'nama' => 'SAME PTJ',
        'nokp' => '111111111111',
        'ptj_id' => 7,
    ]);
    $otherPtj = Pegawai::query()->create([
        'nama' => 'OTHER PTJ',
        'nokp' => '222222222222',
        'ptj_id' => 8,
    ]);

    expect(PegawaiForm::shouldShowForeignPtjReadOnly(null))->toBeFalse()
        ->and(PegawaiForm::shouldShowForeignPtjReadOnly($samePtj))->toBeFalse()
        ->and(PegawaiForm::shouldShowForeignPtjReadOnly($otherPtj))->toBeTrue();
});
