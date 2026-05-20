@extends('layouts.app')
@section('title', 'Edit Program')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Program & Aktiviti</h4>
    <a href="{{ route('program.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('program.update', $program) }}" id="programForm">
    @csrf @method('PUT')

    {{-- Program Section --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Program</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">No Program <span class="text-danger">*</span></label>
                    <input type="text" name="nama_program"
                        class="form-control @error('nama_program') is-invalid @enderror"
                        value="{{ old('nama_program', $program->nama_program) }}"
                        style="text-transform:uppercase"
                        oninput="this.value = this.value.toUpperCase()"
                        required>
                    @error('nama_program')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Program</label>
                    <input type="text" name="desc_program"
                        class="form-control @error('desc_program') is-invalid @enderror"
                        value="{{ old('desc_program', $program->desc_program) }}"
                        style="text-transform:uppercase"
                        oninput="this.value = this.value.toUpperCase()">
                    @error('desc_program')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Aktiviti Section --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Aktiviti</h5>
            <button type="button" class="btn btn-info btn-sm" onclick="addAktiviti()">
                <i class="bx bx-plus me-1"></i> Tambah Aktiviti
            </button>
        </div>
        <div class="card-body">
            <div id="aktiviti-container">

                @forelse($program->aktiviti as $i => $aktiviti)
                <div class="aktiviti-row row g-3 mb-3 align-items-end" data-index="{{ $i }}">
                    <input type="hidden" name="aktiviti[{{ $i }}][id]" value="{{ $aktiviti->id }}">
                    <div class="col-md-4">
                        <label class="form-label">No Aktiviti <span class="text-danger">*</span></label>
                        <input type="text" name="aktiviti[{{ $i }}][no_aktivit]"
                            class="form-control"
                            value="{{ $aktiviti->no_aktivit }}"
                            style="text-transform:uppercase"
                            oninput="this.value = this.value.toUpperCase()"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Aktiviti</label>
                        <input type="text" name="aktiviti[{{ $i }}][nama_aktiviti]"
                            class="form-control"
                            value="{{ $aktiviti->nama_aktiviti }}"
                            style="text-transform:uppercase"
                            oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-icon btn-text-danger" onclick="removeAktiviti(this)" title="Padam">
                            <i class="bx bx-trash bx-sm"></i>
                        </button>
                    </div>
                </div>
                @empty
                {{-- Show empty row if no aktiviti --}}
                <div class="aktiviti-row row g-3 mb-3 align-items-end" data-index="0">
                    <div class="col-md-4">
                        <label class="form-label">No Aktiviti <span class="text-danger">*</span></label>
                        <input type="text" name="aktiviti[0][no_aktivit]"
                            class="form-control"
                            style="text-transform:uppercase"
                            oninput="this.value = this.value.toUpperCase()"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Aktiviti</label>
                        <input type="text" name="aktiviti[0][nama_aktiviti]"
                            class="form-control"
                            style="text-transform:uppercase"
                            oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-icon btn-text-danger" onclick="removeAktiviti(this)" title="Padam">
                            <i class="bx bx-trash bx-sm"></i>
                        </button>
                    </div>
                </div>
                @endforelse

            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Kemaskini
        </button>
        <a href="{{ route('program.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
let aktivitiCount = {{ $program->aktiviti->count() ?: 1 }};

function addAktiviti() {
    const container = document.getElementById('aktiviti-container');
    const index = aktivitiCount++;
    const row = document.createElement('div');
    row.className = 'aktiviti-row row g-3 mb-3 align-items-end';
    row.setAttribute('data-index', index);
    row.innerHTML = `
        <div class="col-md-4">
            <label class="form-label">No Aktiviti <span class="text-danger">*</span></label>
            <input type="text" name="aktiviti[${index}][no_aktivit]"
                class="form-control"
                style="text-transform:uppercase"
                oninput="this.value = this.value.toUpperCase()"
                required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama Aktiviti</label>
            <input type="text" name="aktiviti[${index}][nama_aktiviti]"
                class="form-control"
                style="text-transform:uppercase"
                oninput="this.value = this.value.toUpperCase()">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-icon btn-text-danger" onclick="removeAktiviti(this)" title="Padam">
                <i class="bx bx-trash bx-sm"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
}

function removeAktiviti(btn) {
    const rows = document.querySelectorAll('.aktiviti-row');
    if (rows.length > 1) {
        btn.closest('.aktiviti-row').remove();
    }
}
</script>
@endpush
