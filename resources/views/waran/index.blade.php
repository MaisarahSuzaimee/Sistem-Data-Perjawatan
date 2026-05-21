{{-- ============================================================
     resources/views/waran/index.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'Senarai Waran')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Buku Waran</h4>
        <small class="text-muted">Senarai semua waran jawatan</small>
    </div>
    <a href="{{ route('waran.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Tambah Waran
    </a>
</div>

{{-- Stats Summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="mb-1">
                    <i class="bx bx-file text-primary" style="font-size: 24px;"></i>
                </div>
                <h5 class="fw-bold mb-0">{{ $items->total() }}</h5>
                <small class="text-muted">Jumlah Waran</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="mb-1">
                    <i class="bx bx-trending-up text-success" style="font-size: 24px;"></i>
                </div>
                <h5 class="fw-bold mb-0 text-success">{{ $stats['lebih'] ?? 0 }}</h5>
                <small class="text-muted">Lebih</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="mb-1">
                    <i class="bx bx-trending-down text-danger" style="font-size: 24px;"></i>
                </div>
                <h5 class="fw-bold mb-0 text-danger">{{ $stats['kurang'] ?? 0 }}</h5>
                <small class="text-muted">Kurang</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="mb-1">
                    <i class="bx bx-check-circle text-info" style="font-size: 24px;"></i>
                </div>
                <h5 class="fw-bold mb-0 text-info">{{ $stats['seimbang'] ?? 0 }}</h5>
                <small class="text-muted">Seimbang</small>
            </div>
        </div>
    </div>
</div>

{{-- Main Table Card --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Senarai Waran</h5>

        <div class="d-flex align-items-center gap-2 flex-wrap">

            {{-- Status Filter --}}
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('waran.index', array_merge(request()->query(), ['status' => ''])) }}"
                   class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Semua
                </a>
                <a href="{{ route('waran.index', array_merge(request()->query(), ['status' => 'Lebih'])) }}"
                   class="btn {{ request('status') == 'Lebih' ? 'btn-success' : 'btn-outline-secondary' }}">
                    Lebih
                </a>
                <a href="{{ route('waran.index', array_merge(request()->query(), ['status' => 'Kurang'])) }}"
                   class="btn {{ request('status') == 'Kurang' ? 'btn-danger' : 'btn-outline-secondary' }}">
                    Kurang
                </a>
                <a href="{{ route('waran.index', array_merge(request()->query(), ['status' => 'Seimbang'])) }}"
                   class="btn {{ request('status') == 'Seimbang' ? 'btn-info' : 'btn-outline-secondary' }}">
                    Seimbang
                </a>
            </div>

            {{-- Search --}}
            <form method="GET" action="{{ route('waran.index') }}" id="searchForm">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="input-group input-group-sm" style="width: 240px;">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari no. waran..."
                        value="{{ request('search') }}"
                        oninput="debounceSearch(this.form)">
                    @if(request('search'))
                    <a href="{{ route('waran.index', ['status' => request('status')]) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-x"></i>
                    </a>
                    @endif
                </div>
            </form>

            <small class="text-muted text-nowrap">Jumlah: {{ $items->total() }}</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>No. Waran</th>
                    <th>Status Waran</th>
                    <th>Butiran</th>
                    <th>Aktiviti</th>
                    <th>Penempatan</th>
                    <th class="text-center" title="Jumlah Isi Kandungan">J</th>
                    <th class="text-center" title="Isi">I</th>
                    <th class="text-center" title="Kosong">K</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $waran)
                @php
                    $status = $waran->status_jik;
                    $borderColor = match($status) {
                        'Lebih' => '#198754',
                        'Kurang' => '#dc3545',
                        'Seimbang' => '#0dcaf0',
                        default => '#dee2e6',
                    };
                    $j = (int) $waran->jik;
                    $i = (int) $waran->isi_count;
                    $k = (int) $waran->kosong_count;
                @endphp
                <tr style="border-left: 4px solid {{ $borderColor }};">
                    <td>{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td>
                        <a href="{{ route('waran.show', $waran) }}" class="fw-semibold text-primary text-decoration-none">
                            {{ $waran->no_waran }}
                        </a>
                        @if($waran->catatan)
                            <br><small class="text-muted">{{ Str::limit($waran->catatan, 40) }}</small>
                        @endif
                    </td>
                    <td>
                        @if($waran->jenis === 'tolak')
                            <span class="badge bg-label-warning">Tolak</span>
                        @elseif($waran->jenis === 'tambah')
                            <span class="badge bg-label-success">Tambah</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($waran->butiran_list)
                            <small>{!! $waran->butiran_list !!}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($waran->aktiviti_list)
                            <small>{!! $waran->aktiviti_list !!}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($waran->penempatan_list)
                            <small>{!! $waran->penempatan_list !!}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold">{{ $j }}</span>
                    </td>
                    <td class="text-center">
                        <span class="{{ $i > 0 ? 'text-success fw-semibold' : 'text-muted' }}">{{ $i }}</span>
                    </td>
                    <td class="text-center">
                        <span class="{{ $k < 0 ? 'text-danger fw-semibold' : ($k > 0 ? 'text-warning fw-semibold' : 'text-muted') }}">{{ $k }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $badgeClass = match($status) {
                                'Lebih' => 'bg-label-success',
                                'Kurang' => 'bg-label-danger',
                                'Seimbang' => 'bg-label-info',
                                default => 'bg-label-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <a href="{{ route('waran.show', $waran) }}"
                               class="btn btn-sm btn-icon btn-text-info" title="Lihat">
                                <i class="bx bx-show"></i>
                            </a>
                            <a href="{{ route('waran.edit', $waran) }}"
                               class="btn btn-sm btn-icon btn-text-warning" title="Edit">
                                <i class="bx bx-edit"></i>
                            </a>
                            <button type="button"
                                class="btn btn-sm btn-icon btn-text-danger" title="Padam"
                                onclick="confirmDelete('{{ route('waran.destroy', $waran) }}', 'Waran {{ $waran->no_waran }}')">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-5">
                        <i class="bx bx-file" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">Tiada rekod waran ditemui.</p>
                        <a href="{{ route('waran.create') }}" class="btn btn-primary btn-sm">Tambah Waran</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
    <div class="card-footer">
        {{ $items->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
let searchTimer;
function debounceSearch(form) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => form.submit(), 500);
}
</script>
@endpush