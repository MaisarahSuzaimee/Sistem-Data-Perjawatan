<?php

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Aktiviti;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\User;
use App\Policies\ProgramPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach ([
        'aktiviti_ptj',
        'program_ptj',
        'butirans',
        'aktivitis',
        'programs',
        'users',
        'ptjs',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->timestamps();
    });

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

    Schema::create('programs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_program');
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id');
        $table->string('no_aktivit', 20)->nullable();
        $table->string('nama_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('program_ptj', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id');
        $table->unsignedBigInteger('ptj_id');
        $table->timestamps();
    });

    Schema::create('butirans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->timestamps();
    });

    Schema::create('aktiviti_ptj', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('aktiviti_id');
        $table->unsignedBigInteger('ptj_id');
        $table->timestamps();
        $table->unique(['aktiviti_id', 'ptj_id']);
    });
});

function makeProgramUser(int $role, ?int $ptjId, string $email): User
{
    return User::create([
        'name' => $email,
        'email' => $email,
        'password' => bcrypt('password'),
        'ptj_id' => $ptjId,
        'role' => $role,
        'status' => 1,
    ]);
}

it('lists only programs and aktiviti assigned to a role 3 user ptj', function () {
    $ownPtj = Ptj::create(['nama_ptj' => 'HOSPITAL BALING', 'kod_ptj' => 1]);
    $otherPtj = Ptj::create(['nama_ptj' => 'HOSPITAL SUNGAI PETANI', 'kod_ptj' => 2]);

    $sharedProgram = Program::create(['nama_program' => 'PROGRAM 1', 'desc_program' => 'PENGURUSAN']);
    $ownAktiviti = Aktiviti::create([
        'program_id' => $sharedProgram->id,
        'no_aktivit' => '1.1',
        'nama_aktiviti' => 'PENGURUSAN HOSPITAL',
    ]);
    $otherAktiviti = Aktiviti::create([
        'program_id' => $sharedProgram->id,
        'no_aktivit' => '1.2',
        'nama_aktiviti' => 'PENGURUSAN LAIN',
    ]);
    $ownAktiviti->ptjs()->attach($ownPtj->id);
    $otherAktiviti->ptjs()->attach($otherPtj->id);

    $otherProgram = Program::create(['nama_program' => 'PROGRAM 2', 'desc_program' => 'KAWALAN']);
    $hiddenAktiviti = Aktiviti::create([
        'program_id' => $otherProgram->id,
        'no_aktivit' => '2.1',
        'nama_aktiviti' => 'KAWALAN',
    ]);
    $hiddenAktiviti->ptjs()->attach($otherPtj->id);

    $this->actingAs(makeProgramUser(3, $ownPtj->id, 'user-program@test.com'));

    $records = ProgramResource::getEloquentQuery()->get();

    expect($records->pluck('id')->all())->toBe([$sharedProgram->id])
        ->and($records->first()->aktiviti->pluck('id')->all())->toBe([$ownAktiviti->id])
        ->and($records->first()->aktiviti->first()->ptjs->pluck('id')->all())->toBe([$ownPtj->id]);
});

it('blocks create and edit for role 3 users and allows view of assigned programs only', function () {
    $ownPtj = Ptj::create(['nama_ptj' => 'HOSPITAL BALING', 'kod_ptj' => 1]);
    $otherPtj = Ptj::create(['nama_ptj' => 'HOSPITAL SUNGAI PETANI', 'kod_ptj' => 2]);

    $assigned = Program::create(['nama_program' => 'PROGRAM 1']);
    $assignedAktiviti = Aktiviti::create([
        'program_id' => $assigned->id,
        'no_aktivit' => '1.1',
        'nama_aktiviti' => 'PENGURUSAN',
    ]);
    $assignedAktiviti->ptjs()->attach($ownPtj->id);

    $unassigned = Program::create(['nama_program' => 'PROGRAM 2']);
    $unassignedAktiviti = Aktiviti::create([
        'program_id' => $unassigned->id,
        'no_aktivit' => '2.1',
        'nama_aktiviti' => 'KAWALAN',
    ]);
    $unassignedAktiviti->ptjs()->attach($otherPtj->id);

    $user = makeProgramUser(3, $ownPtj->id, 'user-program-policy@test.com');
    $admin = makeProgramUser(2, null, 'admin-program-policy@test.com');
    $policy = new ProgramPolicy;

    $this->actingAs($user);

    expect($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $assigned))->toBeFalse()
        ->and($policy->view($user, $assigned))->toBeTrue()
        ->and($policy->view($user, $unassigned))->toBeFalse()
        ->and(ProgramResource::canCreate())->toBeFalse()
        ->and(ProgramResource::canEdit($assigned))->toBeFalse();

    $this->actingAs($admin);

    expect($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $assigned))->toBeTrue()
        ->and(ProgramResource::canCreate())->toBeTrue()
        ->and(ProgramResource::canEdit($assigned))->toBeTrue()
        ->and(ProgramResource::getEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$assigned->id, $unassigned->id]);
});
