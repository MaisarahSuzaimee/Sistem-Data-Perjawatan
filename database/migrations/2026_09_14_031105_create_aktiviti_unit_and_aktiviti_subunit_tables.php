<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktiviti_unit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aktiviti_id')->constrained('aktivitis')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aktiviti_id', 'unit_id']);
        });

        Schema::create('aktiviti_subunit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aktiviti_id')->constrained('aktivitis')->cascadeOnDelete();
            $table->foreignId('subunit_id')->constrained('subunits')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aktiviti_id', 'subunit_id']);
        });

        $now = now();

        DB::table('units')
            ->whereNotNull('aktiviti_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $unit) use ($now): void {
                DB::table('aktiviti_unit')->insert([
                    'aktiviti_id' => $unit->aktiviti_id,
                    'unit_id' => $unit->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::table('subunits')
            ->whereNotNull('aktiviti_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $subunit) use ($now): void {
                DB::table('aktiviti_subunit')->insert([
                    'aktiviti_id' => $subunit->aktiviti_id,
                    'subunit_id' => $subunit->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('units', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('aktiviti_id');
        });

        Schema::table('subunits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('aktiviti_id');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->foreignId('aktiviti_id')->nullable()->constrained('aktivitis')->nullOnDelete();
        });

        Schema::table('subunits', function (Blueprint $table): void {
            $table->foreignId('aktiviti_id')->nullable()->constrained('aktivitis')->nullOnDelete();
        });

        foreach (DB::table('aktiviti_unit')->select('unit_id', DB::raw('MIN(aktiviti_id) as aktiviti_id'))->groupBy('unit_id')->get() as $row) {
            DB::table('units')->where('id', $row->unit_id)->update(['aktiviti_id' => $row->aktiviti_id]);
        }

        foreach (DB::table('aktiviti_subunit')->select('subunit_id', DB::raw('MIN(aktiviti_id) as aktiviti_id'))->groupBy('subunit_id')->get() as $row) {
            DB::table('subunits')->where('id', $row->subunit_id)->update(['aktiviti_id' => $row->aktiviti_id]);
        }

        Schema::dropIfExists('aktiviti_subunit');
        Schema::dropIfExists('aktiviti_unit');
    }
};
