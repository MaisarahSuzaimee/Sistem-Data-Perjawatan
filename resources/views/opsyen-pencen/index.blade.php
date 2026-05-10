@extends('layouts.app')
@section('title', 'Senarai Opsyen Pencen')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai Opsyen Pencen</h4>
    <a href="{{ route('opsyen-pencen.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Tambah</a>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Opsyen Pencen</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr><th>#</th><th>Nama Opsyen</th><th>Tindakan</th></tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->nama_opsyen ?? $item->opsyen ?? '-' }}</td>
                    <td>
                        <a href="{{ route('opsyen-pencen.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning"><i class="bx bx-edit"></i></a>
                        <form action="{{ route('opsyen-pencen.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Padam?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-icon btn-text-danger"><i class="bx bx-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-4">Tiada rekod.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
</div>
@endsection
