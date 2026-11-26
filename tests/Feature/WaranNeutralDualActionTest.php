<?php

use App\Models\Pegawai;
use App\Models\User;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('warans');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('nokp')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->integer('role')->default(1);
        $table->boolean('status')->default(true);
        $table->timestamps();
    });

    Schema::create('warans', function (Blueprint $table): void {
        $table->id();
        $table->string('no_waran')->nullable();
        $table->string('jenis')->nullable();
        $table->integer('jik')->nullable();
        $table->text('catatan')->nullable();
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('waran_tolak_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('status')->nullable();
        $table->string('butiran')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    $this->actingAs(User::query()->create([
        'name' => 'ADMIN NEUTRAL',
        'email' => 'admin-neutral@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '880101015501',
        'role' => 1,
        'status' => true,
    ]));
});

it('counts isi from filled adds only on a jenis-null waran', function () {
    $waran = Waran::query()->create([
        'no_waran' => 'WP NEUTRAL/1',
        'jik' => 2,
        'jenis' => null,
    ]);

    $pegawai = Pegawai::query()->create([
        'nama' => 'PEGAWAI ISI',
        'nokp' => '111111111111',
    ]);

    WaranJawatan::query()->create([
        'waran_id' => $waran->id,
        'pegawai_id' => $pegawai->id,
        'status' => 'active',
        'butiran' => 'A1',
    ]);

    WaranJawatan::query()->create([
        'waran_id' => $waran->id,
        'pegawai_id' => null,
        'status' => 'active',
        'butiran' => 'A2',
    ]);

    expect($waran->fresh()->isi_count)->toBe(1)
        ->and($waran->fresh()->status_jik)->toBe('Kurang');
});

it('can buang a jawatan on the same neutral waran without changing isi_count', function () {
    $waran = Waran::query()->create([
        'no_waran' => 'WP NEUTRAL/2',
        'jik' => 1,
        'jenis' => null,
    ]);

    $pegawai = Pegawai::query()->create([
        'nama' => 'PEGAWAI ISI',
        'nokp' => '222222222222',
    ]);

    WaranJawatan::query()->create([
        'waran_id' => $waran->id,
        'pegawai_id' => $pegawai->id,
        'status' => 'active',
        'butiran' => 'OWN',
    ]);

    $other = WaranJawatan::query()->create([
        'waran_id' => Waran::query()->create([
            'no_waran' => 'WP OTHER/1',
            'jik' => 1,
            'jenis' => null,
        ])->id,
        'pegawai_id' => null,
        'status' => 'active',
        'butiran' => 'TO REMOVE',
    ]);

    $other->update([
        'waran_tolak_id' => $waran->id,
        'status' => 'removed',
    ]);
    $other->delete();

    $trashed = WaranJawatan::withTrashed()->find($other->id);

    expect(WaranJawatan::query()->find($other->id))->toBeNull()
        ->and($trashed?->waran_tolak_id)->toBe($waran->id)
        ->and($trashed?->status)->toBe('removed')
        ->and($trashed?->trashed())->toBeTrue()
        ->and($waran->fresh()->isi_count)->toBe(1)
        ->and($waran->fresh()->status_jik)->toBe('Seimbang');
});

it('still allows tambah rows on a legacy tolak waran', function () {
    $waran = Waran::query()->create([
        'no_waran' => 'WP LEGACY TOLAK/1',
        'jik' => 1,
        'jenis' => 'Tolak',
    ]);

    WaranJawatan::query()->create([
        'waran_id' => $waran->id,
        'pegawai_id' => null,
        'status' => 'active',
        'butiran' => 'NEW ADD',
    ]);

    expect(WaranJawatan::query()->where('waran_id', $waran->id)->count())->toBe(1)
        ->and($waran->fresh()->isi_count)->toBe(0);
});
