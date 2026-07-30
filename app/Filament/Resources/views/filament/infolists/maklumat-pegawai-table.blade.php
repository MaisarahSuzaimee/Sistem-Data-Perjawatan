<table class="w-full border-collapse text-sm">
    <tbody>
        <tr>
            <th class="w-1/3 border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                Nama
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2 font-bold">
                {{ $record->nama }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                No kad pengenalan
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->nokp }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                Jantina
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->jantina }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                Jawatan
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->jawatan_gred?->jawatan?->desc_jawatan }}
            </td>
        </tr>
        <tr>
            <th class="border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">
                Gred
            </th>
            <td class="border border-gray-200 dark:border-white/10 px-3 py-2">
                {{ $record->jawatan_gred?->gred?->kod_gred }}
            </td>
        </tr>
    </tbody>
</table>