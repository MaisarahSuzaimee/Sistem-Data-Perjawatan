<?php

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Ptj;
use App\Models\Unit;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    foreach ([
        'aktiviti_unit',
        'aktiviti_ptj',
        'units',
        'bahagians',
        'duns',
        'parlimens',
        'aktivitis',
        'users',
        'ptjs',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->tinyInteger('is_jkn')->default(0);
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('email')->unique();
        $table->string('avatar')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->string('nokp')->unique();
        $table->string('phone_number')->nullable();
        $table->boolean('status')->default(true);
        $table->integer('role')->default(3);
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama_bahagian')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('parlimens', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_parlimen')->nullable();
        $table->timestamps();
    });

    Schema::create('duns', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('parlimen_id')->nullable();
        $table->string('nama_dun')->nullable();
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id')->nullable();
        $table->string('no_aktivit', 20)->nullable();
        $table->string('nama_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('aktiviti_ptj', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('aktiviti_id');
        $table->unsignedBigInteger('ptj_id');
        $table->timestamps();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit');
        $table->unsignedBigInteger('parlimen_id')->nullable();
        $table->unsignedBigInteger('dun_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('aktiviti_unit', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('aktiviti_id');
        $table->unsignedBigInteger('unit_id');
        $table->timestamps();
    });
});

it('redirects a role 3 user to edit when their ptj already has units', function () {
    $ptj = Ptj::query()->create([
        'nama_ptj' => 'HOSPITAL KUALA NERANG',
        'is_jkn' => 0,
    ]);

    $user = User::query()->create([
        'name' => 'USER UNIT',
        'email' => 'user-unit@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '880101015501',
        'status' => true,
        'role' => 3,
        'ptj_id' => $ptj->id,
    ]);

    $unit = Unit::query()->create([
        'ptj_id' => $ptj->id,
        'nama_unit' => 'JABATAN PESAKIT LUAR',
    ]);

    $this->actingAs($user);

    Livewire::test(CreateUnit::class)
        ->assertRedirect(UnitResource::getUrl('edit', ['record' => $unit]));

    expect(session('filament.notifications') ?? [])->toHaveCount(1);

    Notification::assertNotified('Data Telah Wujud');
});

it('does not redirect a role 3 user when their ptj has no units', function () {
    $ptj = Ptj::query()->create([
        'nama_ptj' => 'HOSPITAL KULIM',
        'is_jkn' => 0,
    ]);

    $user = User::query()->create([
        'name' => 'USER UNIT EMPTY',
        'email' => 'user-unit-empty@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '880101015502',
        'status' => true,
        'role' => 3,
        'ptj_id' => $ptj->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CreateUnit::class)
        ->assertOk()
        ->assertNoRedirect();
});
