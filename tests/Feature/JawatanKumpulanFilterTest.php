<?php

use App\Filament\Resources\Jawatans\Pages\ListJawatans;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Kumpulan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    foreach ([
        'jawatan__greds',
        'jawatans',
        'greds',
        'kumpulans',
        'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('email')->unique();
        $table->string('password');
        $table->string('nokp')->nullable();
        $table->boolean('status')->default(true);
        $table->integer('role')->default(1);
        $table->timestamps();
    });

    Schema::create('kumpulans', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_kumpulan');
        $table->timestamps();
    });

    Schema::create('greds', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_gred')->nullable();
        $table->string('desc_gred')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatans', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_jawatan')->nullable();
        $table->string('desc_jawatan')->nullable();
        $table->timestamps();
    });

    Schema::create('jawatan__greds', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('jawatan_id');
        $table->unsignedBigInteger('gred_id');
        $table->unsignedBigInteger('kumpulan_id')->nullable();
        $table->timestamps();
    });
});

function jawatanKumpulanAdmin(): User
{
    return User::query()->create([
        'name' => 'ADMIN JAWATAN',
        'email' => 'admin-jawatan@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '010101020365',
        'status' => true,
        'role' => 1,
    ]);
}

it('filters jawatan by kumpulan', function () {
    $admin = jawatanKumpulanAdmin();
    $this->actingAs($admin);

    $kumpulanPengurusan = Kumpulan::query()->create(['nama_kumpulan' => 'Pengurusan Tertinggi']);
    $kumpulanSokongan = Kumpulan::query()->create(['nama_kumpulan' => 'Sokongan']);
    $gred = Gred::query()->create(['kod_gred' => 'N41', 'desc_gred' => 'N41']);

    $pengarah = Jawatan::query()->create([
        'kod_jawatan' => 'VU6',
        'desc_jawatan' => 'PENGARAH KESIHATAN NEGERI',
    ]);
    $kerani = Jawatan::query()->create([
        'kod_jawatan' => 'N19',
        'desc_jawatan' => 'PEMBANTU TADBIR',
    ]);

    $pengarah->greds()->attach($gred->id, ['kumpulan_id' => $kumpulanPengurusan->id]);
    $kerani->greds()->attach($gred->id, ['kumpulan_id' => $kumpulanSokongan->id]);

    Livewire::test(ListJawatans::class)
        ->assertCanSeeTableRecords([$pengarah, $kerani])
        ->filterTable('kumpulan_id', $kumpulanPengurusan->id)
        ->assertCanSeeTableRecords([$pengarah])
        ->assertCanNotSeeTableRecords([$kerani]);
});
