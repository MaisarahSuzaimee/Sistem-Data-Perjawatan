<?php

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
        $table->string('nokp')->nullable();
        $table->string('phone_number')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->boolean('must_change_password')->default(false);
        $table->tinyInteger('role')->default(3);
        $table->tinyInteger('status')->default(1);
        $table->rememberToken();
        $table->timestamps();
    });
});

function exportUser(int $role): User
{
    return User::query()->create([
        'name' => "Role {$role} User",
        'email' => "role{$role}@test.com",
        'password' => bcrypt('password'),
        'nokp' => str_pad((string) $role, 12, '0'),
        'phone_number' => '0123456789',
        'role' => $role,
        'status' => 1,
    ]);
}

$exportRoutes = [
    'export.letakJawatan',
    'export.penamatanPerkhidmatan',
    'export.dataKeseluruhan',
    'export.dataKontrak',
    'export.l3',
    'export.l4',
    'export.jikByJawatan',
    'export.l6',
    'export.l7',
    'export.l8',
    'export.fasilitiByParlimen',
    'export.users',
];

it('redirects guests away from all export routes', function () use ($exportRoutes) {
    foreach ($exportRoutes as $routeName) {
        $this->get(route($routeName))->assertRedirect('/app/login');
    }
});

it('forbids role 3 users from all export routes', function () use ($exportRoutes) {
    $this->actingAs(exportUser(3));

    foreach ($exportRoutes as $routeName) {
        $this->get(route($routeName))->assertForbidden();
    }
});

it('allows admin and superadmin past the export role gate', function () use ($exportRoutes) {
    foreach ([1, 2] as $role) {
        $this->actingAs(exportUser($role));

        foreach ($exportRoutes as $routeName) {
            $status = $this->get(route($routeName))->getStatusCode();

            expect($status)->not->toBe(403)
                ->and($status)->not->toBe(302);
        }
    }
});
