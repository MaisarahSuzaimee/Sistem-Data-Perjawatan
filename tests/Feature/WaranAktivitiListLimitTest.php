<?php

use App\Filament\Resources\Warans\Pages\ListWarans;
use App\Filament\Resources\Warans\Tables\WaransTable;
use App\Models\Aktiviti;
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
        'name' => 'ADMIN AKTIVITI LIST',
        'email' => 'admin-aktiviti-list@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '990101015501',
        'status' => true,
        'role' => 1,
    ]));
});

it('shows three aktiviti and a see more action when a waran has more', function () {
    $program = Program::create(['nama_program' => 'PROGRAM AKTIVITI LIST']);
    $aktiviti = collect([
        ['no_aktivit' => '110100', 'nama_aktiviti' => 'PENGURUSAN'],
        ['no_aktivit' => '220200', 'nama_aktiviti' => 'PEMBANGUNAN'],
        ['no_aktivit' => '330300', 'nama_aktiviti' => 'PERUBATAN'],
        ['no_aktivit' => '440400', 'nama_aktiviti' => 'KEWANGAN'],
    ])->map(fn (array $data): Aktiviti => Aktiviti::create([
        'program_id' => $program->id,
        ...$data,
    ]));

    $waran = Waran::create([
        'no_waran' => 'WP AKTIVITI-LIST/2026',
        'jenis' => 'Tambah',
        'jik' => 4,
    ]);

    $aktiviti->each(fn (Aktiviti $item): WaranJawatan => WaranJawatan::create([
        'waran_id' => $waran->id,
        'aktiviti_id' => $item->id,
        'status' => 'active',
    ]));

    expect($waran->aktivitiListItems())->toHaveCount(4);

    $modalHtml = WaransTable::aktivitiModalContent($waran)->toHtml();

    expect($modalHtml)
        ->toContain('110100 - PENGURUSAN')
        ->toContain('440400 - KEWANGAN');

    Livewire::test(ListWarans::class)
        ->assertCanSeeTableRecords([$waran])
        ->assertSee('110100 - PENGURUSAN')
        ->assertSee('220200 - PEMBANGUNAN')
        ->assertSee('330300 - PERUBATAN')
        ->assertDontSee('440400 - KEWANGAN')
        ->assertSee(__('filament-tables::table.columns.text.more_list_items', ['count' => 1]))
        ->assertTableActionExists('lihatSemuaAktiviti')
        ->assertSeeHtml('lihatSemuaAktiviti');
});
