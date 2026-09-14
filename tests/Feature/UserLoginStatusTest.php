<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);
    config(['services.turnstile.secret_key' => null]);
    Filament::setCurrentPanel(Filament::getPanel('app'));

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
});

it('allows an active user to authenticate', function () {
    User::factory()->create([
        'nokp' => '900101011111',
        'ptj_id' => 1,
        'phone_number' => '0123456789',
        'status' => 1,
        'role' => 3,
        'password' => 'password',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'nokp' => '900101011111',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->user()?->nokp)->toBe('900101011111');
});

it('rejects an inactive user and shows the inactive message', function () {
    User::factory()->create([
        'nokp' => '900101022222',
        'ptj_id' => 1,
        'phone_number' => '0123456789',
        'status' => 0,
        'role' => 3,
        'password' => 'password',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'nokp' => '900101022222',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['nokp'])
        ->assertSee('No. Kad Pengenalan atau kata laluan tidak sah.')
        ->assertDontSee('Akaun tidak aktif.');

    expect(auth()->check())->toBeFalse();
});

it('denies panel access when status is not 1', function () {
    $panel = Filament::getPanel('app');

    $active = User::factory()->make(['status' => 1]);
    $inactive = User::factory()->make(['status' => 0]);

    expect($active->canAccessPanel($panel))->toBeTrue()
        ->and($inactive->canAccessPanel($panel))->toBeFalse();
});
