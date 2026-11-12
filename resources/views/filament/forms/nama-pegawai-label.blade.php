<span class="inline-flex items-center gap-1.5">
    <span>Nama Pegawai </span>
    <button
        type="button"
        class="inline-flex shrink-0 text-gray-400 transition hover:text-primary-600 dark:text-gray-500 dark:hover:text-primary-400"
        x-on:click.stop
        x-tooltip="{
            content: @js('Jika nama pegawai tidak wujud, sila tambah maklumat pegawai di bahagian maklumat penyandang terlebih dahulu.'),
            theme: $store.theme,
            trigger: 'mouseenter focus click',
            hideOnClick: 'toggle',
            maxWidth: 320,
        }"
        aria-label="Maklumat nama pegawai"
    >
        <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
    </button>
</span>
