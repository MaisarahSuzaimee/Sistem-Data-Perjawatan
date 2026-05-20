{{-- ============================================================
     resources/views/subunit/create.blade.php
     Borang tambah Subunit baru
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Tambah Subunit')

@section('content')

{{-- Header: Tajuk + butang kembali --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Subunit</h4>
    <a href="{{ route('subunit.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

{{-- Papar ralat validasi jika ada --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Borang tambah subunit --}}
<form method="POST" action="{{ route('subunit.store') }}">
    @csrf

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Subunit</h5></div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Dropdown pilih Unit --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Unit <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
                        <option value="">-- Pilih Unit --</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->nama_unit }}
                        </option>
                        @endforeach
                    </select>
                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Dropdown pilih DUN --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">DUN</label>
                    <select name="dun_id" class="form-select @error('dun_id') is-invalid @enderror">
                        <option value="">-- Pilih DUN --</option>
                        @foreach($duns as $dun)
                        <option value="{{ $dun->id }}" {{ old('dun_id') == $dun->id ? 'selected' : '' }}>
                            {{ $dun->nama_dun }}
                        </option>
                        @endforeach
                    </select>
                    @error('dun_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Input nama subunit --}}
                <div class="col-12">
                    <label class="form-label fs-6">Nama Subunit <span class="text-danger">*</span></label>
                    <input type="text" name="nama_subunit"
                        class="form-control @error('nama_subunit') is-invalid @enderror"
                        value="{{ old('nama_subunit') }}">
                    @error('nama_subunit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Butang simpan dan batal --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Simpan
        </button>
        <a href="{{ route('subunit.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>

</form>
@endsection