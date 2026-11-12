<?php

use App\Models\Pegawai;
use App\Models\User;
use App\Services\NavigationBadgeCache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Cache::flush();
    NavigationBadgeCache::flush();

    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('ptjs');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('nokp')->nullable();
        $table->string('phone_number')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->boolean('must_change_password')->default(false);
        $table->tinyInteger('role')->default(1);
        $table->tinyInteger('status')->default(1);
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj')->nullable();
        $table->boolean('is_jkn')->default(false);
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->unsignedBigInteger('subunit_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->string('jantina')->nullable();
        $table->boolean('is_tetap')->default(false);
        $table->boolean('is_kontrak')->default(false);
        $table->boolean('is_jtw')->default(false);
        $table->boolean('ada_unit')->default(0);
        $table->boolean('ada_subunit')->default(0);
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('warans', function (Blueprint $table): void {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->string('jenis')->nullable();
        $table->integer('jik')->default(0);
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('status')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

it('caches the tidak lengkap badge count across repeated reads', function () {
    $admin = User::query()->create([
        'name' => 'ADMIN',
        'email' => 'admin-badge@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'status' => 1,
    ]);

    $this->actingAs($admin);

    Pegawai::query()->create([
        'nama' => 'TIADA PTJ',
        'nokp' => '111111111111',
        'jantina' => 'L',
        'ptj_id' => null,
        'ada_unit' => 1,
        'ada_subunit' => 1,
        'is_kontrak' => 1,
    ]);

    $first = NavigationBadgeCache::pegawaiTidakLengkapCount();
    $second = NavigationBadgeCache::pegawaiTidakLengkapCount();

    expect($first)->toBe(1)
        ->and($second)->toBe(1);
});

it('flushes the cached badge when a pegawai is saved', function () {
    $admin = User::query()->create([
        'name' => 'ADMIN',
        'email' => 'admin-flush@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'status' => 1,
    ]);

    $this->actingAs($admin);

    expect(NavigationBadgeCache::pegawaiTidakLengkapCount())->toBe(0);

    Pegawai::query()->create([
        'nama' => 'BARU TIDAK LENGKAP',
        'nokp' => '222222222222',
        'jantina' => 'P',
        'ptj_id' => null,
        'ada_unit' => 1,
        'ada_subunit' => 1,
        'is_kontrak' => 1,
    ]);

    expect(NavigationBadgeCache::pegawaiTidakLengkapCount())->toBe(1);
});

it('treats missing waran via not-exists as tidak lengkap', function () {
    $admin = User::query()->create([
        'name' => 'ADMIN',
        'email' => 'admin-waran@test.com',
        'password' => bcrypt('password'),
        'role' => 1,
        'status' => 1,
    ]);

    $this->actingAs($admin);

    $ptjId = Schema::getConnection()->table('ptjs')->insertGetId([
        'nama_ptj' => 'HOSPITAL',
        'is_jkn' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Pegawai::query()->create([
        'nama' => 'TIADA WARAN',
        'nokp' => '333333333333',
        'jantina' => 'L',
        'ptj_id' => $ptjId,
        'ada_unit' => 1,
        'ada_subunit' => 1,
        'is_kontrak' => 0,
        'is_jtw' => 0,
    ]);

    expect(NavigationBadgeCache::pegawaiTidakLengkapCount())->toBe(1);
});
