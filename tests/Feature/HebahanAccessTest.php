<?php

use App\Models\Hebahan;
use App\Models\User;
use App\Filament\Resources\Hebahans\HebahanResource;
use App\Policies\HebahanPolicy;

it('blocks role 3 users from accessing hebahan', function () {
    $user = User::make(['role' => 3]);
    $policy = new HebahanPolicy;
    auth()->login($user);

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, new Hebahan))->toBeFalse()
        ->and(HebahanResource::canAccess())->toBeFalse();
});

it('allows admin roles to access hebahan', function () {
    $policy = new HebahanPolicy;
    $admin = User::make(['role' => 1]);
    auth()->login($admin);

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->viewAny(User::make(['role' => 2])))->toBeTrue()
        ->and(HebahanResource::canAccess())->toBeTrue();
});
