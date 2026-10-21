<?php

uses(TestCase::class);

use App\Filament\Resources\Programs\Tables\ProgramsTable;
use App\Models\Aktiviti;
use App\Models\Program;
use App\Models\Ptj;
use Tests\TestCase;

it('rowspans the program across each aktiviti and lists that aktiviti ptjs', function () {
    $jabatan = new Ptj(['nama_ptj' => 'Jabatan Kesihatan']);
    $pusatKesihatan = new Ptj(['nama_ptj' => 'Pusat Kesihatan']);
    $pusatPentadbiran = new Ptj(['nama_ptj' => 'Pusat Pentadbiran']);

    $pengurusan = new Aktiviti(['no_aktivit' => '1.1.1', 'nama_aktiviti' => 'Pengurusan']);
    $pengurusan->setRelation('ptjs', collect([$jabatan]));

    $pentadbiran = new Aktiviti(['no_aktivit' => '1.1.2', 'nama_aktiviti' => 'Pentadbiran']);
    $pentadbiran->setRelation('ptjs', collect([$pusatKesihatan, $pusatPentadbiran]));

    $program = new Program([
        'nama_program' => 'Program 1',
        'desc_program' => 'Kesihatan Awam',
    ]);
    $program->setRelation('aktiviti', collect([$pentadbiran, $pengurusan]));
    $program->setRelation('ptjs', collect());

    $html = ProgramsTable::matrixHtml($program);

    expect($html)
        ->toContain('grid-row: span 2')
        ->toContain('Program 1')
        ->toContain('Kesihatan Awam')
        ->toContain('1.1.1 - Pengurusan')
        ->toContain('1.1.2 - Pentadbiran')
        ->toContain('Jabatan Kesihatan')
        ->toContain('Pusat Kesihatan<br>Pusat Pentadbiran');
});

it('shows a dash and unassigned program ptjs when the program has no aktiviti', function () {
    $assigned = new Ptj(['nama_ptj' => 'Sudah Ada Aktiviti']);
    $assigned->setRelation('aktivitis', collect([new Aktiviti]));

    $unassigned = new Ptj(['nama_ptj' => 'Pusat Kesihatan']);
    $unassigned->setRelation('aktivitis', collect());

    $program = new Program(['nama_program' => 'Program 2']);
    $program->setRelation('aktiviti', collect());
    $program->setRelation('ptjs', collect([$assigned, $unassigned]));

    $html = ProgramsTable::matrixHtml($program);

    expect($html)
        ->toContain('grid-row: span 1')
        ->toContain('fi-program-matrix-aktiviti">-</div>')
        ->toContain('Pusat Kesihatan')
        ->not->toContain('Sudah Ada Aktiviti');
});

it('shows only three ptjs and a see more label when an aktiviti has more', function () {
    $names = ['PTJ A', 'PTJ B', 'PTJ C', 'PTJ D'];
    $ptjs = collect($names)->map(fn (string $name): Ptj => new Ptj(['nama_ptj' => $name]));

    $aktiviti = new Aktiviti(['no_aktivit' => '1.1.1', 'nama_aktiviti' => 'Pengurusan']);
    $aktiviti->setRelation('ptjs', $ptjs);

    $program = new Program(['nama_program' => 'Program 1']);
    $program->setRelation('aktiviti', collect([$aktiviti]));
    $program->setRelation('ptjs', collect());

    $html = ProgramsTable::matrixHtml($program);

    expect($html)
        ->toContain('PTJ A')
        ->toContain('PTJ B')
        ->toContain('PTJ C')
        ->not->toContain('PTJ D')
        ->toContain('fi-program-matrix-more');
});
