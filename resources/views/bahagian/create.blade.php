{{-- ============================================================
     resources/views/bahagian/create.blade.php
     Borang tambah Bahagian baru
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Tambah Bahagian')

@section('content')

{{-- Header: Tajuk + butang kembali --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Bahagian</h4>
    <a href="{{ route('bahagian.index') }}" class="btn btn-outline-secondary">
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

{{-- Borang tambah bahagian --}}
<form method="POST" action="{{ route('bahagian.store') }}">
    @csrf

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Bahagian</h5></div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Dropdown pilih PTJ --}}
                <div class="col-12">
                    <label class="form-label fs-6">PTJ <span class="text-danger">*</span></label>
                    <select name="ptj_id" class="form-select @error('ptj_id') is-invalid @enderror">
                        <option value="">-- Pilih PTJ --</option>
                        @foreach($ptjs as $ptj)
                        <option value="{{ $ptj->id }}" {{ old('ptj_id') == $ptj->id ? 'selected' : '' }}>
                            {{ $ptj->nama_ptj }}
                        </option>
                        @endforeach
                    </select>
                    @error('ptj_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Input nama bahagian --}}
                <div class="col-12">
                    <label class="form-label fs-6">Nama Bahagian <span class="text-danger">*</span></label>
                    <input type="text" name="nama_bahagian"
                        class="form-control @error('nama_bahagian') is-invalid @enderror"
                        value="{{ old('nama_bahagian') }}">
                    @error('nama_bahagian')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Butang simpan dan batal --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Simpan
        </button>
        <a href="{{ route('bahagian.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>

</form>
@endsection