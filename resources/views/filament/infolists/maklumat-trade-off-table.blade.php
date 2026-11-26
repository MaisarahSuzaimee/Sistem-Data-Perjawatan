@php
    $sourceWaran = $record->menggantikanWaran?->no_waran;
    $tradeOffs = $record->tradeOffJawatans;
    $hasTradeOff = $tradeOffs->isNotEmpty();
@endphp

@if($hasTradeOff)
    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr class="bg-white dark:bg-transparent">
                    <th class="w-1/3 border border-gray-200 px-3 py-2.5 text-left font-medium text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <span class="inline-flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4 text-fg-indigo" />
                            No Waran
                        </span>
                    </th>
                    <td class="border border-gray-200 px-3 py-2.5 text-gray-950 dark:border-white/10 dark:text-white">
                        {{ $sourceWaran ?: '-' }}
                    </td>
                </tr>
                @foreach($tradeOffs as $index => $tradeOff)
                    <tr class="{{ $index % 2 === 0 ? 'bg-gray-50/70 dark:bg-white/[0.02]' : 'bg-white dark:bg-transparent' }}">
                        <th class="border border-gray-200 px-3 py-2.5 text-left font-medium text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <span class="inline-flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-4 h-4 text-fg-brand" />
                                {{ $tradeOffs->count() > 1 ? 'Penempatan Trade Off '.($index + 1) : 'Penempatan Trade Off' }}
                            </span>
                        </th>
                        <td class="border border-gray-200 px-3 py-2.5 text-gray-950 dark:border-white/10 dark:text-white">
                            {{ $tradeOff->tradeOffLabel() }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-white/15 dark:bg-white/5">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-200 dark:bg-white/10 dark:ring-white/10">
            <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-6 w-6 text-gray-400" />
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-950 dark:text-white">Tiada Maklumat Trade Off Bagi Waran Ini.</p>
        </div>
    </div>
@endif
