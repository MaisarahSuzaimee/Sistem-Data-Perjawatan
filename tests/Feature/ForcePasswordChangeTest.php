<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.turnstile.secret_key' => null]);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Schema::dropIfExists('users');
    Schema::dropIfExists('ptjs');

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj')->nullable();
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
        $table->boolean('must_change_password')->default(false);
        $table->rememberToken();
        $table->string('nokp')->unique();
        $table->string('phone_number')->nullable();
        $table->boolean('status')->default(true);
        $table->integer('role')->default(3);
        $table->timestamps();
    });
});

function forcePasswordUser(array $overrides = []): User
{
    return User::factory()->mustChangePassword()->create(array_merge([
        'name' => 'PENGGUNA BARU',
        'nokp' => '880101011234',
        'password' => 'katalaluan-sementara',
        'ptj_id' => null,
        'phone_number' => '0123456789',
        'status' => true,
        'role' => 3,
    ], $overrides));
}

it('marks new factory users with mustChangePassword as forced', function () {
    $user = forcePasswordUser();

    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('redirects forced users away from the dashboard to the profile page', function () {
    $user = forcePasswordUser();

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect(Filament::getProfileUrl());
});

it('does not redirect forced users away from the profile page', function () {
    $user = forcePasswordUser();

    $response = $this->actingAs($user)
        ->get(Filament::getProfileUrl());

    expect($response->headers->get('Location'))->not->toBe('/app');
    expect($response->isRedirect())->toBeFalse();
});

it('does not force-redirect users who already changed their password', function () {
    $user = User::factory()->create([
        'nokp' => '660101011234',
        'password' => 'katalaluan-rahsia',
        'ptj_id' => null,
        'phone_number' => '0123456789',
        'status' => true,
        'role' => 3,
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($user)->get('/app');

    expect($response->headers->get('Location'))->not->toBe(Filament::getProfileUrl());
});

it('clears must_change_password after the password is updated', function () {
    $user = forcePasswordUser();

    expect($user->must_change_password)->toBeTrue();

    $user->update([
        'password' => 'katalaluan-baharu-123',
    ]);

    expect($user->fresh()->must_change_password)->toBeFalse();
});

it('redirects to profile after login when password must be changed', function () {
    $user = forcePasswordUser();

    Livewire::test(Login::class)
        ->fillForm([
            'nokp' => '880101011234',
            'password' => 'katalaluan-sementara',
        ])
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);

    $this->get('/app')
        ->assertRedirect(Filament::getProfileUrl());
});

it('requires a new password on the profile form when forced', function () {
    $user = forcePasswordUser();

    $this->actingAs($user);

    Livewire::test(EditProfile::class)
        ->assertFormFieldExists('password')
        ->call('save')
        ->assertHasFormErrors(['password']);
});
