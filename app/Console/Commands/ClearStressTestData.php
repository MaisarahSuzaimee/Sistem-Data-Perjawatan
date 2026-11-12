<?php

namespace App\Console\Commands;

use Database\Seeders\StressTestDataSeeder;
use Database\Seeders\StressWaranJawatanSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ClearStressTestData extends Command
{
    protected $signature = 'stress-data:clear';

    protected $description = 'Remove stress pegawai, letak_jawatan, and waran_jawatan rows';

    public function handle(): int
    {
        Config::set('mail.default', 'array');
        Mail::fake();
        Notification::fake();

        $stressWaranIds = DB::table('warans')
            ->where('no_waran', StressWaranJawatanSeeder::WARAN_NO)
            ->pluck('id')
            ->all();

        $waranJawatanDeleted = 0;
        $waranDeleted = 0;

        if ($stressWaranIds !== []) {
            $waranJawatanDeleted = DB::table('waran_jawatans')
                ->whereIn('waran_id', $stressWaranIds)
                ->delete();

            $waranDeleted = DB::table('warans')
                ->whereIn('id', $stressWaranIds)
                ->delete();
        }

        $waranJawatanDeleted += DB::table('waran_jawatans')
            ->where('catatan_jawatan', 'like', StressWaranJawatanSeeder::CATATAN_MARKER.'%')
            ->delete();

        $pegawaiDeleted = DB::table('pegawais')
            ->where('emel', StressTestDataSeeder::PEGAWAI_EMAIL_MARKER)
            ->delete();

        $letakDeleted = DB::table('letak_jawatans')
            ->where('alasan', 'like', StressTestDataSeeder::LETAK_ALASAN_MARKER.'%')
            ->delete();

        $this->info("Deleted {$waranJawatanDeleted} stress waran_jawatans rows.");
        $this->info("Deleted {$waranDeleted} stress waran rows.");
        $this->info("Deleted {$pegawaiDeleted} stress pegawai rows.");
        $this->info("Deleted {$letakDeleted} stress letak_jawatans rows.");
        $this->warn('No emails were sent.');

        return self::SUCCESS;
    }
}
