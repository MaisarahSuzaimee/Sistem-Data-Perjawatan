<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warans', function (Blueprint $table): void {
            if (! Schema::hasColumn('warans', 'tarikh_kuatkuasa')) {
                $table->date('tarikh_kuatkuasa')->nullable()->after('nama_waran');
            }
        });

        if (Schema::hasColumn('waran_jawatans', 'tarikh_kuatkuasa')) {
            $dates = DB::table('waran_jawatans')
                ->select('waran_id', DB::raw('MIN(tarikh_kuatkuasa) as tarikh_kuatkuasa'))
                ->whereNotNull('waran_id')
                ->whereNotNull('tarikh_kuatkuasa')
                ->groupBy('waran_id')
                ->get();

            foreach ($dates as $row) {
                DB::table('warans')
                    ->where('id', $row->waran_id)
                    ->whereNull('tarikh_kuatkuasa')
                    ->update(['tarikh_kuatkuasa' => $row->tarikh_kuatkuasa]);
            }

            Schema::table('waran_jawatans', function (Blueprint $table): void {
                $table->dropColumn('tarikh_kuatkuasa');
            });
        }
    }

    public function down(): void
    {
        Schema::table('waran_jawatans', function (Blueprint $table): void {
            if (! Schema::hasColumn('waran_jawatans', 'tarikh_kuatkuasa')) {
                $table->date('tarikh_kuatkuasa')->nullable()->after('butiran');
            }
        });

        if (Schema::hasColumn('warans', 'tarikh_kuatkuasa')) {
            $warans = DB::table('warans')
                ->select('id', 'tarikh_kuatkuasa')
                ->whereNotNull('tarikh_kuatkuasa')
                ->get();

            foreach ($warans as $waran) {
                DB::table('waran_jawatans')
                    ->where('waran_id', $waran->id)
                    ->whereNull('tarikh_kuatkuasa')
                    ->update(['tarikh_kuatkuasa' => $waran->tarikh_kuatkuasa]);
            }

            Schema::table('warans', function (Blueprint $table): void {
                $table->dropColumn('tarikh_kuatkuasa');
            });
        }
    }
};
