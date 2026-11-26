<?php

use App\Filament\Resources\Warans\Pages\CreateWaran;
use App\Models\User;
use App\Models\Waran;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');
    Schema::dropIfExists('users');
    Schema::dropIfExists('notifications');

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

    Schema::create('warans', function (Blueprint $table): void {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->text('nama_waran')->nullable();
        $table->date('tarikh_kuatkuasa')->nullable();
        $table->string('jenis')->nullable();
        $table->integer('jik')->nullable();
        $table->integer('jik_tolak')->nullable();
        $table->text('catatan')->nullable();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->string('status')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('notifications', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });

    $this->actingAs(User::create([
        'name' => 'ADMIN JENIS',
        'email' => 'admin-jenis@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '770101015501',
        'status' => true,
        'role' => 1,
    ]));
});

it('creates a waran without jenis', function () {
    Livewire::test(CreateWaran::class)
        ->fillForm([
            'no_waran' => 'WP JENIS/2026',
            'nama_waran' => 'Waran Jenis Test',
            'tarikh_kuatkuasa' => '2026-03-15',
            'jik' => 2,
            'jik_tolak' => 1,
            'catatan' => 'Neutral waran',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $waran = Waran::query()->where('no_waran', 'WP JENIS/2026')->first();

    expect($waran)->not->toBeNull()
        ->and($waran->jik)->toBe(2)
        ->and($waran->jik_tolak)->toBe(1)
        ->and($waran->jenis)->toBeNull();
});
