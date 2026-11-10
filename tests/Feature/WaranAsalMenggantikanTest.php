<?php

use App\Models\User;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('waran_jawatans');
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

    Schema::create('waran_jawatans', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_id')->nullable();
        $table->unsignedBigInteger('waran_tolak_id')->nullable();
        $table->unsignedBigInteger('menggantikan_waran_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('status')->nullable();
        $table->string('butiran')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    $this->actingAs(User::query()->create([
        'name' => 'ADMIN TRACE',
        'email' => 'admin-trace@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '880101015502',
        'role' => 1,
        'status' => true,
    ]));
});

it('persists menggantikan_waran_id when a new penempatan jawatan is created', function () {
    $waranAsal = Waran::query()->create([
        'no_waran' => 'WP ASAL/1',
        'jik' => 1,
        'jenis' => null,
    ]);

    $waranBaharu = Waran::query()->create([
        'no_waran' => 'WP BAHARU/1',
        'jik' => 1,
        'jenis' => null,
    ]);

    $jawatan = WaranJawatan::query()->create([
        'waran_id' => $waranBaharu->id,
        'menggantikan_waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'GANTI-1',
    ]);

    $jawatan->refresh();

    expect($jawatan->menggantikan_waran_id)->toBe($waranAsal->id)
        ->and($jawatan->menggantikanWaran?->no_waran)->toBe('WP ASAL/1')
        ->and($waranAsal->digantiOlehJawatan()->pluck('id')->all())->toContain($jawatan->id);
});

it('keeps original waran_id when a jawatan is dibuang', function () {
    $waranAsal = Waran::query()->create([
        'no_waran' => 'WP ASAL/2',
        'jik' => 1,
        'jenis' => null,
    ]);

    $waranTolak = Waran::query()->create([
        'no_waran' => 'WP TOLAK/1',
        'jik' => 1,
        'jenis' => null,
    ]);

    $jawatan = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'ASAL-1',
    ]);

    $jawatan->update([
        'waran_tolak_id' => $waranTolak->id,
        'status' => 'removed',
    ]);
    $jawatan->delete();

    $trashed = WaranJawatan::withTrashed()->find($jawatan->id);

    expect($trashed)->not->toBeNull()
        ->and($trashed->waran_id)->toBe($waranAsal->id)
        ->and($trashed->waran_tolak_id)->toBe($waranTolak->id)
        ->and($trashed->waran?->no_waran)->toBe('WP ASAL/2')
        ->and($trashed->waranTolak?->no_waran)->toBe('WP TOLAK/1')
        ->and($trashed->status)->toBe('removed')
        ->and($trashed->trashed())->toBeTrue();
});
