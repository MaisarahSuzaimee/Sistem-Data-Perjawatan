<?php

use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('units');
    Schema::dropIfExists('bahagians');
    Schema::dropIfExists('ptjs');

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama_bahagian')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit');
        $table->timestamps();
        $table->softDeletes();
    });
});

it('matches a ptj group when a later unit name is searched', function () {
    $ptjId = Schema::getConnection()->table('ptjs')->insertGetId([
        'nama_ptj' => 'HOSPITAL BALING',
        'kod_ptj' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $first = Unit::query()->create([
        'ptj_id' => $ptjId,
        'nama_unit' => 'UNIT PERTAMA',
    ]);
    Unit::query()->create([
        'ptj_id' => $ptjId,
        'nama_unit' => 'KLINIK KESIHATAN DESA',
    ]);

    // Simulate grouped table row (only the first/MIN unit is in the result set).
    $oldStyle = Unit::query()
        ->whereKey($first->id)
        ->where('nama_unit', 'like', '%KLINIK%')
        ->get();

    $matched = UnitsTable::applyUnitNameSearch(
        Unit::query()->whereKey($first->id),
        'KLINIK'
    )->get();

    expect($oldStyle)->toHaveCount(0)
        ->and($matched)->toHaveCount(1)
        ->and($matched->first()->id)->toBe($first->id);
});

it('matches a bahagian group when a later unit name is searched', function () {
    $ptjId = Schema::getConnection()->table('ptjs')->insertGetId([
        'nama_ptj' => 'JKN KEDAH',
        'kod_ptj' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bahagianId = Schema::getConnection()->table('bahagians')->insertGetId([
        'ptj_id' => $ptjId,
        'nama_bahagian' => 'BAHAGIAN A',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $first = Unit::query()->create([
        'ptj_id' => $ptjId,
        'bahagian_id' => $bahagianId,
        'nama_unit' => 'UNIT ALPHA',
    ]);
    Unit::query()->create([
        'ptj_id' => $ptjId,
        'bahagian_id' => $bahagianId,
        'nama_unit' => 'UNIT BETA SEARCHABLE',
    ]);

    $matched = UnitsTable::applyUnitNameSearch(
        Unit::query()->whereKey($first->id),
        'BETA'
    )->get();

    expect($matched)->toHaveCount(1)
        ->and($matched->first()->id)->toBe($first->id);
});
