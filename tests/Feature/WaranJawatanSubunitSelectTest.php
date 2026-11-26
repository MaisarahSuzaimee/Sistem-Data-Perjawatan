<?php

use App\Filament\Resources\Warans\RelationManagers\WaranJawatansRelationManager;
use App\Models\Subunit;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['subunits', 'units'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('subunits', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->string('nama_subunit')->nullable();
        $table->timestamps();
    });
});

function normalizePlacement(array $data): array
{
    $manager = (new ReflectionClass(WaranJawatansRelationManager::class))
        ->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(WaranJawatansRelationManager::class, 'normalizePlacementFormData');

    return $method->invoke($manager, $data);
}

it('backfills jabatan from an existing kd kkia selection', function () {
    $unit = Unit::query()->create(['nama_unit' => 'UNIT SUMBER MANUSIA']);
    $subunit = Subunit::query()->create([
        'unit_id' => $unit->id,
        'nama_subunit' => 'UNIT PERJAWATAN',
    ]);

    $data = normalizePlacement([
        'unit_id' => null,
        'subunit_id' => $subunit->id,
    ]);

    expect($data['unit_id'])->toBe($unit->id)
        ->and($data['subunit_id'])->toBe($subunit->id);
});

it('clears a missing kd kkia id so the form does not keep a raw number', function () {
    $data = normalizePlacement([
        'unit_id' => null,
        'subunit_id' => 12,
    ]);

    expect($data['subunit_id'])->toBeNull();
});

it('keeps the current kd kkia label available when unit options are empty', function () {
    $unit = Unit::query()->create(['nama_unit' => 'UNIT SUMBER MANUSIA']);
    $subunit = Subunit::query()->create([
        'unit_id' => $unit->id,
        'nama_subunit' => 'UNIT PERJAWATAN',
    ]);

    $manager = (new ReflectionClass(WaranJawatansRelationManager::class))
        ->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(WaranJawatansRelationManager::class, 'withCurrentSubunitOption');

    $options = $method->invoke($manager, [], $subunit->id);

    expect($options)->toBe([$subunit->id => 'UNIT PERJAWATAN']);
});
