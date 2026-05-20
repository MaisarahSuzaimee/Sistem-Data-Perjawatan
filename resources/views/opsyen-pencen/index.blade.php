@extends('layouts.app')
@section('title', 'Senarai Opsyen Pencen')

@section('content')

{{-- Header: Tajuk + butang tambah yang trigger modal --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai Opsyen Pencen</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bx bx-plus me-1"></i> Tambah
    </button>
</div>

{{-- Papar mesej kejayaan --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Opsyen Pencen</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Opsyen</th>
                    <th>Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->opsyen ?? '-' }}</td>
                    <td>
                        {{-- Butang edit - trigger modal edit --}}
                        <button type="button" class="btn btn-sm btn-icon btn-text-warning"
                            onclick="openEditModal({{ $item->id }}, '{{ $item->opsyen }}')">
                            <i class="bx bx-edit"></i>
                        </button>
                        {{-- Butang padam --}}
                        <button type="button" class="btn btn-sm btn-icon btn-text-danger"
                            onclick="confirmDelete('{{ route('opsyen-pencen.destroy', $item) }}', 'Opsyen Pencen')">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-4">Tiada rekod.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Opsyen Pencen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('opsyen-pencen.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fs-6">Opsyen Pencen <span class="text-danger">*</span></label>
                        <input type="text" name="opsyen"
                            class="form-control @error('opsyen') is-invalid @enderror"
                            value="{{ old('opsyen') }}"
                            placeholder="Contoh: 55, 56, 58, 60"
                            required>
                        @error('opsyen')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

{{-- Modal Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Opsyen Pencen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fs-6">Opsyen Pencen <span class="text-danger">*</span></label>
                        <input type="text" name="opsyen" id="editOpsyen"
                            class="form-control"
                            required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Kemaskini
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Buka modal edit dengan data semasa
function openEditModal(id, opsyen) {
    document.getElementById('editOpsyen').value = opsyen;
    document.getElementById('editForm').action = '/opsyen-pencen/' + id;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// Auto buka modal tambah jika ada validation error
@if($errors->any() && old('opsyen'))
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalTambah')).show();
});
@endif
</script>
@endpush