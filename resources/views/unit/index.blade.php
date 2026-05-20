{{-- ============================================================
     resources/views/unit/index.blade.php
     Senarai semua Unit dengan carian dan tindakan
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Senarai Unit')

@section('content')

{{-- Header: Tajuk + butang tambah --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai Unit</h4>
    <a href="{{ route('unit.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah
    </a>
</div>

{{-- Kad utama --}}
<div class="card">

    {{-- Header kad + jumlah rekod --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Unit</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>

    {{-- Search bar --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('unit.index') }}">
            <div class="input-group" style="max-width: 400px; margin-left: auto;">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" name="search" class="form-control"
                    placeholder="Cari nama unit atau bahagian..."
                    value="{{ $search ?? '' }}">
                @if(!empty($search))
                <a href="{{ route('unit.index') }}" class="btn btn-outline-secondary">
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
                <a href="{{ route('unit.index') }}" class="text-dark ms-1"><i class="bx bx-x"></i></a>
            </span>
        </div>
        @endif
    </div>

    {{-- Jadual senarai Unit --}}
    <div class="table-responsive">
        <table class="table table-hover">

            {{-- Kepala jadual --}}
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Bahagian</th>
                    <th>Nama Unit</th>
                    <th>Tindakan</th>
                </tr>
            </thead>

            <tbody>
                {{-- Loop semua rekod, tunjuk mesej jika kosong --}}
                @forelse($items as $item)
                <tr>
                    {{-- Nombor baris dengan pagination --}}
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->bahagian->nama_bahagian ?? '-' }}</td>
                    <td>{{ $item->nama_unit ?? '-' }}</td>

                    {{-- Butang tindakan --}}
                    <td>
                        {{-- Edit --}}
                        <a href="{{ route('unit.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning">
                            <i class="bx bx-edit"></i>
                        </a>
                        {{-- Padam dengan SweetAlert2 --}}
                        <button type="button" class="btn btn-sm btn-icon btn-text-danger"
                            onclick="confirmDelete('{{ route('unit.destroy', $item) }}', 'Unit')">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4">Tiada rekod ditemui.</td></tr>
                @endforelse
            </tbody>

        </table>
    </div>

    {{-- Pagination --}}
    <div class="card-footer">{{ $items->links() }}</div>

</div>
@endsection