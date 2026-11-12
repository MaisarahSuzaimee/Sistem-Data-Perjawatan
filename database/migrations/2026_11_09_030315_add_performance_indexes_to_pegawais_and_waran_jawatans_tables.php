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
        $this->deduplicateSoftDeletedPegawaiNokp();

        Schema::table('pegawais', function (Blueprint $table) {
            $table->unique('nokp', 'pegawais_nokp_unique');
            $table->index(['ptj_id', 'deleted_at'], 'pegawais_ptj_id_deleted_at_index');
            $table->index(['jawatan_gred_id', 'deleted_at'], 'pegawais_jawatan_gred_id_deleted_at_index');
        });

        Schema::table('waran_jawatans', function (Blueprint $table) {
            $table->index(['pegawai_id', 'deleted_at'], 'waran_jawatans_pegawai_id_deleted_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropUnique('pegawais_nokp_unique');
            $table->dropIndex('pegawais_ptj_id_deleted_at_index');
            $table->dropIndex('pegawais_jawatan_gred_id_deleted_at_index');
        });

        Schema::table('waran_jawatans', function (Blueprint $table) {
            $table->dropIndex('waran_jawatans_pegawai_id_deleted_at_index');
        });
    }

    /**
     * Soft-deleted duplicates keep the same nokp and would block a unique index.
     * Keep one row per nokp (prefer the active row) and rename the rest.
     */
    private function deduplicateSoftDeletedPegawaiNokp(): void
    {
        $duplicateNokps = DB::table('pegawais')
            ->select('nokp')
            ->groupBy('nokp')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nokp');

        foreach ($duplicateNokps as $nokp) {
            $rows = DB::table('pegawais')
                ->where('nokp', $nokp)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get(['id', 'nokp']);

            $keepId = $rows->first()?->id;

            foreach ($rows as $row) {
                if ($row->id === $keepId) {
                    continue;
                }

                DB::table('pegawais')
                    ->where('id', $row->id)
                    ->update([
                        'nokp' => $row->nokp.'-deleted-'.$row->id,
                    ]);
            }
        }
    }
};
