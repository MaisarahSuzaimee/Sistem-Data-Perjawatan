<?php

use App\Filament\Resources\Subunits\Tables\SubunitsTable;
use App\Models\Subunit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('subunits');
    Schema::dropIfExists('units');

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_unit')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('subunits', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->string('nama_subunit');
        $table->timestamps();
    });
});

it('matches a unit group when a later subunit name is searched', function () {
    $unitId = Schema::getConnection()->table('units')->insertGetId([
        'nama_unit' => 'UNIT A',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $first = Subunit::query()->create([
        'unit_id' => $unitId,
        'nama_subunit' => 'WAD PERTAMA',
    ]);
    Subunit::query()->create([
        'unit_id' => $unitId,
        'nama_subunit' => 'KKIA DESA',
    ]);

    $oldStyle = Subunit::query()
        ->whereKey($first->id)
        ->where('nama_subunit', 'like', '%KKIA%')
        ->get();

    $matched = SubunitsTable::applySubunitNameSearch(
        Subunit::query()->whereKey($first->id),
        'KKIA'
    )->get();

    expect($oldStyle)->toHaveCount(0)
        ->and($matched)->toHaveCount(1)
        ->and($matched->first()->id)->toBe($first->id);
});
