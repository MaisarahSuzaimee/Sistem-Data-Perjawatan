{{-- ============================================================
     resources/views/jawatan-gred/create.blade.php
     Borang tambah Jawatan & Gred baru menggunakan Livewire
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Tambah Jawatan & Gred')

@section('content')

{{-- Header: Tajuk + butang kembali --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Jawatan & Gred</h4>
    <a href="{{ route('jawatan-gred.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

{{-- Livewire form component --}}
@livewire('jawatan-gred-form')

@endsection