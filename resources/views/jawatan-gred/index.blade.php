{{-- ============================================================
     resources/views/jawatan-gred/index.blade.php
     Senarai semua Jawatan & Gred
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Senarai Jawatan & Gred')

@section('content')

{{-- Header: Tajuk + butang tambah --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Jawatan & Gred</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bx bx-plus me-1"></i> Tambah
    </button>
</div>

<div class="card">

    {{-- Header kad: tajuk + search + jumlah --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Senarai</h5>

        <div class="d-flex align-items-center gap-2">
            {{-- Search bar --}}
            <form method="GET" action="{{ route('jawatan-gred.index') }}" id="searchForm">
                <div class="input-group input-group-sm" style="width: 280px;">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari jawatan atau gred..."
                        value="{{ $search ?? '' }}"
                        oninput="debounceSearch(this.form)">
                    @if(!empty($search))
                    <a href="{{ route('jawatan-gred.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-x"></i>
                    </a>
                    @endif
                </div>
            </form>

            {{-- Jumlah rekod --}}
            <small class="text-muted text-nowrap">Jumlah: {{ $items->total() }}</small>
        </div>
    </div>

    {{-- Jadual --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Kod Jawatan</th>
                <th>Nama Jawatan</th>
                <th>Skim</th>
                <th>Gred</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                <td>{{ $item->kod_jawatan ?? '-' }}</td>
                <td>{{ $item->desc_jawatan ?? '-' }}</td>
                <td>{{ $item->skim ?? '-' }}</td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse($item->greds as $gred)
                            <span class="badge bg-label-primary">{{ $gred->kod_gred }}</span>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                    </div>
                </td>
                <td>
                    <a href="{{ route('jawatan-gred.edit', $item->id) }}" class="btn btn-sm btn-icon btn-text-warning">
                        <i class="bx bx-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-icon btn-text-danger"
                        onclick="confirmDelete('{{ route('jawatan-gred.destroy', $item->id) }}', 'Jawatan & Gred')">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-4">Tiada rekod ditemui.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

    <div class="card-footer">{{ $items->links() }}</div>
</div>

{{-- Modal Tambah Jawatan & Gred --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jawatan & Gred</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('jawatan-gred.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">

                        {{-- Kod Jawatan --}}
                        <div class="col-md-4">
                            <label class="form-label fs-6">Kod Jawatan <span class="text-danger">*</span></label>
                            <input type="text" name="kod_jawatan"
                                class="form-control @error('kod_jawatan') is-invalid @enderror"
                                value="{{ old('kod_jawatan') }}"
                                style="text-transform: uppercase"
                                placeholder="Contoh: C, F, M">
                            @error('kod_jawatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Nama Jawatan --}}
                        <div class="col-md-8">
                            <label class="form-label fs-6">Nama Jawatan <span class="text-danger">*</span></label>
                            <input type="text" name="desc_jawatan"
                                class="form-control @error('desc_jawatan') is-invalid @enderror"
                                value="{{ old('desc_jawatan') }}"
                                style="text-transform: uppercase"
                                placeholder="Contoh: PEGAWAI TEKNOLOGI MAKLUMAT">
                            @error('desc_jawatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Skim----this one user yg choose, kalau nk buat bagi dia ikut jawatan nnti tgok len --}} 
                        <div class="col-12">
                            <label class="form-label fs-6">Skim</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['Pakar', 'Pegawai Perubatan', 'P&P', 'Paramedik', 'Pelaksana'] as $skim)
                                <input type="radio" class="btn-check" name="skim" 
                                    id="skim_{{ Str::slug($skim) }}" 
                                    value="{{ $skim }}"
                                    {{ old('skim') == $skim ? 'checked' : '' }}
                                    autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm" for="skim_{{ Str::slug($skim) }}">
                                    {{ $skim }}
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Pilih Gred - checkbox --}}
                        <div class="col-12">
                            <label class="form-label fs-6">Gred</label>
                            <div class="d-flex flex-wrap gap-2" id="gredCheckboxes">
                                @foreach($greds as $gred)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        name="gred_ids[]"
                                        value="{{ $gred->id }}"
                                        id="gred_{{ $gred->id }}"
                                        {{ in_array($gred->id, old('gred_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="gred_{{ $gred->id }}">
                                        {{ $gred->kod_gred }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let searchTimer;
function debounceSearch(form) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => form.submit(), 500);
}

@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalTambah')).show();
});
@endif
</script>
@endpush