<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class StressWaranJawatanSeeder extends Seeder
{
    use WithoutModelEvents;

    public const WARAN_NO = 'STRESS_SEED_WARAN';

    public const CATATAN_MARKER = '[STRESS_SEED]';

    /**
     * Seed ~15,000 waran_jawatans and assign stress pegawai.
     *
     * Usage:
     *   php artisan db:seed --class=Database\\Seeders\\StressWaranJawatanSeeder
     *
     * Optional:
     *   STRESS_WARAN_COUNT=15000 php artisan db:seed --class=Database\\Seeders\\StressWaranJawatanSeeder
     */
    public function run(): void
    {
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array.transport', 'array');
        Mail::fake();
        Notification::fake();

        $count = max(1, (int) env('STRESS_WARAN_COUNT', 15000));

        $aktivitiIds = DB::table('aktivitis')->orderBy('id')->pluck('id')->all();
        if ($aktivitiIds === []) {
            $this->command?->error('No aktiviti rows found. Aborting.');

            return;
        }

        $jawatanGredMap = DB::table('jawatan__greds')
            ->select(['id', 'jawatan_id', 'gred_id'])
            ->get()
            ->keyBy('id');

        if ($jawatanGredMap->isEmpty()) {
            $this->command?->error('No jawatan_gred rows found. Aborting.');

            return;
        }

        $pegawai = DB::table('pegawais')
            ->where('emel', StressTestDataSeeder::PEGAWAI_EMAIL_MARKER)
            ->where('is_kontrak', 0)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'ptj_id', 'jawatan_gred_id']);

        if ($pegawai->count() < $count) {
            $this->command?->error(
                "Need {$count} non-kontrak stress pegawai, found {$pegawai->count()}. ".
                'Run StressTestDataSeeder first or lower STRESS_WARAN_COUNT.'
            );

            return;
        }

        $this->command?->info('Mailer forced to: '.config('mail.default'));

        $existingWaranIds = DB::table('warans')
            ->where('no_waran', self::WARAN_NO)
            ->pluck('id')
            ->all();

        if ($existingWaranIds !== []) {
            $deleted = DB::table('waran_jawatans')
                ->whereIn('waran_id', $existingWaranIds)
                ->delete();
            DB::table('warans')->whereIn('id', $existingWaranIds)->delete();
            $this->command?->warn("Removed previous stress waran data ({$deleted} waran_jawatans).");
        }

        $waranId = DB::table('warans')->insertGetId([
            'no_waran' => self::WARAN_NO,
            'jenis' => 'Tambah',
            'jik' => $count,
            'catatan' => self::CATATAN_MARKER.' bulk waran for export timing',
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info("Created stress waran #{$waranId}. Seeding {$count} waran_jawatans...");

        $now = now();
        $chunkSize = 1000;
        $aktivitiCount = count($aktivitiIds);
        $selected = $pegawai->take($count)->values();

        for ($offset = 0; $offset < $count; $offset += $chunkSize) {
            $size = min($chunkSize, $count - $offset);
            $rows = [];

            for ($i = 0; $i < $size; $i++) {
                $n = $offset + $i + 1;
                $p = $selected[$offset + $i];
                $jg = $jawatanGredMap->get($p->jawatan_gred_id);

                $jawatanIds = $jg ? [(int) $jg->jawatan_id] : [1];
                $gredIds = $jg ? [(int) $jg->gred_id] : [1];

                $rows[] = [
                    'waran_id' => $waranId,
                    'ptj_id' => $p->ptj_id,
                    'bahagian_id' => null,
                    'unit_id' => null,
                    'subunit_id' => null,
                    'aktiviti_id' => $aktivitiIds[$n % $aktivitiCount],
                    'pegawai_id' => $p->id,
                    'jawatan_ids' => json_encode($jawatanIds),
                    'gred_ids' => json_encode($gredIds),
                    'jawatan_gred_id' => $p->jawatan_gred_id,
                    'is_kup' => 0,
                    'butiran' => '(STRESS '.$n.')',
                    'paparan_jawatan' => null,
                    'tarikh_kuatkuasa' => '2025-01-01',
                    'waran_tolak_id' => null,
                    'catatan_jawatan' => self::CATATAN_MARKER.' waran jawatan #'.$n,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                    'status' => 'active',
                ];
            }

            DB::table('waran_jawatans')->insert($rows);
            $this->command?->getOutput()?->write('.');
        }

        $this->command?->newLine();

        $assigned = DB::table('waran_jawatans')
            ->where('waran_id', $waranId)
            ->whereNotNull('pegawai_id')
            ->count();

        $this->command?->info("Done. waran_id={$waranId}, posts={$assigned}, all assigned to stress pegawai.");
        $this->command?->warn('No emails were sent (mailer=array + Mail::fake()).');
        $this->command?->warn('Cleanup later with: php artisan stress-data:clear');
    }
}
