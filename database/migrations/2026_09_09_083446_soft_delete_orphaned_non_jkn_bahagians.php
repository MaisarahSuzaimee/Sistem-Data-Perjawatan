<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Soft-delete bahagians whose PTJ is missing or not JKN (leftover orphans).
     */
    public function up(): void
    {
        $orphanIds = DB::table('bahagians as b')
            ->leftJoin('ptjs as p', 'p.id', '=', 'b.ptj_id')
            ->whereNull('b.deleted_at')
            ->where(function ($q): void {
                $q->whereNull('p.id')
                    ->orWhere('p.is_jkn', 0);
            })
            ->pluck('b.id');

        if ($orphanIds->isEmpty()) {
            return;
        }

        DB::table('units')
            ->whereIn('bahagian_id', $orphanIds)
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

        DB::table('bahagians')
            ->whereIn('id', $orphanIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible cleanup of orphaned bahagians.
    }
};
