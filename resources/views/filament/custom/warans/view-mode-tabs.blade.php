@php
    $isView = $isViewPage ?? false;
@endphp

<div class="sm:hidden">
    <select wire:model.live="viewMode"
        class="block w-full px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base">
        <option value="catalog">Semua</option>
        <option value="dibuang">Ditolak</option>
    </select>
</div>

<ul class="hidden sm:flex text-sm font-medium text-center -space-x-px">
    <li class="w-full">
        <button wire:click="$set('viewMode', 'catalog')" type="button"
            class="w-full whitespace-nowrap px-4 py-2.5 border border-white rounded-s-base transition
                {{ $this->viewMode === 'catalog'
                    ? 'bg-blue-500 text-white'
                    : 'bg-neutral-50 text-black' }}">
            Semua
        </button>
    </li>
    <li class="w-full">
        <button wire:click="$set('viewMode', 'dibuang')" type="button"
            class="w-full whitespace-nowrap px-4 py-2.5 border border-white rounded-e-base transition
                {{ $this->viewMode === 'dibuang'
                    ? 'bg-blue-500 text-white'
                    : 'bg-neutral-50 text-black' }}">
            Ditolak
        </button>
    </li>
</ul>
