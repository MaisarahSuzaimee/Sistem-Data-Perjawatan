@extends('layouts.app')
@section('title', 'Program & Aktiviti')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Program & Aktiviti</h4>
    <a href="{{ route('program.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah Program
    </a>
</div>

<p class="text-muted mb-4">Pilih program untuk melihat senarai aktiviti</p>

@php
$colors = [
    1 => ['bg' => '#fff3cd', 'text' => '#856404', 'border' => '#ffc107'],
    2 => ['bg' => '#d1e7dd', 'text' => '#0a3622', 'border' => '#198754'],
    3 => ['bg' => '#cff4fc', 'text' => '#055160', 'border' => '#0dcaf0'],
    4 => ['bg' => '#f8d7da', 'text' => '#58151c', 'border' => '#dc3545'],
    5 => ['bg' => '#e2d9f3', 'text' => '#3d0a6e', 'border' => '#6f42c1'],
    6 => ['bg' => '#fde8d8', 'text' => '#7c3100', 'border' => '#fd7e14'],
    7 => ['bg' => '#d3e5ff', 'text' => '#002752', 'border' => '#0d6efd'],
];
@endphp

<div class="row g-4">
    @forelse($items as $program)
    @php
        $colorIndex = ($loop->iteration % 7) ?: 7;
        $color = $colors[$colorIndex];
    @endphp
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 cursor-pointer program-card" 
             style="border-top: 4px solid {{ $color['border'] }}; cursor: pointer;"
             onclick="window.location='{{ route('program.show', $program) }}'">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <span class="badge mb-2" style="background-color: {{ $color['bg'] }}; color: {{ $color['text'] }}; font-size: 12px;">
                            {{ $program->nama_program }}
                        </span>
                        <h6 class="mb-0">{{ $program->desc_program }}</h6>
                    </div>
                    <div class="d-flex gap-1 ms-2" onclick="event.stopPropagation()">
                        <a href="{{ route('program.edit', $program) }}" class="btn btn-sm btn-icon btn-text-warning" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>
                        <form action="{{ route('program.destroy', $program) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Padam program ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-icon btn-text-danger" title="Padam">
                                <i class="bx bx-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bx bx-list-ul me-1"></i>
                    {{ $program->aktiviti_count }} aktiviti
                </small>
                <small class="text-primary">Lihat aktiviti →</small>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bx bx-book-open" style="font-size: 48px; color: #ccc;"></i>
                <p class="mt-2 text-muted">Tiada rekod program.</p>
                <a href="{{ route('program.create') }}" class="btn btn-primary">Tambah Program</a>
            </div>
        </div>
    </div>
    @endforelse
</div>

@if($items->hasPages())
<div class="mt-4">{{ $items->links() }}</div>
@endif

@endsection
