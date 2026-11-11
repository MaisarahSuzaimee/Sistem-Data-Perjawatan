<x-filament-panels::page>
    <div class="dashboard-page">

    {{-- Hebahan Terkini --}}
    <div class="dashboard-block">
        <x-filament::section heading="Hebahan Terkini">
            @forelse($recentHebahans as $hebahan)
                <div class="dashboard-hebahan-item">
                    <div class="dashboard-hebahan-copy">
                        <p style="margin:0; font-weight:600; font-size:13px;">{{ $hebahan->tajuk }}</p>
                        @if ($hebahan->kandungan)
                            <p style="margin:2px 0 0; font-size:12px; color:#6b7280;">
                                {{ \Illuminate\Support\Str::limit(strip_tags($hebahan->kandungan), 100) }}</p>
                        @endif
                        @if ($hebahan->lampiran)
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($hebahan->lampiran) }}"
                                target="_blank" style="font-size:12px; color:#0ea5e9; text-decoration:underline;">
                                Lihat Lampiran
                            </a>
                        @endif
                    </div>
                    <span class="dashboard-hebahan-date">{{ $hebahan->tarikh_hebahan->translatedFormat('d M Y') }}</span>
                </div>
            @empty
                <p style="padding:8px 0; text-align:center; color:#6b7280; font-size:13px;">Tiada hebahan buat masa ini.
                </p>
            @endforelse
        </x-filament::section>
    </div>

    {{-- Stats Cards --}}
    <div class="dashboard-stats">
        <div class="sneat-stat-card sneat-stat-card--blue">
            <div class="sneat-stat-inner">
                <div>
                    <p class="sneat-stat-label">Jumlah Waran</p>
                    <p class="sneat-stat-value">{{ $totalWaran }}</p>
                </div>
                <div class="sneat-stat-icon sneat-stat-icon--blue">
                    <x-filament::icon icon="heroicon-o-document-text" />
                </div>
            </div>
        </div>
        <div class="sneat-stat-card sneat-stat-card--green">
            <div class="sneat-stat-inner">
                <div>
                    <p class="sneat-stat-label">Pengisian Semasa</p>
                    <p class="sneat-stat-value">{{ $totalPengisianSemasa }}</p>
                </div>
                <div class="sneat-stat-icon sneat-stat-icon--success">
                    <x-filament::icon icon="heroicon-o-users" />
                </div>
            </div>
        </div>
        <div class="sneat-stat-card sneat-stat-card--red">
            <div class="sneat-stat-inner">
                <div>
                    <p class="sneat-stat-label">Kekosongan</p>
                    <p class="sneat-stat-value">{{ $totalKekosongan }}</p>
                </div>
                <div class="sneat-stat-icon sneat-stat-icon--danger">
                    <x-filament::icon icon="heroicon-o-user-minus" />
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="dashboard-charts">

        {{-- Donut Chart --}}
        <x-filament::section heading="Status Waran">
            @php
                $statusTotal = $totalPengisianSemasa + $totalKekosongan;
                $pct = fn ($n) => $statusTotal > 0 ? round(($n / $statusTotal) * 100) : 0;
            @endphp
            <div class="dashboard-donut">
                <div class="dashboard-donut-canvas">
                    <canvas
                        id="statusChart"
                        data-pengisian="{{ $totalPengisianSemasa }}"
                        data-kekosongan="{{ $totalKekosongan }}"
                        data-total-waran="{{ $totalWaran }}"
                    ></canvas>
                </div>
                <div class="dashboard-legend">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="width:12px; height:12px; border-radius:50%; background:#10b981;"></div>
                        <span style="font-size:12px;">Pengisian Semasa ({{ $pct($totalPengisianSemasa) }}%)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="width:12px; height:12px; border-radius:50%; background:#f43f5e;"></div>
                        <span style="font-size:12px;">Kekosongan ({{ $pct($totalKekosongan) }}%)</span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Bar Chart --}}
        <x-filament::section heading="Jumlah Pengisian Waran Perjawatan Mengikut Program">
            <div class="dashboard-bar-chart">
                <canvas
                    id="programChart"
                    data-labels='@json($waranByProgram->pluck('desc_program'))'
                    data-values='@json($waranByProgram->pluck('waran_count'))'
                ></canvas>
            </div>
        </x-filament::section>

    </div>

    {{-- Recent Waran --}}
    <div class="dashboard-block">

        <x-filament::section heading="Waran Terbaharu">
            <div class="dashboard-table-scroll">
                <table>
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="text-align:left; padding:10px 12px; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.03em; border:1px solid #e5e7eb;">
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <x-filament::icon icon="heroicon-o-document-text" class="w-3.5 h-3.5 text-fg-indigo" />
                                    No. Waran
                                </span>
                            </th>
                            <th style="text-align:left; padding:10px 12px; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.03em; border:1px solid #e5e7eb;">Jenis</th>
                            <th style="text-align:center; padding:10px 12px; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.03em; border:1px solid #e5e7eb;">J</th>
                            <th style="text-align:center; padding:10px 12px; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.03em; border:1px solid #e5e7eb;">I</th>
                            <th style="text-align:center; padding:10px 12px; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.03em; border:1px solid #e5e7eb;">K</th>
                            <th style="text-align:left; padding:10px 12px; color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.03em; border:1px solid #e5e7eb;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentWarans as $waran)
                            @php $status = $waran->status_jik; @endphp
                            <tr style="transition:background-color .15s ease;"
                                onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding:10px 12px; border:1px solid #e5e7eb;">
                                    <a href="{{ \App\Filament\Resources\Warans\WaranResource::getUrl('view', ['record' => $waran]) }}"
                                        style="color:#4f46e5; font-weight:600; text-decoration:none;">
                                        {{ $waran->no_waran }}
                                    </a>
                                </td>
                                <td style="padding:10px 12px; border:1px solid #e5e7eb;">
                                    @if ($waran->jenis === 'Tambah')
                                        <x-filament::badge color="success" icon="heroicon-o-plus">Tambah</x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning" icon="heroicon-o-minus">Tolak</x-filament::badge>
                                    @endif
                                </td>
                                <td style="padding:10px 12px; text-align:center; border:1px solid #e5e7eb;">
                                    <span style="display:inline-flex; min-width:24px; justify-content:center; padding:2px 8px; border-radius:999px; font-weight:600; font-size:12px; background:#f3f4f6; color:#374151;">{{ $waran->jik }}</span>
                                </td>
                                <td style="padding:10px 12px; text-align:center; border:1px solid #e5e7eb;">
                                    <span style="display:inline-flex; min-width:24px; justify-content:center; padding:2px 8px; border-radius:999px; font-weight:600; font-size:12px; background:#dcfce7; color:#15803d;">{{ $waran->isi_count }}</span>
                                </td>
                                <td style="padding:10px 12px; text-align:center; border:1px solid #e5e7eb;">
                                    @if ($waran->kosong_count < 0)
                                        <span style="display:inline-flex; min-width:24px; justify-content:center; padding:2px 8px; border-radius:999px; font-weight:600; font-size:12px; background:#fee2e2; color:#b91c1c;">{{ $waran->kosong_count }}</span>
                                    @else
                                        <span style="display:inline-flex; min-width:24px; justify-content:center; padding:2px 8px; border-radius:999px; font-weight:600; font-size:12px; background:#fef3c7; color:#b45309;">{{ $waran->kosong_count }}</span>
                                    @endif
                                </td>
                                <td style="padding:10px 12px; border:1px solid #e5e7eb;">
                                    @if ($status === 'Lebih')
                                        <x-filament::badge color="success">{{ $status }}</x-filament::badge>
                                    @elseif($status === 'Kurang')
                                        <x-filament::badge color="danger">{{ $status }}</x-filament::badge>
                                    @else
                                        <x-filament::badge color="info">{{ $status }}</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:16px 0; text-align:center; color:#6b7280; border:1px solid #e5e7eb;">Tiada waran
                                    ditemui.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    @vite('resources/js/dashboard-charts.js')

    </div>
</x-filament-panels::page>
