@php
    $pegawai = $record->pegawai;
    $hasPegawai = filled($record->pegawai_id) && $pegawai;
    $jawatanGred = null;

    if ($hasPegawai) {
        $jawatan = $pegawai->jawatan_gred?->jawatan?->desc_jawatan;
        $gred = $pegawai->jawatan_gred?->gred?->kod_gred;
        $tbk = $record->tbk?->tbk;
        $jawatanGred = collect([$jawatan, $gred])->filter()->implode(', ');
        if ($tbk) {
            $jawatanGred .= " (TBK{$tbk})";
        }
    }

    $showPtjPegawai = $hasPegawai && (int) $pegawai->ptj_id !== (int) $record->ptj_id;
@endphp

<table class="w-full border-collapse text-sm">
    <tbody>
        <tr>
            <th class="w-1/3 border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-user" class="w-4 h-4 text-fg-brand" />
                    Nama Pegawai
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2 font-bold">
                {{ $hasPegawai ? $pegawai->nama : 'Tiada Penyandang' }}
            </td>
        </tr>
        @if($hasPegawai)
            <tr>
                <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-identification" class="w-4 h-4 text-fg-indigo" />
                        No kad pengenalan
                    </span>
                </th>
                <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                    {{ $pegawai->nokp }}
                </td>
            </tr>
            <tr>
                <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-briefcase" class="w-4 h-4 text-fg-purple" />
                        Jawatan / Gred
                    </span>
                </th>
                <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                    {{ $jawatanGred ?: '-' }}
                </td>
            </tr>
            @if($showPtjPegawai)
                <tr>
                    <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-building-office-2" class="w-4 h-4 text-fg-cyan" />
                            PTJ Pegawai
                        </span>
                    </th>
                    <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                        {{ $pegawai->ptj?->nama_ptj ?: 'Tiada' }}
                    </td>
                </tr>
            @endif
        @endif
    </tbody>
</table>
