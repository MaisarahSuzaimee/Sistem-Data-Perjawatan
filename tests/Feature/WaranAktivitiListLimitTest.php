<?php

use App\Filament\Resources\Warans\Pages\ListWarans;
use App\Filament\Resources\Warans\Tables\WaransTable;
use App\Models\Aktiviti;
use App\Models\Program;
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

    expect($waran->aktivitiListEntries()->pluck('label')->all())->toBe([
        '110100 - PENGURUSAN (1)',
        '220200 - PEMBANGUNAN (1)',
        '330300 - PERUBATAN (1)',
        '440400 - KEWANGAN (1)',
    ]);

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

it('sorts the aktiviti list by no_aktivit', function () {
    $program = Program::create(['nama_program' => 'PROGRAM AKTIVITI SORT']);
    $aktiviti = collect([
        ['no_aktivit' => '2.30.1', 'nama_aktiviti' => 'PERUBATAN FORENSIK'],
        ['no_aktivit' => '7.2.1.2', 'nama_aktiviti' => 'PENGURUSAN NEGERI'],
        ['no_aktivit' => '2.8.1', 'nama_aktiviti' => 'PAEDIATRIK'],
        ['no_aktivit' => '2.3.1', 'nama_aktiviti' => 'RAWATAN KECEMASAN'],
    ])->map(fn (array $data): Aktiviti => Aktiviti::create([
        'program_id' => $program->id,
        ...$data,
    ]));

    $waran = Waran::create([
        'no_waran' => 'WP AKTIVITI-SORT/2026',
        'jenis' => 'Tambah',
        'jik' => 4,
    ]);

    $aktiviti->each(fn (Aktiviti $item): WaranJawatan => WaranJawatan::create([
        'waran_id' => $waran->id,
        'aktiviti_id' => $item->id,
        'status' => 'active',
    ]));

    expect($waran->aktivitiListEntries()->pluck('label')->all())->toBe([
        '2.3.1 - RAWATAN KECEMASAN (1)',
        '2.8.1 - PAEDIATRIK (1)',
        '2.30.1 - PERUBATAN FORENSIK (1)',
        '7.2.1.2 - PENGURUSAN NEGERI (1)',
    ]);
});

it('shows three penempatan and a see more action when a waran has more', function () {
    $program = Program::create(['nama_program' => 'PROGRAM PENEMPATAN LIST']);
    $aktiviti = Aktiviti::create([
        'program_id' => $program->id,
        'no_aktivit' => '2.2.1',
        'nama_aktiviti' => 'PENGURUSAN HOSPITAL',
    ]);
    $ptjs = collect(['HOSPITAL YAN', 'HOSPITAL JITRA', 'HOSPITAL PENDANG', 'HOSPITAL KULIM'])
        ->map(fn (string $name): Ptj => Ptj::create(['nama_ptj' => $name]));

    $waran = Waran::create([
        'no_waran' => 'WP PENEMPATAN-LIST/2026',
        'jenis' => 'Tambah',
        'jik' => 4,
    ]);

    $ptjs->each(fn (Ptj $ptj): WaranJawatan => WaranJawatan::create([
        'waran_id' => $waran->id,
        'aktiviti_id' => $aktiviti->id,
        'ptj_id' => $ptj->id,
        'status' => 'active',
    ]));

    expect($waran->penempatanListItems())->toHaveCount(4);

    $cellHtml = WaransTable::penempatanCellHtml($waran);
    $modalHtml = WaransTable::penempatanModalContent($waran)->toHtml();

    expect($cellHtml)
        ->toContain('HOSPITAL JITRA (1)')
        ->toContain('HOSPITAL KULIM (1)')
        ->toContain('HOSPITAL PENDANG (1)')
        ->not->toContain('HOSPITAL YAN (1)')
        ->toContain(__('filament-tables::table.columns.text.more_list_items', ['count' => 1]));

    expect($modalHtml)
        ->toContain('HOSPITAL JITRA (1)')
        ->toContain('HOSPITAL YAN (1)');

    expect(strpos($modalHtml, 'HOSPITAL JITRA'))->toBeLessThan(strpos($modalHtml, 'HOSPITAL KULIM'))
        ->and(strpos($modalHtml, 'HOSPITAL KULIM'))->toBeLessThan(strpos($modalHtml, 'HOSPITAL PENDANG'))
        ->and(strpos($modalHtml, 'HOSPITAL PENDANG'))->toBeLessThan(strpos($modalHtml, 'HOSPITAL YAN'));

    Livewire::test(ListWarans::class)
        ->assertCanSeeTableRecords([$waran])
        ->assertSee('HOSPITAL JITRA')
        ->assertSee('HOSPITAL KULIM')
        ->assertSee('HOSPITAL PENDANG')
        ->assertSee(__('filament-tables::table.columns.text.more_list_items', ['count' => 1]))
        ->assertTableActionExists('lihatSemuaPenempatan');
});
