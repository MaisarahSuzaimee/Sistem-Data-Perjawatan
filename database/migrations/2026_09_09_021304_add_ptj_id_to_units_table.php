<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('ptj_id')->nullable()->after('id');
        });

        // Backfill ptj_id from bahagians for existing units
        DB::table('units')
            ->join('bahagians', 'units.bahagian_id', '=', 'bahagians.id')
            ->whereNotNull('units.bahagian_id')
            ->update(['units.ptj_id' => DB::raw('bahagians.ptj_id')]);

        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('bahagian_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('ptj_id');
        });
    }
};
