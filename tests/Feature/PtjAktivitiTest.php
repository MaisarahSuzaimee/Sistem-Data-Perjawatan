<?php

use App\Models\Aktiviti;
use App\Models\Program;
use App\Models\Ptj;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('aktiviti_ptj');
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
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->timestamps();
    });

    Schema::create('program_ptj', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id');
        $table->unsignedBigInteger('ptj_id');
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

it('attaches multiple programs and aktiviti to a ptj', function () {
    $programA = Program::create(['nama_program' => 'PROGRAM A']);
    $programB = Program::create(['nama_program' => 'PROGRAM B']);

    $aktivitiA = Aktiviti::create([
        'program_id' => $programA->id,
        'no_aktivit' => '010101',
        'nama_aktiviti' => 'AKTIVITI A',
    ]);
    $aktivitiB = Aktiviti::create([
        'program_id' => $programB->id,
        'no_aktivit' => '020201',
        'nama_aktiviti' => 'AKTIVITI B',
    ]);

    $ptj = Ptj::create([
        'nama_ptj' => 'PTJ UJIAN',
        'kod_ptj' => 1,
    ]);

    $ptj->programs()->attach([$programA->id, $programB->id]);
    $ptj->aktivitis()->attach([$aktivitiA->id, $aktivitiB->id]);

    expect($ptj->programs()->count())->toBe(2)
        ->and($ptj->aktivitis()->pluck('nama_aktiviti')->all())->toBe(['AKTIVITI A', 'AKTIVITI B']);
});
