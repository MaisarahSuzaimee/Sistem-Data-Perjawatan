<?php

// use Illuminate\Foundation\Inspiring;
// use Illuminate\Support\Facades\Artisan;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

// Schedule::command('pegawai:delete-letak-jawatan')
//     ->everyFiveSeconds();

// Schedule::command('pegawai:delete-tamat-perkhidmatan')
//     ->everyFiveSeconds();

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => Artisan::call('pegawai:delete-letak-jawatan'))
    ->everyFiveSeconds();

Schedule::call(fn () => Artisan::call('pegawai:delete-tamat-perkhidmatan'))
    ->everyFiveSeconds();

Schedule::command('database:backup')
    ->dailyAt('18:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping()
    ->name('mystaff-daily-database-backup');
