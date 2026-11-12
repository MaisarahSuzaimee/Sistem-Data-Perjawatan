<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class StressTestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PEGAWAI_EMAIL_MARKER = 'stress-seed@example.invalid';

    public const LETAK_ALASAN_MARKER = '[STRESS_SEED]';

    /**
     * Seed large datasets for local export timing checks.
     *
     * Usage:
     *   php artisan db:seed --class=Database\\Seeders\\StressTestDataSeeder
     *
     * Optional:
     *   STRESS_COUNT=20000 php artisan db:seed --class=Database\\Seeders\\StressTestDataSeeder
     *   STRESS_SKIP_LETAK=1 php artisan db:seed --class=Database\\Seeders\\StressTestDataSeeder
     */
    public function run(): void
    {
        // Hard-disable outbound mail for this command, regardless of .env.
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array.transport', 'array');
        Mail::fake();
        Notification::fake();

        $count = max(1, (int) env('STRESS_COUNT', 20000));
        $seedLetak = ! filter_var(env('STRESS_SKIP_LETAK', false), FILTER_VALIDATE_BOOLEAN);

        $ptjIds = DB::table('ptjs')->orderBy('id')->pluck('id')->all();
        $jawatanGredIds = DB::table('jawatan__greds')->orderBy('id')->pluck('id')->all();

        if ($ptjIds === [] || $jawatanGredIds === []) {
            $this->command?->error('PTJ or jawatan_gred reference data is missing. Aborting.');

            return;
        }

        $this->command?->info('Mailer forced to: '.config('mail.default'));
        $this->command?->info("Seeding {$count} pegawai rows (marker email: ".self::PEGAWAI_EMAIL_MARKER.')...');

        $this->seedPegawais($count, $ptjIds, $jawatanGredIds);

        if ($seedLetak) {
            $this->command?->info("Seeding {$count} letak_jawatans rows (marker: ".self::LETAK_ALASAN_MARKER.')...');
            $this->seedLetakJawatans($count, $ptjIds, $jawatanGredIds);
        }

        $pegawaiTotal = DB::table('pegawais')->count();
        $letakTotal = Schema::hasTable('letak_jawatans')
            ? DB::table('letak_jawatans')->count()
            : 0;

        $this->command?->info("Done. pegawais={$pegawaiTotal}, letak_jawatans={$letakTotal}");
        $this->command?->warn('No emails were sent (mailer=array + Mail::fake()).');
        $this->command?->warn('Cleanup later with: php artisan stress-data:clear');
    }

    /**
     * @param  list<int|string>  $ptjIds
     * @param  list<int|string>  $jawatanGredIds
     */
    private function seedPegawais(int $count, array $ptjIds, array $jawatanGredIds): void
    {
        $existingStress = (int) DB::table('pegawais')
            ->where('emel', self::PEGAWAI_EMAIL_MARKER)
            ->count();

        if ($existingStress > 0) {
            $this->command?->warn("Found {$existingStress} existing stress pegawai rows. Removing them first...");
            DB::table('pegawais')->where('emel', self::PEGAWAI_EMAIL_MARKER)->delete();
        }

        $now = now();
        $chunkSize = 1000;
        $ptjCount = count($ptjIds);
        $jgCount = count($jawatanGredIds);

        for ($offset = 0; $offset < $count; $offset += $chunkSize) {
            $size = min($chunkSize, $count - $offset);
            $rows = [];

            for ($i = 0; $i < $size; $i++) {
                $n = $offset + $i + 1;
                $isTetap = $n % 5 !== 0;

                $rows[] = [
                    'ptj_id' => $ptjIds[$n % $ptjCount],
                    'bahagian_id' => null,
                    'unit_id' => null,
                    'subunit_id' => null,
                    'jawatan_gred_id' => $jawatanGredIds[$n % $jgCount],
                    'opsyen_pencen_id' => null,
                    'nama' => 'STRESS PEGAWAI '.$n,
                    // Unique 12-digit NOKP reserved for stress data (99xxxxxxxxxx).
                    'nokp' => sprintf('99%010d', $n),
                    'jantina' => $n % 2 === 0 ? 'L' : 'P',
                    'tarikh_lantikan' => '2018-01-15',
                    'tarikh_sah_jawatan' => '2018-07-01',
                    'tarikh_pencen' => '2048-01-15',
                    'is_tetap' => $isTetap ? 1 : 0,
                    'is_kontrak_interim' => 0,
                    'is_kontrak' => $isTetap ? 0 : 1,
                    'is_kontrak_isi_tetap' => 0,
                    'is_kup' => 0,
                    'is_kupj' => 0,
                    'is_jtw' => 0,
                    'tarikh_pinjam' => null,
                    'tarikh_sandang' => null,
                    'emel' => self::PEGAWAI_EMAIL_MARKER,
                    'ada_unit' => 1,
                    'ada_subunit' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('pegawais')->insert($rows);
            $this->command?->getOutput()?->write('.');
        }

        $this->command?->newLine();
    }

    /**
     * @param  list<int|string>  $ptjIds
     * @param  list<int|string>  $jawatanGredIds
     */
    private function seedLetakJawatans(int $count, array $ptjIds, array $jawatanGredIds): void
    {
        $existingStress = (int) DB::table('letak_jawatans')
            ->where('alasan', 'like', self::LETAK_ALASAN_MARKER.'%')
            ->count();

        if ($existingStress > 0) {
            $this->command?->warn("Found {$existingStress} existing stress letak_jawatans rows. Removing them first...");
            DB::table('letak_jawatans')
                ->where('alasan', 'like', self::LETAK_ALASAN_MARKER.'%')
                ->delete();
        }

        $now = now();
        $chunkSize = 1000;
        $ptjCount = count($ptjIds);
        $jgCount = count($jawatanGredIds);
        $jenisNotis = ['24 Jam', '30 Hari', '60 Hari'];
        $lantikan = ['Tetap', 'Kontrak', 'Kontrak Interim'];

        for ($offset = 0; $offset < $count; $offset += $chunkSize) {
            $size = min($chunkSize, $count - $offset);
            $rows = [];

            for ($i = 0; $i < $size; $i++) {
                $n = $offset + $i + 1;
                // Spread across 2025 so month/year export filters still hit a large set.
                $month = (($n - 1) % 12) + 1;
                $day = (($n - 1) % 27) + 1;

                $rows[] = [
                    'ptj_id' => $ptjIds[$n % $ptjCount],
                    'jawatan_gred_id' => $jawatanGredIds[$n % $jgCount],
                    'nama' => 'STRESS LETAK '.$n,
                    'nokp' => sprintf('98%010d', $n),
                    'tarikh_notis' => sprintf('2025-%02d-%02d', $month, $day),
                    'tarikh_kuatkuasa' => sprintf('2025-%02d-%02d', $month, min($day + 1, 28)),
                    'jenis_notis' => $jenisNotis[$n % count($jenisNotis)],
                    'alasan' => self::LETAK_ALASAN_MARKER.' letak jawatan #'.$n,
                    'tarikh_lantik' => '2020-02-01',
                    'lantikan' => $lantikan[$n % count($lantikan)],
                    'ikatan_jpa' => 0,
                    'ikatan_bpl' => 0,
                    'pinjaman_lppsa' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('letak_jawatans')->insert($rows);
            $this->command?->getOutput()?->write('.');
        }

        $this->command?->newLine();
    }
}
