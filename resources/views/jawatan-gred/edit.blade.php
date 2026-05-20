{{-- ============================================================
     resources/views/jawatan-gred/edit.blade.php
     Borang edit Jawatan & Gred menggunakan Livewire
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Edit Jawatan & Gred')

@section('content')

{{-- Header: Tajuk + butang kembali --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Jawatan & Gred</h4>
    <a href="{{ route('jawatan-gred.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

{{-- Livewire form component dengan editId --}}
@livewire('jawatan-gred-form', ['editId' => $jawatanGred->id])

@endsection