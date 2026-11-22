<?php

use App\Filament\Pages\Report;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

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
        'role' => 3,
        'status' => 1,
    ]);

    auth()->login($user);

    expect(Report::canAccess())->toBeTrue()
        ->and(Report::shouldRegisterNavigation())->toBeTrue();
});

it('allows report page access for admin role users', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'status' => 1,
    ]);

    auth()->login($user);

    expect(Report::canAccess())->toBeTrue();
});

it('shows only L1 and L2 for role 3 users on the report page', function () {
    config(['app.env' => 'local']);

    $user = User::create([
        'name' => 'Role 3 Viewer',
        'email' => 'role3-viewer@test.com',
        'password' => bcrypt('password'),
        'role' => 3,
        'status' => 1,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Report::class)->assertSuccessful();

    $ids = collect($component->instance()->getTableRecords())
        ->map(fn ($record) => is_array($record) ? (int) ($record['id'] ?? 0) : (int) $record->id)
        ->values()
        ->all();

    expect($ids)->toBe([1, 2]);

    $component
        ->assertSee('L1: Data Keseluruhan Mengikut PTJ')
        ->assertSee('L2: Data Perjawatan Kontrak')
        ->assertDontSee('L3: Data Mengikut Kumpulan Mengikut PTJ');
});
