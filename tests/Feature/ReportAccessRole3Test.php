<?php

use App\Filament\Pages\Report;
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

it('allows report page access for role 3 users', function () {
    $user = User::create([
        'name' => 'Role 3 User',
        'email' => 'role3@test.com',
        'password' => bcrypt('password'),
        'ptj_id' => 10,
        'role' => 3,
        'status' => 1,
    ]);

    auth()->login($user);

    expect(Report::canAccess())->toBeTrue()
        ->and($user->canAccessAllPtj())->toBeFalse()
        ->and($user->isPtjScoped())->toBeTrue();
});

it('allows report page access for admin role users', function () {
    foreach ([1, 2] as $role) {
        $user = User::create([
            'name' => "Admin Role {$role}",
            'email' => "admin{$role}@test.com",
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 1,
        ]);

        auth()->login($user);

        expect(Report::canAccess())->toBeTrue()
            ->and($user->canAccessAllPtj())->toBeTrue();
    }
});
