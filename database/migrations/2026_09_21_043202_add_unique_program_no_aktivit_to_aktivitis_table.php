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
        $duplicates = DB::table('aktivitis')
            ->select('program_id', 'no_aktivit')
            ->groupBy('program_id', 'no_aktivit')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('aktivitis')
                ->where('program_id', $duplicate->program_id)
                ->where('no_aktivit', $duplicate->no_aktivit)
                ->orderBy('id')
                ->get();

            $keepId = $rows
                ->sortByDesc(function ($row) {
                    $ptjLinks = DB::table('aktiviti_ptj')->where('aktiviti_id', $row->id)->count();
                    $waranLinks = Schema::hasTable('waran_jawatans')
                        ? DB::table('waran_jawatans')->where('aktiviti_id', $row->id)->count()
                        : 0;

                    return $ptjLinks + $waranLinks;
                })
                ->first()
                ->id;

            $deleteIds = $rows->pluck('id')->reject(fn ($id) => $id === $keepId)->values();

            foreach ($deleteIds as $deleteId) {
                if (Schema::hasTable('aktiviti_ptj')) {
                    DB::table('aktiviti_ptj')->where('aktiviti_id', $deleteId)->delete();
                }

                if (Schema::hasTable('aktiviti_unit')) {
                    DB::table('aktiviti_unit')->where('aktiviti_id', $deleteId)->delete();
                }

                if (Schema::hasTable('aktiviti_subunit')) {
                    DB::table('aktiviti_subunit')->where('aktiviti_id', $deleteId)->delete();
                }

                if (Schema::hasTable('butirans')) {
                    DB::table('butirans')->where('aktiviti_id', $deleteId)->delete();
                }

                if (Schema::hasTable('waran_jawatans')) {
                    DB::table('waran_jawatans')->where('aktiviti_id', $deleteId)->update(['aktiviti_id' => $keepId]);
                }

                DB::table('aktivitis')->where('id', $deleteId)->delete();
            }
        }

        Schema::table('aktivitis', function (Blueprint $table) {
            $table->unique(['program_id', 'no_aktivit'], 'aktivitis_program_id_no_aktivit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aktivitis', function (Blueprint $table) {
            $table->dropUnique('aktivitis_program_id_no_aktivit_unique');
        });
    }
};
