<?php

use App\Filament\Resources\Warans\Pages\EditWaran;
use App\Filament\Resources\Warans\RelationManagers\WaranJawatansRelationManager;
use App\Models\Aktiviti;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Program;
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
    Schema::dropIfExists('tbks');
    Schema::dropIfExists('subunits');
    Schema::dropIfExists('units');
    Schema::dropIfExists('bahagians');
    Schema::dropIfExists('pegawais');
    Schema::dropIfExists('jawatan__greds');
    Schema::dropIfExists('aktivitis');
    Schema::dropIfExists('programs');
    Schema::dropIfExists('jawatans');
    Schema::dropIfExists('greds');
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

    Schema::create('jawatans', function (Blueprint $table): void {
        $table->id();
        $table->string('desc_jawatan');
        $table->timestamps();
    });

    Schema::create('greds', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_gred');
        $table->timestamps();
    });

    Schema::create('jawatan__greds', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('jawatan_id')->nullable();
        $table->unsignedBigInteger('gred_id')->nullable();
        $table->timestamps();
    });

    Schema::create('bahagians', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('nama_bahagian');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('units', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->string('nama_unit');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('subunits', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->string('nama_subunit');
        $table->timestamps();
    });

    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->string('nama')->nullable();
        $table->string('nokp')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('jawatan_gred_id')->nullable();
        $table->boolean('is_kup')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('tbks', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('waran_jawatan_id')->nullable();
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
        $table->unsignedBigInteger('aktiviti_id')->nullable();
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->unsignedBigInteger('bahagian_id')->nullable();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->unsignedBigInteger('subunit_id')->nullable();
        $table->unsignedBigInteger('pegawai_id')->nullable();
        $table->json('jawatan_ids')->nullable();
        $table->json('gred_ids')->nullable();
        $table->boolean('is_kup')->default(false);
        $table->string('butiran')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    $this->actingAs(User::create([
        'name' => 'ADMIN PEGAWAI COL',
        'email' => 'admin-pegawai-col@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '880101015501',
        'status' => true,
        'role' => 1,
    ]));
});

it('shows pegawai name or tiada penyandang in the jawatan gred column', function () {
    $program = Program::create(['nama_program' => 'PROGRAM PEGAWAI COL']);
    $aktiviti = Aktiviti::create([
        'program_id' => $program->id,
        'no_aktivit' => '770100',
        'nama_aktiviti' => 'PENYANDANG',
    ]);
    $jawatan = Jawatan::create(['desc_jawatan' => 'PEGAWAI PERUBATAN']);
    $gred = Gred::create(['kod_gred' => 'UD44']);
    $pegawai = Pegawai::create([
        'nama' => 'AHMAD BIN ALI',
        'nokp' => '900101015501',
    ]);

    $waran = Waran::create([
        'no_waran' => 'WP PEGAWAI-COL/2026',
        'jenis' => 'Tambah',
        'jik' => 2,
    ]);

    $filled = WaranJawatan::create([
        'waran_id' => $waran->id,
        'aktiviti_id' => $aktiviti->id,
        'pegawai_id' => $pegawai->id,
        'butiran' => 'BUTIRAN-ISI',
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
        'status' => 'active',
    ]);
    $empty = WaranJawatan::create([
        'waran_id' => $waran->id,
        'aktiviti_id' => $aktiviti->id,
        'butiran' => 'BUTIRAN-KOSONG',
        'jawatan_ids' => [$jawatan->id],
        'gred_ids' => [$gred->id],
        'status' => 'active',
    ]);

    Livewire::test(WaranJawatansRelationManager::class, [
        'ownerRecord' => $waran,
        'pageClass' => EditWaran::class,
    ])
        ->assertCanSeeTableRecords([$filled, $empty])
        ->assertSee('Jawatan / Gred / Pegawai')
        ->assertSee('AHMAD BIN ALI')
        ->assertSee('Tiada penyandang')
        ->assertSeeHtml('fi-color-success')
        ->assertSeeHtml('fi-color-danger');
});

it('paginates the waran jawatan relation manager table to five records', function () {
    $program = Program::create(['nama_program' => 'PROGRAM PAGE SIZE']);
    $aktiviti = Aktiviti::create([
        'program_id' => $program->id,
        'no_aktivit' => '880100',
        'nama_aktiviti' => 'MUKA SURAT',
    ]);

    $waran = Waran::create([
        'no_waran' => 'WP PAGE-SIZE/2026',
        'jenis' => 'Tambah',
        'jik' => 6,
    ]);

    $records = collect(range(1, 6))->map(fn (int $n): WaranJawatan => WaranJawatan::create([
        'waran_id' => $waran->id,
        'aktiviti_id' => $aktiviti->id,
        'butiran' => 'BUTIRAN-'.$n,
        'status' => 'active',
    ]));

    Livewire::test(WaranJawatansRelationManager::class, [
        'ownerRecord' => $waran,
        'pageClass' => EditWaran::class,
    ])
        ->assertCanSeeTableRecords($records->take(5)->all())
        ->assertCanNotSeeTableRecords([$records->last()]);
});
