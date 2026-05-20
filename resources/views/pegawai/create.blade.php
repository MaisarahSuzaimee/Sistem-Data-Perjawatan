@extends('layouts.app')
@section('title', 'Tambah Pegawai')

{{-- Select2 CSS untuk dropdown yang boleh dicari --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
.select2-container--bootstrap-5 .select2-selection {
    background-color: var(--bs-body-bg) !important;
    border-color: var(--bs-border-color) !important;
    color: var(--bs-body-color) !important;
}
.select2-container--bootstrap-5 .select2-selection__rendered {
    color: var(--bs-body-color) !important;
}
.select2-dropdown {
    background-color: var(--bs-body-bg) !important;
    border-color: var(--bs-border-color) !important;
    color: var(--bs-body-color) !important;
}
.select2-results__option {
    color: var(--bs-body-color) !important;
    background-color: var(--bs-body-bg) !important;
}
.select2-results__option--highlighted,
.select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #696cff !important;
    color: #fff !important;
}
.select2-search__field {
    background-color: var(--bs-body-bg) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--bs-border-color) !important;
}

.form-check-label {
    color: var(--bs-body-color) !important;
}

/* Fix Opsyen Pencen dropdown */
select.form-select option {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
}
/* Dark mode - invert calendar icon to white */
[data-bs-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator,
.dark-style input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
/* Fix date input color in dark mode */
.dark-style input[type="date"] {
    color-scheme: dark;
}
</style>

@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Pegawai</h4>
    <a href="{{ route('pegawai.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Kembali
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('pegawai.store') }}">
    @csrf

    {{-- Maklumat Pegawai --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Pegawai</h5></div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-12">
                    <label class="form-label fs-6">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="nama"
                        class="form-control @error('nama') is-invalid @enderror"
                        value="{{ old('nama') }}">
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">No Kad Pengenalan <span class="text-danger">*</span></label>
                    <input type="text" name="nokp"
                        class="form-control @error('nokp') is-invalid @enderror"
                        value="{{ old('nokp') }}">
                    @error('nokp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Tarikh Lahir</label>
                    <input type="date" name="tarikh_lahir"
                        class="form-control @error('tarikh_lahir') is-invalid @enderror"
                        value="{{ old('tarikh_lahir') }}">
                    @error('tarikh_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">E-mel</label>
                    <input type="email" name="emel"
                        class="form-control @error('emel') is-invalid @enderror"
                        value="{{ old('emel') }}">
                    @error('emel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Jantina <span class="text-danger">*</span></label>
                    <select name="jantina" class="form-select @error('jantina') is-invalid @enderror">
                        <option value="">-- Pilih Jantina --</option>
                        <option value="L" {{ old('jantina') == 'L' ? 'selected' : '' }}>Lelaki</option>
                        <option value="P" {{ old('jantina') == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('jantina')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fs-6">PTJ <span class="text-danger">*</span></label>
                    <select name="ptj_id" class="form-select @error('ptj_id') is-invalid @enderror">
                        <option value="">-- Pilih PTJ --</option>
                        @foreach($ptjs as $ptj)
                        <option value="{{ $ptj->id }}" {{ old('ptj_id') == $ptj->id ? 'selected' : '' }}>{{ $ptj->nama_ptj }}</option>
                        @endforeach
                    </select>
                    @error('ptj_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Bahagian</label>
                    <select name="bahagian_id" class="form-select @error('bahagian_id') is-invalid @enderror">
                        <option value="">-- Pilih Bahagian --</option>
                        @foreach($bahagians as $bahagian)
                        <option value="{{ $bahagian->id }}" {{ old('bahagian_id') == $bahagian->id ? 'selected' : '' }}>{{ $bahagian->nama_bahagian }}</option>
                        @endforeach
                    </select>
                    @error('bahagian_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Unit</label>
                    <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
                        <option value="">-- Pilih Unit --</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->nama_unit }}</option>
                        @endforeach
                    </select>
                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Subunit</label>
                    <select name="subunit_id" class="form-select @error('subunit_id') is-invalid @enderror">
                        <option value="">-- Pilih Subunit --</option>
                        @foreach($subunits as $subunit)
                        <option value="{{ $subunit->id }}" {{ old('subunit_id') == $subunit->id ? 'selected' : '' }}>{{ $subunit->nama_subunit }}</option>
                        @endforeach
                    </select>
                    @error('subunit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Dropdown pilih Jawatan - boleh dicari dengan Select2 --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Jawatan</label>
                    <select id="jawatan_select" class="form-select">
                        <option value="">-- Pilih Jawatan --</option>
                        @foreach($jawatans as $jawatan)
                        <option value="{{ $jawatan->id }}"
                            data-greds="{{ $jawatan->greds->map(fn($g) => ['id' => $g->pivot->id, 'kod' => $g->kod_gred])->toJson() }}"
                            {{ old('jawatan_id') == $jawatan->id ? 'selected' : '' }}>
                            {{ $jawatan->desc_jawatan }} ({{ $jawatan->kod_jawatan }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- {{-- Gred badges - klik untuk pilih berbilang --}}
                <div class="col-12">
                    <label class="form-label fs-6">Gred</label>
                    <div id="gred_badges" class="d-flex flex-wrap gap-2">
                        <span class="text-muted fst-italic">-- Pilih Jawatan dahulu --</span>
                    </div>
                    {{-- Hidden inputs akan dijana secara dinamik --}}
                    <div id="gred_hidden_inputs"></div>
                    @error('jawatan_gred_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div> -->
                {{-- Gred badges - klik untuk pilih --}}
                <div class="col-12">
                    <label class="form-label fs-6">Gred</label>
                    <div id="gred_badges" class="d-flex flex-wrap gap-2">
                        <span class="text-muted fst-italic">-- Pilih Jawatan dahulu --</span>
                    </div>
                    <input type="hidden" name="jawatan_gred_id" id="gred_selected_value">
                    @error('jawatan_gred_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Checkboxes kategori pegawai --}}
                <div class="col-12">
                    <label class="form-label fs-6">Kategori</label>
                    <div class="row g-2">
                        <div class="col-md-3 col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_kontrak" value="1" id="is_kontrak" {{ old('is_kontrak') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kontrak">KONTRAK</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_kup" value="1" id="is_kup" {{ old('is_kup') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kup">KUP</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_kupj" value="1" id="is_kupj" {{ old('is_kupj') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kupj">KUPJ</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_jtw" value="1" id="is_jtw" {{ old('is_jtw') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_jtw">JTW</label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Maklumat Lantikan --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Lantikan</h5></div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fs-6">Tarikh Lantikan</label>
                    <input type="date" name="tarikh_lantikan"
                        class="form-control @error('tarikh_lantikan') is-invalid @enderror"
                        value="{{ old('tarikh_lantikan') }}">
                    @error('tarikh_lantikan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Tarikh Sah Jawatan</label>
                    <input type="date" name="tarikh_sah_jawatan"
                        class="form-control @error('tarikh_sah_jawatan') is-invalid @enderror"
                        value="{{ old('tarikh_sah_jawatan') }}">
                    @error('tarikh_sah_jawatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Opsyen Pencen</label>
                    <select name="opsyen_pencen_id" class="form-select @error('opsyen_pencen_id') is-invalid @enderror">
                        <option value="">-- Pilih Opsyen Pencen --</option>
                        @foreach($opsyen_pencens as $op)
                        <option value="{{ $op->id }}" {{ old('opsyen_pencen_id') == $op->id ? 'selected' : '' }}>{{ $op->nama_opsyen }}</option>
                        @endforeach
                    </select>
                    @error('opsyen_pencen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-6">Tarikh Pencen</label>
                    <input type="date" name="tarikh_pencen"
                        class="form-control @error('tarikh_pencen') is-invalid @enderror"
                        value="{{ old('tarikh_pencen') }}">
                    @error('tarikh_pencen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Simpan
        </button>
        <a href="{{ route('pegawai.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>

</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {

    // Init Select2 pada dropdown Jawatan supaya boleh dicari
    $('#jawatan_select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Pilih Jawatan --',
        allowClear: true
    });

    // Bila jawatan berubah, update badges gred
    $('#jawatan_select').on('change', function() {
        const selectedOption = $(this).find(':selected');
        const gredsData = selectedOption.attr('data-greds');
        const badgesContainer = $('#gred_badges');

        badgesContainer.html('');
        $('#gred_selected_value').val('');

        if (!gredsData) {
            badgesContainer.html('<span class="text-muted fst-italic">-- Pilih Jawatan dahulu --</span>');
            return;
        }

        const greds = JSON.parse(gredsData);

        if (greds.length === 0) {
            badgesContainer.html('<span class="text-muted fst-italic">Tiada gred untuk jawatan ini</span>');
            return;
        }

        // Cipta badge untuk setiap gred - single select
        greds.forEach(function(gred) {
            const badge = $('<span>')
                .addClass('badge bg-label-primary px-3 py-2')
                .css('cursor', 'pointer')
                .attr('data-id', gred.id)
                .text(gred.kod)
                .on('click', function() {
                    // Buang selected dari semua badge
                    badgesContainer.find('.badge').removeClass('bg-primary').addClass('bg-label-primary');
                    // Mark badge ini sebagai selected
                    $(this).removeClass('bg-label-primary').addClass('bg-primary');
                    // Simpan nilai dalam hidden input
                    $('#gred_selected_value').val(gred.id);
                });

            badgesContainer.append(badge);
        });
    });

    // Jika ada old value, trigger change untuk load gred semula
    const oldJawatan = '{{ old("jawatan_id") }}';
    if (oldJawatan) {
        $('#jawatan_select').val(oldJawatan).trigger('change');
        setTimeout(function() {
            const oldGred = '{{ old("jawatan_gred_id") }}';
            if (oldGred) {
                $('#gred_badges [data-id="' + oldGred + '"]').click();
            }
        }, 100);
    }

});
</script>
@endpush