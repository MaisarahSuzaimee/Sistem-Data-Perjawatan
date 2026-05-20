{{-- ============================================================
     resources/views/subunit/index.blade.php
     Senarai semua Subunit dengan carian dan tindakan
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Senarai Subunit')

@section('content')

{{-- Header: Tajuk + butang tambah --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai Subunit</h4>
    <a href="{{ route('subunit.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah
    </a>
</div>

{{-- Kad utama --}}
<div class="card">

    {{-- Header kad + jumlah rekod --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Subunit</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>

    {{-- Search bar --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('subunit.index') }}">
            <div class="input-group" style="max-width: 400px; margin-left: auto;">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" name="search" class="form-control"
                    placeholder="Cari nama subunit atau unit..."
                    value="{{ $search ?? '' }}"
                    oninput="debounceSearch(this.form)">
                @if(!empty($search))
                <a href="{{ route('subunit.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-x"></i>
                </a>
                @endif
            </div>
        </form>

        {{-- Badge carian aktif --}}
        @if(!empty($search))
        <div class="mt-2">
            <span class="text-muted small">Active filters:</span>
            <span class="badge bg-warning text-dark ms-1">
                Search: {{ $search }}
                <a href="{{ route('subunit.index') }}" class="text-dark ms-1"><i class="bx bx-x"></i></a>
            </span>
        </div>
        @endif
    </div>

    {{-- Jadual senarai Subunit --}}
    <div class="table-responsive">
        <table class="table table-hover">

            {{-- Kepala jadual --}}
            <thead class="table-light">
                <tr>
                    <th>Bil</th>
                    <th>Unit</th>
                    <th>DUN</th>
                    <th>Nama Subunit</th>
                    <th>Tindakan</th>
                </tr>
            </thead>

            <tbody>
                {{-- Loop semua rekod, tunjuk mesej jika kosong --}}
                @forelse($items as $item)
                <tr>
                    {{-- Nombor baris dengan pagination --}}
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->unit->nama_unit ?? '-' }}</td>
                    <td>{{ $item->dun->nama_dun ?? '-' }}</td>
                    <td>{{ $item->nama_subunit ?? '-' }}</td>

                    {{-- Butang tindakan --}}
                    <td>
                        {{-- Edit --}}
                        <a href="{{ route('subunit.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning">
                            <i class="bx bx-edit"></i>
                        </a>
                        {{-- Padam dengan SweetAlert2 --}}
                        <button type="button" class="btn btn-sm btn-icon btn-text-danger"
                            onclick="confirmDelete('{{ route('subunit.destroy', $item) }}', 'Subunit')">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4">Tiada rekod ditemui.</td></tr>
                @endforelse
            </tbody>

        </table>
    </div>

    {{-- Pagination --}}
    <div class="card-footer">{{ $items->links() }}</div>

</div>
@endsection

{{-- JS: Auto submit search selepas berhenti menaip --}}
@push('scripts')
<script>
let searchTimer;
function debounceSearch(form) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => form.submit(), 500);
}
</script>
@endpush