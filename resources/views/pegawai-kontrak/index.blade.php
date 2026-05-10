@extends('layouts.app')
@section('title', 'Senarai Pegawai Kontrak')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai Pegawai Kontrak</h4>
    <a href="{{ route('pegawai-kontrak.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Tambah</a>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pegawai Kontrak</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr><th>#</th><th>Nama</th><th>No. K/P</th><th>Tarikh Mula</th><th>Tarikh Tamat</th><th>Tindakan</th></tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->pegawai->nama ?? '-' }}</td>
                    <td>{{ $item->pegawai->nokp ?? '-' }}</td>
                    <td>{{ $item->tarikh_mula ?? '-' }}</td>
                    <td>{{ $item->tarikh_tamat ?? '-' }}</td>
                    <td>
                        <a href="{{ route('pegawai-kontrak.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning"><i class="bx bx-edit"></i></a>
                        <form action="{{ route('pegawai-kontrak.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Padam?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-icon btn-text-danger"><i class="bx bx-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4">Tiada rekod.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
</div>
@endsection
