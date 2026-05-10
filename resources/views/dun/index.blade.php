@extends('layouts.app')
@section('title', 'Senarai DUN')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai DUN</h4>
    <a href="{{ route('dun.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Tambah</a>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">DUN</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr><th>#</th><th>Kod DUN</th><th>Nama DUN</th><th>Parlimen</th><th>Tindakan</th></tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->kod_dun ?? '-' }}</td>
                    <td>{{ $item->nama_dun ?? '-' }}</td>
                    <td>{{ $item->parlimen->nama_parlimen ?? '-' }}</td>
                    <td>
                        <a href="{{ route('dun.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning"><i class="bx bx-edit"></i></a>
                        <form action="{{ route('dun.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Padam?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-icon btn-text-danger"><i class="bx bx-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4">Tiada rekod.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
</div>
@endsection
