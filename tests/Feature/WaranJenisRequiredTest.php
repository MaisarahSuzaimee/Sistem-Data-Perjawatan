<?php

use App\Filament\Resources\Warans\Pages\CreateWaran;
use App\Models\User;
use App\Models\Waran;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    Schema::dropIfExists('warans');
    Schema::dropIfExists('users');

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
        $table->string('jenis')->nullable();
        $table->integer('jik')->nullable();
        $table->text('catatan')->nullable();
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

it('requires jenis when creating a waran', function () {
    Livewire::test(CreateWaran::class)
        ->fillForm([
            'no_waran' => 'WP JENIS/2026',
            'jik' => 2,
            'jenis' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['jenis' => 'required']);

    expect(Waran::query()->where('no_waran', 'WP JENIS/2026')->exists())->toBeFalse();
});
