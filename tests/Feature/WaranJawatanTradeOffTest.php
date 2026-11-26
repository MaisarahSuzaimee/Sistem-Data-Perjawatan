<?php

use App\Models\User;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('waran_jawatan_trade_offs');
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

    Schema::create('waran_jawatan_trade_offs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_jawatan_id');
        $table->unsignedBigInteger('trade_off_waran_jawatan_id');
        $table->timestamps();
        $table->unique(['waran_jawatan_id', 'trade_off_waran_jawatan_id'], 'waran_jawatan_trade_offs_unique');
    });

    $this->actingAs(User::query()->create([
        'name' => 'ADMIN TRADE OFF',
        'email' => 'admin-trade-off@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '880101015503',
        'role' => 1,
        'status' => true,
    ]));
});

it('syncs many trade-off penempatan under the selected waran', function () {
    $waranAsal = Waran::query()->create([
        'no_waran' => 'WP ASAL/TRADE',
        'jik' => 2,
    ]);

    $waranBaharu = Waran::query()->create([
        'no_waran' => 'WP BAHARU/TRADE',
        'jik' => 1,
    ]);

    $slotA = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'A-1',
    ]);

    $slotB = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'A-2',
    ]);

    $newSlot = WaranJawatan::query()->create([
        'waran_id' => $waranBaharu->id,
        'menggantikan_waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'NEW-1',
    ]);

    $newSlot->syncValidTradeOffJawatans([$slotA->id, $slotB->id]);

    expect($newSlot->tradeOffJawatans()->pluck('waran_jawatans.id')->sort()->values()->all())
        ->toBe([$slotA->id, $slotB->id]);
});

it('excludes removed penempatan when syncing trade offs', function () {
    $waranAsal = Waran::query()->create([
        'no_waran' => 'WP ASAL/REMOVED',
        'jik' => 2,
    ]);

    $waranBaharu = Waran::query()->create([
        'no_waran' => 'WP BAHARU/REMOVED',
        'jik' => 1,
    ]);

    $active = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'ACTIVE',
    ]);

    $removed = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'removed',
        'butiran' => 'REMOVED',
    ]);

    $newSlot = WaranJawatan::query()->create([
        'waran_id' => $waranBaharu->id,
        'menggantikan_waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'NEW',
    ]);

    $newSlot->syncValidTradeOffJawatans([$active->id, $removed->id]);

    expect($newSlot->tradeOffJawatans()->pluck('waran_jawatans.id')->all())
        ->toBe([$active->id]);
});

it('clears trade-off penempatan when source waran is cleared', function () {
    $waranAsal = Waran::query()->create([
        'no_waran' => 'WP ASAL/CLEAR',
        'jik' => 1,
    ]);

    $waranBaharu = Waran::query()->create([
        'no_waran' => 'WP BAHARU/CLEAR',
        'jik' => 1,
    ]);

    $slot = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'SLOT',
    ]);

    $newSlot = WaranJawatan::query()->create([
        'waran_id' => $waranBaharu->id,
        'menggantikan_waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'NEW',
    ]);

    $newSlot->syncValidTradeOffJawatans([$slot->id]);

    expect($newSlot->tradeOffJawatans()->count())->toBe(1);

    $newSlot->update(['menggantikan_waran_id' => null]);
    $newSlot->syncValidTradeOffJawatans([$slot->id]);

    expect($newSlot->fresh()->tradeOffJawatans()->count())->toBe(0);
});

it('clears stale trade offs when source waran changes', function () {
    $waranAsal = Waran::query()->create([
        'no_waran' => 'WP ASAL/CHANGE',
        'jik' => 1,
    ]);

    $waranLain = Waran::query()->create([
        'no_waran' => 'WP LAIN/CHANGE',
        'jik' => 1,
    ]);

    $waranBaharu = Waran::query()->create([
        'no_waran' => 'WP BAHARU/CHANGE',
        'jik' => 1,
    ]);

    $slotAsal = WaranJawatan::query()->create([
        'waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'ASAL',
    ]);

    $slotLain = WaranJawatan::query()->create([
        'waran_id' => $waranLain->id,
        'status' => 'active',
        'butiran' => 'LAIN',
    ]);

    $newSlot = WaranJawatan::query()->create([
        'waran_id' => $waranBaharu->id,
        'menggantikan_waran_id' => $waranAsal->id,
        'status' => 'active',
        'butiran' => 'NEW',
    ]);

    $newSlot->syncValidTradeOffJawatans([$slotAsal->id]);

    $newSlot->update(['menggantikan_waran_id' => $waranLain->id]);
    $newSlot->syncValidTradeOffJawatans([$slotAsal->id, $slotLain->id]);

    expect($newSlot->tradeOffJawatans()->pluck('waran_jawatans.id')->all())
        ->toBe([$slotLain->id]);
});
