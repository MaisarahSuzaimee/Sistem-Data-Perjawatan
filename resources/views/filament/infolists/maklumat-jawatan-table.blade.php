@php
    $statusLabel = match ($record->status) {
        'removed' => 'Dibuang',
        'pindaan nama' => 'Pindaan Nama',
        'batal nama' => 'Batal Nama',
        default => 'Aktif',
    };

    $statusStyle = match ($record->status) {
        'removed' => 'background-color:#fee2e2;color:#b91c1c;',
        'pindaan nama' => 'background-color:#dbeafe;color:#1d4ed8;',
        'batal nama' => 'background-color:#fef3c7;color:#b45309;',
        default => 'background-color:#dcfce7;color:#15803d;',
    };

    $aktiviti = $record->aktiviti
        ? trim(($record->aktiviti->no_aktivit ?? '').' - '.($record->aktiviti->nama_aktiviti ?? ''), ' -')
        : '-';

    $jawatanGred = collect([
        filled($record->jawatan_list) ? $record->jawatan_list : null,
        filled($record->gred_list) ? 'GRED '.$record->gred_list : null,
    ])->filter()->implode(' , ') ?: '-';

    $showBahagian = (bool) $record->ptj?->usesBahagianHierarchy();
@endphp

<table class="w-full border-collapse text-sm">
    <tbody>
        <tr>
            <th class="w-1/3 border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-fg-indigo" />
                    No Waran
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->waran?->no_waran ?: '-' }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-4 h-4 text-fg-pink" />
                    Butiran
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->butiran ?: '-' }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="w-4 h-4 text-fg-brand" />
                    Tarikh Kuatkuasa Waran
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->tarikh_kuatkuasa ? \Illuminate\Support\Carbon::parse($record->tarikh_kuatkuasa)->translatedFormat('d F Y') : 'Tiada' }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-bolt" class="w-4 h-4 text-fg-warning-subtle" />
                    Aktiviti
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $aktiviti }}
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
                {{ $jawatanGred }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="w-4 h-4 text-fg-cyan" />
                    PTJ
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->ptj?->nama_ptj ?: '-' }}
            </td>
        </tr>
        @if($showBahagian)
            <tr>
                <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-building-office" class="w-4 h-4 text-fg-cyan" />
                        Bahagian
                    </span>
                </th>
                <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                    {{ $record->bahagian?->nama_bahagian ?: 'Tiada' }}
                </td>
            </tr>
        @endif
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="w-4 h-4 text-fg-yellow" />
                    Unit
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->unit?->nama_unit ?: 'Tiada' }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-square-2-stack" class="w-4 h-4 text-fg-brand" />
                    Subunit
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->subunit?->nama_subunit ?: 'Tiada' }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-check-badge" class="w-4 h-4 text-fg-warning" />
                    Status
                </span>
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                <span class="fi-badge inline-flex items-center rounded-md px-2.5 py-1 text-sm font-medium" style="{{ $statusStyle }}">
                    {{ $statusLabel }}
                </span>
            </td>
        </tr>
    </tbody>
</table>
