<?php

use App\Services\PrestasiService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'services.prestasi_v2.api_key' => 'test-api-key',
        'services.prestasi_v2.base_url' => 'https://prestasi.test/api/v1',
    ]);

    Schema::dropIfExists('pegawais');
    Schema::create('pegawais', function (Blueprint $table): void {
        $table->id();
        $table->string('nokp')->nullable();
        $table->string('nama')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

it('makes only one API request even when meta reports many pages', function () {
    $requestCount = 0;

    Http::fake(function (Request $request) use (&$requestCount) {
        $requestCount++;

        expect($request->url())->toContain('no_kp=880101011234')
            ->and($request['page'] ?? $request->data()['page'] ?? null)->toBeIn([1, '1', null]);

        return Http::response([
            'data' => [
                ['no_kp' => '110101011111', 'nama_pegawai' => 'ORANG LAIN'],
                ['no_kp' => '880101011234', 'nama_pegawai' => 'PEGAWAI DICARI'],
            ],
            'meta' => [
                'last_page' => 195,
                'current_page' => 1,
                'per_page' => 100,
            ],
        ]);
    });

    $result = PrestasiService::fetchByNokp('880101011234');

    expect($requestCount)->toBe(1)
        ->and($result['found'])->toBeTrue()
        ->and($result['source'])->toBe('api')
        ->and($result['data']['nama'])->toBe('PEGAWAI DICARI');

    Http::assertSentCount(1);
});

it('does not page further when the filtered response has no matching nokp', function () {
    Http::fake([
        'prestasi.test/api/v1/pegawai*' => Http::response([
            'data' => array_fill(0, 100, ['no_kp' => '110101011111', 'nama_pegawai' => 'ORANG LAIN']),
            'meta' => [
                'last_page' => 195,
                'current_page' => 1,
                'per_page' => 100,
            ],
        ]),
    ]);

    $result = PrestasiService::fetchByNokp('880101011234');

    expect($result['found'])->toBeFalse()
        ->and($result['source'])->toBeNull();

    Http::assertSentCount(1);
});

it('returns a single-object filtered payload without paging', function () {
    Http::fake([
        'prestasi.test/api/v1/pegawai*' => Http::response([
            'data' => [
                'no_kp' => '990101011234',
                'nama_pegawai' => 'PEGAWAI TUNGGAL',
            ],
        ]),
    ]);

    $result = PrestasiService::fetchByNokp('990101011234');

    expect($result['found'])->toBeTrue()
        ->and($result['data']['nama'])->toBe('PEGAWAI TUNGGAL');

    Http::assertSentCount(1);
});
