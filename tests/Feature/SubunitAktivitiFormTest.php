<?php

use App\Models\Aktiviti;
use App\Models\Program;
use App\Models\Ptj;
use App\Models\Subunit;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('subunits');
    Schema::dropIfExists('units');
    Schema::dropIfExists('program_ptj');
    Schema::dropIfExists('aktivitis');
    Schema::dropIfExists('programs');
    Schema::dropIfExists('ptjs');

    Schema::create('programs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_program');
        $table->string('desc_program')->nullable();
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id');
        $table->string('no_aktivit', 20);
        $table->string('nama_aktiviti')->nullable();
        $table->string('desc_aktiviti')->nullable();
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->tinyInteger('is_jkn')->default(0);
        $table->timestamps();
    });

    Schema::create('program_ptj', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id');
        $table->unsignedBigInteger('ptj_id');
        $table->timestamps();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit');
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('subunits', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('unit_id');
        $table->unsignedBigInteger('dun_id')->nullable();
        $table->string('nama_subunit')->nullable();
        $table->unsignedBigInteger('parlimen_id')->nullable();
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->timestamps();
    });
});

it('persists aktiviti_id on a subunit for a program aktiviti', function () {
    $program = Program::create([
        'nama_program' => 'PROGRAM UJIAN',
        'desc_program' => 'DESC',
    ]);

    $aktiviti = Aktiviti::create([
        'program_id' => $program->id,
        'no_aktivit' => '010101',
        'nama_aktiviti' => 'AKTIVITI UJIAN',
    ]);

    $ptj = Ptj::create([
        'nama_ptj' => 'PTJ UJIAN',
        'kod_ptj' => 1,
        'is_jkn' => 0,
    ]);
    $ptj->programs()->attach($program->id);

    $unit = Unit::create([
        'ptj_id' => $ptj->id,
        'nama_unit' => 'UNIT UJIAN',
    ]);

    $subunit = Subunit::create([
        'unit_id' => $unit->id,
        'nama_subunit' => 'SUBUNIT UJIAN',
        'aktiviti_id' => $aktiviti->id,
    ]);

    expect($subunit->fresh()->aktiviti_id)->toBe($aktiviti->id)
        ->and($subunit->fresh()->aktiviti?->nama_aktiviti)->toBe('AKTIVITI UJIAN');
});

it('can update a subunit aktiviti_id', function () {
    $program = Program::create([
        'nama_program' => 'PROGRAM EDIT',
        'desc_program' => 'DESC',
    ]);

    $aktiviti = Aktiviti::create([
        'program_id' => $program->id,
        'no_aktivit' => '020202',
        'nama_aktiviti' => 'AKTIVITI EDIT',
    ]);

    $ptj = Ptj::create([
        'nama_ptj' => 'PTJ EDIT',
        'kod_ptj' => 2,
        'is_jkn' => 0,
    ]);

    $unit = Unit::create([
        'ptj_id' => $ptj->id,
        'nama_unit' => 'UNIT EDIT',
    ]);

    $subunit = Subunit::create([
        'unit_id' => $unit->id,
        'nama_subunit' => 'SUBUNIT EDIT',
        'aktiviti_id' => null,
    ]);

    $subunit->update(['aktiviti_id' => $aktiviti->id]);

    expect($subunit->fresh()->aktiviti_id)->toBe($aktiviti->id);
});
