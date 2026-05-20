{{-- ============================================================
     resources/views/unit/edit.blade.php
     Borang kemaskini Unit sedia ada
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Edit Unit')

@section('content')

{{-- Header: Tajuk + butang kembali --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Unit</h4>
    <a href="{{ route('unit.index') }}" class="btn btn-outline-secondary">
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

{{-- Borang kemaskini unit --}}
<form method="POST" action="{{ route('unit.update', $unit) }}">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Unit</h5></div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Dropdown pilih Bahagian, nilai sedia ada dipilih semula --}}
                <div class="col-12">
                    <label class="form-label fs-6">Bahagian <span class="text-danger">*</span></label>
                    <select name="bahagian_id" class="form-select @error('bahagian_id') is-invalid @enderror">
                        <option value="">-- Pilih Bahagian --</option>
                        @foreach($bahagians as $bahagian)
                        <option value="{{ $bahagian->id }}" {{ old('bahagian_id', $unit->bahagian_id) == $bahagian->id ? 'selected' : '' }}>
                            {{ $bahagian->nama_bahagian }}
                        </option>
                        @endforeach
                    </select>
                    @error('bahagian_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Input nama unit, nilai sedia ada diisi semula --}}
                <div class="col-12">
                    <label class="form-label fs-6">Nama Unit <span class="text-danger">*</span></label>
                    <input type="text" name="nama_unit"
                        class="form-control @error('nama_unit') is-invalid @enderror"
                        value="{{ old('nama_unit', $unit->nama_unit) }}">
                    @error('nama_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Butang kemaskini dan batal --}}
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Kemaskini
        </button>
        <a href="{{ route('unit.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>

</form>
@endsection