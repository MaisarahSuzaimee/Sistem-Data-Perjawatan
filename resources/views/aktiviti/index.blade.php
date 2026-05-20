{{-- ============================================================
     resources/views/aktiviti/index.blade.php
     Senarai Aktiviti dengan carian dan filter program
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Senarai Aktiviti')

@section('content')

{{-- Header: Tajuk + butang tambah --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai Aktiviti</h4>
    <a href="{{ route('aktiviti.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah
    </a>
</div>

<div class="card">

    {{-- Header kad + jumlah rekod --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Aktiviti</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>

    {{-- Search + Filter Program --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('aktiviti.index') }}" id="filterForm">
            <div class="row g-2 align-items-center justify-content-end">

                {{-- Filter dropdown pilih Program --}}
                <div class="col-md-2">
                    <select name="program_id" class="form-select text-center" onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- Semua Program --</option>
                        @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ $program_id == $program->id ? 'selected' : '' }}>
                            {{ $program->nama_program }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Search bar --}}
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari no. atau nama aktiviti..."
                            value="{{ $search ?? '' }}"
                            oninput="debounceSearch(this.form)">
                        @if(!empty($search) || !empty($program_id))
                        <a href="{{ route('aktiviti.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x"></i>
                        </a>
                        @endif
                    </div>
                </div>

            </div>
        </form>

        {{-- Badge filter aktif --}}
        @if(!empty($search) || !empty($program_id))
        <div class="mt-2 d-flex gap-2 justify-content-center">
            @if(!empty($program_id))
            <span class="badge bg-primary">
                Program: {{ $programs->firstWhere('id', $program_id)->nama_program ?? '' }}
                <a href="{{ route('aktiviti.index', ['search' => $search]) }}" class="text-white ms-1"><i class="bx bx-x"></i></a>
            </span>
            @endif
            @if(!empty($search))
            <span class="badge bg-warning text-dark">
                Search: {{ $search }}
                <a href="{{ route('aktiviti.index', ['program_id' => $program_id]) }}" class="text-dark ms-1"><i class="bx bx-x"></i></a>
            </span>
            @endif
        </div>
        @endif
    </div>
    {{-- Jadual senarai Aktiviti --}}
    <div class="table-responsive">
        <table class="table table-hover">

            {{-- Kepala jadual --}}
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>No. Aktiviti</th>
                    <th>Nama Aktiviti</th>
                    <th>Program</th>
                    <th>Tindakan</th>
                </tr>
            </thead>

            <tbody>
                {{-- Loop semua rekod --}}
                @forelse($items as $item)
                <tr>
                    {{-- Nombor baris dengan pagination --}}
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->no_aktivit }}</td>
                    <td>{{ $item->nama_aktiviti }}</td>
                    <td>{{ $item->program->nama_program ?? '-' }}</td>

                    {{-- Butang tindakan --}}
                    <td>
                        {{-- Edit --}}
                        <a href="{{ route('aktiviti.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning">
                            <i class="bx bx-edit"></i>
                        </a>
                        {{-- Padam dengan SweetAlert2 --}}
                        <button type="button" class="btn btn-sm btn-icon btn-text-danger"
                            onclick="confirmDelete('{{ route('aktiviti.destroy', $item) }}', 'Aktiviti')">
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