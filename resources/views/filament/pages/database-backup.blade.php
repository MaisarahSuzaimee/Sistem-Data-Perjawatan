<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Database Backup
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage and monitor MySTAFF database backups.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                    Backup service not configured
                </span>
            </div>
        </div>
        <div class="mb-4">
            <a href="{{ route('google-drive.connect') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                Connect Google Drive
            </a>
        </div>

        {{-- Backup summary cards --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Available Backups
                </p>

                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ $backupCount }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Recorded backup files
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Latest Backup
                </p>

                <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $latestBackup?->completed_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') ?? 'Not available' }}
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Most recent completed backup
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Backup Size
                </p>

                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($totalSize / 1024 / 1024, 2) }} MB
                </p>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Combined recorded file sizes
                </p>
            </div>

        </div>



        {{-- Backup Settings --}}
        <section
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">

            <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="rounded-lg bg-amber-50 p-2 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                    <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-5 w-5" />
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Backup Configuration
                    </h3>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Intended schedule and storage settings
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-200 sm:grid-cols-2 sm:divide-y-0 dark:divide-gray-800">

                <div class="flex items-start gap-3 p-5">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Backup Schedule
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                            Every day at 6:00 PM
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Asia/Kuala_Lumpur
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-5 sm:border-l sm:border-gray-200 dark:sm:border-gray-800">
                    <x-filament::icon icon="heroicon-o-calendar" class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Retention Period
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                            30 days
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Automatic cleanup planned
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 border-t border-gray-200 p-5 dark:border-gray-800">
                    <x-filament::icon icon="heroicon-o-document-arrow-down"
                        class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Backup Format
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                            SQL + GZIP
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            .sql.gz
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-start gap-3 border-t border-gray-200 p-5 sm:border-l sm:border-gray-200 dark:border-gray-800 dark:sm:border-gray-800">
                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Storage Destination
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                            Google Workspace Shared Drive
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Awaiting integration
                        </p>
                    </div>
                </div>

            </div>
        </section>

        {{-- Backup History --}}
        <section
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">

            <div
                class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Backup History
                    </h3>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Review previously generated backup files.
                    </p>
                </div>

                {{-- <x-filament::button
                    color="gray"
                    icon="heroicon-o-plus"
                    disabled
                >
                    Create Backup
                </x-filament::button> --}}
            </div>
            @if ($backups->isEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="px-5 py-3 font-medium text-gray-600 dark:text-gray-300">
                                    Backup File
                                </th>

                                <th class="px-5 py-3 font-medium text-gray-600 dark:text-gray-300">
                                    Created At
                                </th>

                                <th class="px-5 py-3 font-medium text-gray-600 dark:text-gray-300">
                                    Size
                                </th>

                                <th class="px-5 py-3 font-medium text-center text-gray-600 dark:text-gray-300">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                    Google Drive
                                </th>

                                <th class="px-5 py-3  font-medium text-center text-gray-600 dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                                    <x-filament::icon icon="heroicon-o-archive-box" class="h-7 w-7 text-gray-400" />
                                </div>

                                <p class="mt-4 text-sm font-semibold text-gray-950 dark:text-white">
                                    No backups yet
                                </p>

                                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                    Your backup history will appear here once the backup service has been
                                    implemented and a backup has been created.
                                </p>
                            </div>
                        </td>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="px-5 py-3 font-medium text-gray-600 dark:text-gray-300">
                                    Backup File
                                </th>

                                <th class="px-5 py-3 font-medium text-gray-600 dark:text-gray-300">
                                    Created At
                                </th>

                                <th class="px-5 py-3 font-medium text-gray-600 dark:text-gray-300">
                                    Size
                                </th>

                                <th class="px-5 py-3 font-medium text-center text-gray-600 dark:text-gray-300">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                    Google Drive
                                </th>

                                <th class="px-5 py-3  font-medium text-center text-gray-600 dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($backups as $backup)
                                <tr>
                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $backup->filename }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $backup->completed_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ number_format($backup->size_bytes / 1024, 2) }} KB
                                    </td>



                                    <td class="whitespace-nowrap px-5 py-4 text-center">
                                        @if ($backup->status === 'completed')
                                            <span
                                                class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                Completed
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                {{ ucfirst($backup->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-center">
                                        @if ($backup->google_drive_status === 'uploaded')
                                            <div class="flex flex-col items-center gap-1">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                                                    Uploaded
                                                </span>

                                                @if ($backup->google_drive_web_view_link)
                                                    <a href="{{ $backup->google_drive_web_view_link }}" target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                                                        View in Drive
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif ($backup->google_drive_status === 'failed')
                                            <span
                                                class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                Upload failed
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                Not uploaded
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($backup->status === 'completed')
                                            <div class="flex flex-wrap items-center justify-center gap-2">
                                                <a href="{{ route('database-backups.download', $backup->id) }}"
                                                    class="inline-flex items-center gap-2 rounded-lg
                                                                bg-amber-900 px-3 py-2 text-sm font-medium
                                                                text-white transition hover:bg-amber-800
                                                                focus:outline-none focus:ring-2
                                                                focus:ring-amber-700 focus:ring-offset-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3" />
                                                    </svg>

                                                    Download
                                                </a>
                                                @if ($backup->google_drive_status !== 'uploaded')
                                                    <button type="button"
                                                        wire:click="uploadToGoogleDrive({{ $backup->id }})"
                                                        wire:confirm="Upload {{ $backup->filename }} to Google Drive?"
                                                        wire:loading.attr="disabled"
                                                        wire:target="uploadToGoogleDrive({{ $backup->id }})"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-70">
                                                        <x-filament::icon icon="heroicon-o-cloud-arrow-up"
                                                            class="h-4 w-4" />
                                                        <span wire:loading.remove
                                                            wire:target="uploadToGoogleDrive({{ $backup->id }})">
                                                            Upload to Drive
                                                        </span>
                                                        <span wire:loading
                                                            wire:target="uploadToGoogleDrive({{ $backup->id }})">
                                                            Uploading...
                                                        </span>
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">
                                                Unavailable
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Retention Information --}}
            <div
                class="flex items-start gap-2 border-t border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-800 dark:bg-gray-800/30">
                <x-filament::icon icon="heroicon-o-information-circle"
                    class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                    The planned retention policy keeps 30 days of backups. Expired files must only be deleted after
                    the
                    replacement backup has been verified successfully.
                </p>
            </div>

        </section>

    </div>
</x-filament-panels::page>
