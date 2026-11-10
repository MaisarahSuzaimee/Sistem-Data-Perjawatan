<?php

use App\Filament\Resources\Warans\Pages\ListWarans;
use App\Filament\Resources\Warans\Tables\WaransTable;
use App\Models\Ptj;
use App\Models\User;
use App\Models\Waran;
use App\Models\WaranJawatan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    Schema::dropIfExists('waran_jawatans');
    Schema::dropIfExists('warans');
    Schema::dropIfExists('aktiviti_ptj');
    Schema::dropIfExists('program_ptj');
    Schema::dropIfExists('aktivitis');
    Schema::dropIfExists('programs');
    Schema::dropIfExists('ptjs');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('email')->unique();
        $table->string('avatar')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->string('nokp')->unique();
        $table->string('phone_number')->nullable();
        $table->boolean('status')->default(true);
        $table->integer('role')->default(3);
        $table->timestamps();
    });

    Schema::create('ptjs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_ptj');
        $table->integer('kod_ptj')->default(0);
        $table->boolean('is_jkn')->default(false);
        $table->timestamps();
    });

    Schema::create('programs', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_program');
        $table->timestamps();
    });

    Schema::create('aktivitis', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('program_id');
        $table->string('no_aktivit')->nullable();
        $table->string('nama_aktiviti');
        $table->timestamps();
    });

    Schema::create('program_ptj', function (Blueprint $table): void {
        $table->unsignedBigInteger('program_id');
        $table->unsignedBigInteger('ptj_id');
    });

    Schema::create('aktiviti_ptj', function (Blueprint $table): void {
        $table->unsignedBigInteger('aktiviti_id');
        $table->unsignedBigInteger('ptj_id');
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
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    $this->actingAs(User::create([
        'name' => 'ADMIN PENEMPATAN LIST',
        'email' => 'admin-penempatan-list@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '990101015502',
        'status' => true,
        'role' => 1,
    ]));
});

it('shows three penempatan and a see more action when a waran has more', function () {
    $ptjs = collect([
        'PTJ PENGURUSAN',
        'PTJ PEMBANGUNAN',
        'PTJ PERUBATAN',
        'PTJ KEWANGAN',
    ])->map(fn (string $nama): Ptj => Ptj::create([
        'nama_ptj' => $nama,
        'kod_ptj' => random_int(1000, 9999),
    ]));

    $waran = Waran::create([
        'no_waran' => 'WP PENEMPATAN-LIST/2026',
        'jenis' => 'Tambah',
        'jik' => 4,
    ]);

    $ptjs->each(fn (Ptj $ptj): WaranJawatan => WaranJawatan::create([
        'waran_id' => $waran->id,
        'ptj_id' => $ptj->id,
        'status' => 'active',
    ]));

    expect($waran->penempatanListItems())->toHaveCount(4);

    $cellHtml = WaransTable::penempatanCellHtml($waran);
    $modalHtml = WaransTable::penempatanModalContent($waran)->toHtml();

    expect($cellHtml)
        ->toContain('PTJ PENGURUSAN')
        ->toContain('PTJ PEMBANGUNAN')
        ->toContain('PTJ PERUBATAN')
        ->not->toContain('PTJ KEWANGAN')
        ->toContain(__('filament-tables::table.columns.text.more_list_items', ['count' => 1]));

    expect($modalHtml)
        ->toContain('PTJ PENGURUSAN')
        ->toContain('PTJ KEWANGAN');

    Livewire::test(ListWarans::class)
        ->assertCanSeeTableRecords([$waran])
        ->assertSee(__('filament-tables::table.columns.text.more_list_items', ['count' => 1]))
        ->assertTableActionExists('lihatSemuaPenempatan')
        ->assertSeeHtml('lihatSemuaPenempatan');
});
