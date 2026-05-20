{{-- ============================================================
     resources/views/aktiviti/create.blade.php
     Borang tambah Aktiviti baru
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Tambah Aktiviti')

@section('content')

{{-- Header: Tajuk + butang kembali --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Aktiviti</h4>
    <a href="{{ route('aktiviti.index') }}" class="btn btn-outline-secondary">
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

{{-- Borang tambah aktiviti --}}
<form method="POST" action="{{ route('aktiviti.store') }}">
    @csrf

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Aktiviti</h5></div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Dropdown pilih Program --}}
                <div class="col-12">
                    <label class="form-label fs-6">Program <span class="text-danger">*</span></label>
                    <select name="program_id" class="form-select @error('program_id') is-invalid @enderror">
                        <option value="">-- Pilih Program --</option>
                        @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                            {{ $program->nama_program }}
                        </option>
                        @endforeach
                    </select>
                    @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Input no aktiviti --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">No. Aktiviti <span class="text-danger">*</span></label>
                    <input type="text" name="no_aktivit"
                        class="form-control @error('no_aktivit') is-invalid @enderror"
                        value="{{ old('no_aktivit') }}">
                    @error('no_aktivit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Input nama aktiviti --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Nama Aktiviti <span class="text-danger">*</span></label>
                    <input type="text" name="nama_aktiviti"
                        class="form-control @error('nama_aktiviti') is-invalid @enderror"
                        value="{{ old('nama_aktiviti') }}">
                    @error('nama_aktiviti')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Input description aktiviti --}}
                <div class="col-12">
                    <label class="form-label fs-6">Deskripsi Aktiviti</label>
                    <textarea name="desc_aktiviti" rows="3"
                        class="form-control @error('desc_aktiviti') is-invalid @enderror">{{ old('desc_aktiviti') }}</textarea>
                    @error('desc_aktiviti')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Butang simpan dan batal --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Simpan
        </button>
        <a href="{{ route('aktiviti.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>

</form>
@endsection