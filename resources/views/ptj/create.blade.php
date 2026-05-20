@extends('layouts.app')
@section('title', 'Tambah PTJ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah PTJ</h4>
    <a href="{{ route('ptj.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('ptj.store') }}">
    @csrf
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat PTJ</h5></div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-12">
                    <label class="form-label fs-6">Nama PTJ <span class="text-danger">*</span></label>
                    <input type="text" name="nama_ptj"
                        class="form-control @error('nama_ptj') is-invalid @enderror"
                        value="{{ old('nama_ptj') }}">
                    @error('nama_ptj')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Kod PTJ <span class="text-danger">*</span></label>
                    <input type="text" name="kod_ptj"
                        class="form-control @error('kod_ptj') is-invalid @enderror"
                        value="{{ old('kod_ptj') }}">
                    @error('kod_ptj')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Rujukan Surat <span class="text-danger">*</span></label>
                    <input type="text" name="rujukan_surat"
                        class="form-control @error('rujukan_surat') is-invalid @enderror"
                        value="{{ old('rujukan_surat') }}">
                    @error('rujukan_surat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fs-6">Pengarah <span class="text-danger">*</span></label>
                    <input type="text" name="pengarah"
                        class="form-control @error('pengarah') is-invalid @enderror"
                        value="{{ old('pengarah') }}">
                    @error('pengarah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fs-6">Alamat <span class="text-danger">*</span></label>
                    <textarea name="alamat" rows="3"
                        class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat') }}</textarea>
                    @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_jkn" value="1" id="is_jkn" {{ old('is_jkn') ? 'checked' : '' }}>
                        <label class="form-check-label fs-6" for="is_jkn">Is JKN</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Simpan
        </button>
        <a href="{{ route('ptj.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection