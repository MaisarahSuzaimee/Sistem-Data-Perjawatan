@extends('layouts.app')
@section('title', 'Edit Waran')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    #jawatanRows .form-select,
    #jawatanRows .form-control {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #jawatanRows td {
        padding: 6px 4px;
    }
    .table thead th {
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }
    .select2-container {
        width: 100% !important;
    }
        .select2-container--bootstrap-5 .select2-selection {
        font-size: 0.8rem;
    }
    .select2-container--bootstrap-5 .select2-selection {
    font-size: 0.8rem;
    }

    .select2-container--bootstrap-5 .select2-selection__rendered {
        font-size: 0.8rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding-right: 40px !important;
    }
    .select2-container--bootstrap-5 .select2-selection {
    font-size: 0.8rem;
    background-color: transparent !important;
    border-color: #444 !important;
    color: inherit !important;
}

.select2-container--bootstrap-5 .select2-selection__rendered {
    font-size: 0.8rem;
    overflow: hidden;
    text-overflow: ellipsis;
    /* white-space: nowrap; */
    padding-right: 40px !important;
    color: inherit !important;
}

.select2-dropdown {
    background-color: var(--bs-body-bg) !important;
    border-color: #444 !important;
    color: var(--bs-body-color) !important;
}

.select2-results__option {
    color: var(--bs-body-color) !important;
}

.select2-results__option--highlighted {
    background-color: var(--bs-primary) !important;
}

.select2-search__field {
    background-color: var(--bs-body-bg) !important;
    color: var(--bs-body-color) !important;
    border-color: #444 !important;
}
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Waran</h4>
    <a href="{{ route('waran.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('waran.update', $waran) }}" id="waranForm">
    @csrf
    @method('PUT')

    {{-- Maklumat Waran --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Waran</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fs-6">No Waran</label>
                    <input type="text" name="no_waran"
                        class="form-control @error('no_waran') is-invalid @enderror"
                        value="{{ old('no_waran', $waran->no_waran) }}">
                    @error('no_waran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-6">Jumlah Jawatan (JIK)</label>
                    <input type="number" name="jik" id="jikInput"
                        class="form-control @error('jik') is-invalid @enderror"
                        value="{{ old('jik', $waran->jik) }}" min="1" max="100">
                    @error('jik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-6">Punca Kuasa</label>
                    <input type="text" name="puncakuasa"
                        class="form-control @error('puncakuasa') is-invalid @enderror"
                        value="{{ old('puncakuasa', $waran->puncakuasa) }}">
                    @error('puncakuasa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-6">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan', $waran->catatan) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Maklumat Jawatan --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Maklumat Jawatan</h5>
            <span class="badge bg-primary" id="jawatanCount">{{ $waran->waranJawatan->count() }} Jawatan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="table-layout: fixed;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px">Bil</th>
                            <th class="text-center" style="width: 20%">PTJ</th>
                            <th class="text-center" style="width: 30%">Aktiviti</th>
                            <th class="text-center" style="width: 8%">Butiran</th>
                            <th class="text-center" style="width: 18%">Jawatan & Gred</th>
                            <th class="text-center" style="width: 18%">Pegawai</th>
                        </tr>
                    </thead>
                    <tbody id="jawatanRows">
                        @foreach($waran->waranJawatan as $i => $jaw)
                        <tr class="jawatan-row" data-index="{{ $i }}">
                            <td class="text-center fw-bold align-middle">{{ $i + 1 }}</td>
                            <td class="align-middle">
                                <select name="jawatan[{{ $i }}][ptj_id]" class="form-select jawatan-select">
                                    <option value="">-- Pilih PTJ --</option>
                                    @foreach($ptjs as $ptj)
                                    <option value="{{ $ptj->id }}" {{ $jaw->ptj_id == $ptj->id ? 'selected' : '' }}>{{ $ptj->nama_ptj }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="align-middle">
                                <select name="jawatan[{{ $i }}][aktiviti_id]" class="form-select jawatan-select">
                                    <option value="">-- Pilih Aktiviti --</option>
                                    @foreach($programs as $program)
                                    <optgroup label="{{ $program->nama_program }} - {{ $program->desc_program }}">
                                        @foreach($program->aktiviti as $aktiviti)
                                        <option value="{{ $aktiviti->id }}" {{ $jaw->aktiviti_id == $aktiviti->id ? 'selected' : '' }}>{{ $aktiviti->no_aktivit }} - {{ $aktiviti->nama_aktiviti }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </td>
                            <td class="align-middle text-center">
                                <input type="text" name="jawatan[{{ $i }}][butiran]" class="form-control" placeholder="No. Butiran" value="{{ $jaw->butiran }}">
                            </td>
                            <td class="align-middle">
                                <select name="jawatan[{{ $i }}][jawatan_gred_id]" class="form-select jawatan-select">
                                    <option value="">-- Pilih Jawatan --</option>
                                    @foreach($jawatanGreds as $jg)
                                    <option value="{{ $jg->id }}" {{ $jaw->jawatan_gred_id == $jg->id ? 'selected' : '' }}>{{ $jg->jawatan->desc_jawatan ?? '-' }} ({{ $jg->gred->kod_gred ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="align-middle">
                                <select name="jawatan[{{ $i }}][pegawai_id]" class="form-select jawatan-select">
                                    <option value="">-- Pilih Pegawai --</option>
                                    @foreach($pegawais as $pegawai)
                                    <option value="{{ $pegawai->id }}" {{ $jaw->pegawai_id == $pegawai->id ? 'selected' : '' }}>{{ $pegawai->nama }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Kemaskini
        </button>
        <a href="{{ route('waran.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function initSelect2() {
    $('#jawatanRows .jawatan-select').each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih --',
                allowClear: true
            });
        }
    });
}

initSelect2();

const existingCount = {{ $waran->waranJawatan->count() }};

function getRowTemplate(index) {
    return `
        <tr class="jawatan-row" data-index="${index}">
            <td class="text-center fw-bold align-middle">${index + 1}</td>
            <td class="align-middle">
                <select name="jawatan[${index}][ptj_id]" class="form-select jawatan-select">
                    <option value="">-- Pilih PTJ --</option>
                    @foreach($ptjs as $ptj)
                    <option value="{{ $ptj->id }}">{{ $ptj->nama_ptj }}</option>
                    @endforeach
                </select>
            </td>
            <td class="align-middle">
                <select name="jawatan[${index}][aktiviti_id]" class="form-select jawatan-select">
                    <option value="">-- Pilih Aktiviti --</option>
                    @foreach($programs as $program)
                    <optgroup label="{{ $program->nama_program }} - {{ $program->desc_program }}">
                        @foreach($program->aktiviti as $aktiviti)
                        <option value="{{ $aktiviti->id }}">{{ $aktiviti->no_aktivit }} - {{ $aktiviti->nama_aktiviti }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </td>
            <td class="align-middle text-center">
                <input type="text" name="jawatan[${index}][butiran]" class="form-control" placeholder="No. Butiran">
            </td>
            <td class="align-middle">
                <select name="jawatan[${index}][jawatan_gred_id]" class="form-select jawatan-select">
                    <option value="">-- Pilih Jawatan --</option>
                    @foreach($jawatanGreds as $jg)
                    <option value="{{ $jg->id }}">{{ $jg->jawatan->desc_jawatan ?? '-' }} ({{ $jg->gred->kod_gred ?? '-' }})</option>
                    @endforeach
                </select>
            </td>
            <td class="align-middle">
                <select name="jawatan[${index}][pegawai_id]" class="form-select jawatan-select">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach($pegawais as $pegawai)
                    <option value="{{ $pegawai->id }}">{{ $pegawai->nama }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
    `;
}

document.getElementById('jikInput').addEventListener('change', function () {
    const jik = parseInt(this.value) || 1;
    const tbody = document.getElementById('jawatanRows');
    const currentRows = tbody.querySelectorAll('.jawatan-row').length;

    if (jik > currentRows) {
        for (let i = currentRows; i < jik; i++) {
            tbody.insertAdjacentHTML('beforeend', getRowTemplate(i));
        }
        initSelect2();
    } else if (jik < currentRows) {
        const rows = tbody.querySelectorAll('.jawatan-row');
        for (let i = currentRows - 1; i >= jik; i--) {
            rows[i].remove();
        }
    }

    document.getElementById('jawatanCount').textContent = jik + ' Jawatan';
});
</script>
@endpush