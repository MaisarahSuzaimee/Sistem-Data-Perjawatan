<?php

use App\Filament\Resources\Pegawais\Schemas\PegawaiForm;
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

test('kontrak aktiviti options only include aktiviti assigned to the selected ptj', function () {
    $program = Program::query()->create(['nama_program' => 'PROGRAM 2']);
    $assigned = Aktiviti::query()->create([
        'program_id' => $program->id,
        'no_aktivit' => '2.2.1',
        'nama_aktiviti' => 'PENGURUSAN HOSPITAL',
    ]);
    Aktiviti::query()->create([
        'program_id' => $program->id,
        'no_aktivit' => '2.3.1',
        'nama_aktiviti' => 'RAWATAN KECEMASAN',
    ]);

    $ptj = Ptj::query()->create(['nama_ptj' => 'HOSPITAL KULIM', 'kod_ptj' => 5]);
    $program->ptjs()->attach($ptj->id);
    $assigned->ptjs()->attach($ptj->id);

    expect(PegawaiForm::kontrakAktivitiOptions($ptj->id))->toBe([
        $assigned->id => '2.2.1 - PENGURUSAN HOSPITAL',
    ])
        ->and(PegawaiForm::kontrakAktivitiOptions(null))->toBe([])
        ->and(PegawaiForm::kontrakAktivitiOptions(''))->toBe([]);
});
