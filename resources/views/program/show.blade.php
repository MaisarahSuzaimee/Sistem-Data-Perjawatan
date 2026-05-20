@extends('layouts.app')
@section('title', $program->nama_program . ' - Aktiviti')

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('program.index') }}">Program & Aktiviti</a>
        </li>
        <li class="breadcrumb-item active">{{ $program->nama_program }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $program->nama_program }} - {{ $program->desc_program }}</h4>
        <p class="text-muted mb-0">{{ $program->aktiviti->count() }} aktiviti</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('program.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Kembali
        </a>
        <a href="{{ route('program.edit', $program) }}" class="btn btn-warning">
            <i class="bx bx-edit me-1"></i> Edit Program
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Senarai Aktiviti</h5>
        <a href="{{ route('program.edit', $program) }}" class="btn btn-info btn-sm">
            <i class="bx bx-plus me-1"></i> Tambah Aktiviti
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">#</th>
                    <th style="width:150px">No. Aktiviti</th>
                    <th>Nama Aktiviti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($program->aktiviti->sortBy('no_aktivit') as $i => $aktiviti)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <span class="badge bg-label-primary">{{ $aktiviti->no_aktivit }}</span>
                    </td>
                    <td>{{ $aktiviti->nama_aktiviti }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center py-4">
                        Tiada aktiviti untuk program ini.
                        <a href="{{ route('program.edit', $program) }}">Tambah sekarang</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
