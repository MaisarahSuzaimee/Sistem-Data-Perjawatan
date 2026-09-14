<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire Bahagian under non-JKN PTJs (Hierarchy 2).
     * JKN PTJs (is_jkn = 1) keep Hierarchy 1: PTJ → Bahagian → Unit → Subunit.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasColumn('pegawais', 'bahagian_id')) {
                DB::statement('ALTER TABLE pegawais MODIFY bahagian_id INT NULL');
            }

            if (Schema::hasColumn('waran_jawatans', 'bahagian_id')) {
                DB::statement('ALTER TABLE waran_jawatans MODIFY bahagian_id INT NULL');
            }
        }

        $nonJknPtjIds = DB::table('ptjs')
            ->where('is_jkn', 0)
            ->pluck('id');

        if ($nonJknPtjIds->isEmpty()) {
            return;
        }

        $nonJknBahagianIds = DB::table('bahagians')
            ->whereIn('ptj_id', $nonJknPtjIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        // Ensure units under non-JKN bahagian have ptj_id, then detach bahagian.
        if ($nonJknBahagianIds->isNotEmpty()) {
            DB::table('units')
                ->whereIn('bahagian_id', $nonJknBahagianIds)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->chunkById(200, function ($units): void {
                    foreach ($units as $unit) {
                        $ptjId = DB::table('bahagians')->where('id', $unit->bahagian_id)->value('ptj_id');

                        DB::table('units')
                            ->where('id', $unit->id)
                            ->update([
                                'ptj_id' => $unit->ptj_id ?: $ptjId,
                                'bahagian_id' => null,
                                'updated_at' => now(),
                            ]);
                    }
                });
        }

        // Also clear bahagian_id on any remaining non-JKN units that already have ptj_id.
        DB::table('units')
            ->whereIn('ptj_id', $nonJknPtjIds)
            ->whereNotNull('bahagian_id')
            ->update([
                'bahagian_id' => null,
                'updated_at' => now(),
            ]);

        if (Schema::hasColumn('pegawais', 'bahagian_id')) {
            DB::table('pegawais')
                ->whereIn('ptj_id', $nonJknPtjIds)
                ->whereNotNull('bahagian_id')
                ->update([
                    'bahagian_id' => null,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasColumn('waran_jawatans', 'bahagian_id')) {
            DB::table('waran_jawatans')
                ->whereIn('ptj_id', $nonJknPtjIds)
                ->whereNotNull('bahagian_id')
                ->update([
                    'bahagian_id' => null,
                    'updated_at' => now(),
                ]);
        }

        DB::table('bahagians')
            ->whereIn('ptj_id', $nonJknPtjIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Soft-deleted non-JKN bahagians can be restored manually if needed;
        // unit/pegawai bahagian links are not reconstructed.
        DB::table('bahagians')
            ->whereNotNull('deleted_at')
            ->whereIn('ptj_id', function ($query): void {
                $query->select('id')->from('ptjs')->where('is_jkn', 0);
            })
            ->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }
};
