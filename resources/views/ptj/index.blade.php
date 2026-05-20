@extends('layouts.app')
@section('title', 'Senarai PTJ')

{{-- ============================================================
     CSS: SweetAlert2 custom size untuk popup padam yang lebih kecil
     ============================================================ --}}
@push('styles')
<style>
.swal-small {
    font-size: 0.85rem !important;
    padding: 1rem !important;
}
.swal-small .swal2-title {
    font-size: 1.1rem !important;
}
.swal-small .swal2-icon {
    width: 3rem !important;
    height: 3rem !important;
    margin: 0.5rem auto !important;
}
.swal-small .swal2-icon .swal2-icon-content {
    font-size: 1.5rem !important;
}
</style>
@endpush

@section('content')

{{-- Header: Tajuk halaman + butang tambah PTJ baru --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Senarai PTJ</h4>
    <a href="{{ route('ptj.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah
    </a>
</div>

{{-- Kad utama yang mengandungi jadual senarai PTJ --}}
<div class="card">

    {{-- Header kad: tajuk seksyen + jumlah rekod --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">PTJ</h5>
        <small class="text-muted">Jumlah: {{ $items->total() }}</small>
    </div>

    {{-- Jadual senarai PTJ --}}
    <div class="table-responsive">
        <table class="table table-hover">

            {{-- Kepala jadual --}}
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kod PTJ</th>
                    <th>Nama PTJ</th>
                    <th>Tindakan</th>
                </tr>
            </thead>

            <tbody>
                {{-- Loop semua rekod PTJ, jika tiada rekod tunjuk mesej kosong --}}
                @forelse($items as $item)
                <tr>
                    {{-- Nombor baris mengambil kira pagination --}}
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>{{ $item->kod_ptj ?? '-' }}</td>
                    <td>{{ $item->nama_ptj ?? '-' }}</td>

                    {{-- Butang tindakan: Edit & Padam --}}
                    <td>
                        {{-- Butang edit: redirect ke halaman edit PTJ --}}
                        <a href="{{ route('ptj.edit', $item) }}" class="btn btn-sm btn-icon btn-text-warning">
                            <i class="bx bx-edit"></i>
                        </a>

                        {{-- Butang padam: trigger SweetAlert2 confirmation --}}
                        <button type="button" class="btn btn-sm btn-icon btn-text-danger"
                            onclick="confirmDelete('{{ route('ptj.destroy', $item) }}')">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                {{-- Papar mesej jika tiada rekod dalam database --}}
                <tr><td colspan="4" class="text-center py-4">Tiada rekod.</td></tr>
                @endforelse
            </tbody>

        </table>
    </div>

    {{-- Footer kad: pautan pagination --}}
    <div class="card-footer">{{ $items->links() }}</div>

</div>
@endsection

{{-- ============================================================
     JS: Fungsi confirmDelete menggunakan SweetAlert2
     Dipanggil apabila butang padam ditekan
     ============================================================ --}}
@push('scripts')
<script>
function confirmDelete(url) {
    Swal.fire({
        title: 'Padam PTJ?',
        text: 'Tindakan ini tidak boleh dibatalkan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',       // Merah untuk butang confirm
        cancelButtonColor: '#6c757d',     // Kelabu untuk butang batal
        confirmButtonText: 'Ya, Padam!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'swal-small'           // Guna class CSS kecil yang didefinisikan di atas
        },
        width: '320px',                   // Lebar popup
    }).then((result) => {
        // Jika pengguna tekan "Ya, Padam!" — buat form DELETE dan submit
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            // Laravel perlukan _token (CSRF) dan _method (DELETE) untuk delete request
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="DELETE">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush