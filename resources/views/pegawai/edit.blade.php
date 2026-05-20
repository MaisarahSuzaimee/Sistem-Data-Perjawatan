@extends('layouts.app')
@section('title', 'Edit Pegawai')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Edit Pegawai</h4>
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

<form method="POST" action="{{ route('pegawai.update', $pegawai) }}">
    @csrf @method('PUT')

    {{-- Maklumat Pegawai --}}
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Maklumat Pegawai</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                        value="{{ old('nama', $pegawai->nama) }}" required>
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">No. Kad Pengenalan <span class="text-danger">*</span></label>
                    <input type="text" name="nokp" id="nokp" class="form-control @error('nokp') is-invalid @enderror"
                        value="{{ old('nokp', $pegawai->nokp) }}" required>
                    @error('nokp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tarikh Lahir</label>
                    <input type="date" name="tarikh_lahir" id="tarikh_lahir" class="form-control"
                        value="{{ old('tarikh_lahir', $pegawai->tarikh_lahir) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">E-mel</label>
                    <input type="email" name="emel" class="form-control"
                        value="{{ old('emel', $pegawai->emel) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Jantina <span class="text-danger">*</span></label>
                    <select name="jantina" class="form-select @error('jantina') is-invalid @enderror" required>
                        <option value="">-- Pilih --</option>
                        <option value="lelaki" {{ old('jantina', $pegawai->jantina) == 'lelaki' ? 'selected' : '' }}>Lelaki</option>
                        <option value="perempuan" {{ old('jantina', $pegawai->jantina) == 'perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('jantina')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">PTJ <span class="text-danger">*</span></label>
                    <select name="ptj_id" id="ptj_id" class="form-select @error('ptj_id') is-invalid @enderror" required>
                        <option value="">-- Pilih PTJ --</option>
                        @foreach($ptjs as $ptj)
                            <option value="{{ $ptj->id }}" {{ old('ptj_id', $pegawai->ptj_id) == $ptj->id ? 'selected' : '' }}>
                                {{ $ptj->nama_ptj }}
                            </option>
                        @endforeach
                    </select>
                    @error('ptj_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Bahagian</label>
                    <select name="bahagian_id" id="bahagian_id" class="form-select">
                        <option value="">-- Pilih Bahagian --</option>
                        @foreach($bahagians as $bahagian)
                            <option value="{{ $bahagian->id }}" {{ old('bahagian_id', $pegawai->bahagian_id) == $bahagian->id ? 'selected' : '' }}>
                                {{ $bahagian->nama_bahagian }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" id="unit_id" class="form-select">
                        <option value="">-- Pilih Unit --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $pegawai->unit_id) == $unit->id ? 'selected' : '' }}>
                                {{ $unit->nama_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subunit</label>
                    <select name="subunit_id" class="form-select">
                        <option value="">-- Pilih Subunit --</option>
                        @foreach($subunits as $subunit)
                            <option value="{{ $subunit->id }}" {{ old('subunit_id', $pegawai->subunit_id) == $subunit->id ? 'selected' : '' }}>
                                {{ $subunit->nama_subunit }}
                            </option>
                        @endforeach
                    </select>
                </div>


                {{-- Dropdown pilih Jawatan - boleh dicari dengan Select2 --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Jawatan</label>
                    <select id="jawatan_select" class="form-select">
                        <option value="">-- Pilih Jawatan --</option>
                        @foreach($jawatans as $jawatan)
                        <option value="{{ $jawatan->id }}"
                            data-greds="{{ $jawatan->greds->map(fn($g) => ['id' => $g->pivot->id, 'kod' => $g->kod_gred])->toJson() }}"
                            {{-- Semak jika jawatan ini adalah jawatan pegawai semasa --}}
                            {{ old('jawatan_id', $pegawai->jawatan_gred->jawatan_id ?? '') == $jawatan->id ? 'selected' : '' }}>
                            {{ $jawatan->desc_jawatan }} ({{ $jawatan->kod_jawatan }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Gred badges - klik untuk pilih --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Gred</label>
                    <div id="gred_badges" class="d-flex flex-wrap gap-2">
                        <span class="text-muted fst-italic">-- Pilih Jawatan dahulu --</span>
                    </div>
                    <input type="hidden" name="jawatan_gred_id" id="gred_selected_value"
                        value="{{ old('jawatan_gred_id', $pegawai->jawatan_gred_id) }}">
                    @error('jawatan_gred_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="row">
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_kontrak" id="is_kontrak" value="1"
                                    {{ old('is_kontrak', $pegawai->is_kontrak) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kontrak">KONTRAK</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_kup" id="is_kup" value="1"
                                    {{ old('is_kup', $pegawai->is_kup) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kup">KUP</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_kupj" id="is_kupj" value="1"
                                    {{ old('is_kupj', $pegawai->is_kupj) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kupj">KUPJ</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_jtw" id="is_jtw" value="1"
                                    {{ old('is_jtw', $pegawai->is_jtw) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_jtw">JTW</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Maklumat Lantikan (Non-Kontrak) --}}
    <div class="card mb-4" id="section_lantikan">
        <div class="card-header"><h5 class="mb-0">Maklumat Lantikan</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tarikh Lantikan</label>
                    <input type="date" name="tarikh_lantikan" class="form-control"
                        value="{{ old('tarikh_lantikan', $pegawai->tarikh_lantikan) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tarikh Sah Jawatan</label>
                    <input type="date" name="tarikh_sah_jawatan" class="form-control"
                        value="{{ old('tarikh_sah_jawatan', $pegawai->tarikh_sah_jawatan) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opsyen Pencen</label>
                    <select name="opsyen_pencen_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach($opsyen_pencens as $op)
                            <option value="{{ $op->id }}" {{ old('opsyen_pencen_id', $pegawai->opsyen_pencen_id) == $op->id ? 'selected' : '' }}>
                                {{ $op->opsyen }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tarikh Pencen</label>
                    <input type="date" name="tarikh_pencen" class="form-control"
                        value="{{ old('tarikh_pencen', $pegawai->tarikh_pencen) }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Maklumat Lantikan Kontrak --}}
    <div class="card mb-4" id="section_kontrak" style="display:none;">
        <div class="card-header"><h5 class="mb-0">Maklumat Lantikan Kontrak</h5></div>
        <div class="card-body">
            <div class="row g-3">
                @for($i = 1; $i <= 5; $i++)
                <div class="col-md-6">
                    <label class="form-label">Tarikh Lantikan {{ $i }}</label>
                    <input type="date" name="tarikh_lantikan{{ $i }}" class="form-control"
                        value="{{ old('tarikh_lantikan'.$i, $pegawai->kontrak->{'tarikh_lantikan'.$i} ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tarikh Tamat {{ $i }}</label>
                    <input type="date" name="tarikh_tamat{{ $i }}" class="form-control"
                        value="{{ old('tarikh_tamat'.$i, $pegawai->kontrak->{'tarikh_tamat'.$i} ?? '') }}">
                </div>
                @endfor
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
<script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    // Auto-fill tarikh_lahir from nokp
    document.getElementById('nokp').addEventListener('input', function() {
        const nokp = this.value.replace(/-/g, '');
        if (nokp.length >= 6) {
            const year = nokp.substring(0, 2);
            const month = nokp.substring(2, 4);
            const day = nokp.substring(4, 6);
            const fullYear = parseInt(year) > parseInt(new Date().getFullYear().toString().substring(2))
                ? '19' + year : '20' + year;
            document.getElementById('tarikh_lahir').value = fullYear + '-' + month + '-' + day;
        }
    });

    // Toggle kontrak/lantikan sections
    const kontrakCheck = document.getElementById('is_kontrak');
    const sectionLantikan = document.getElementById('section_lantikan');
    const sectionKontrak = document.getElementById('section_kontrak');

    function toggleSections() {
        if (kontrakCheck.checked) {
            sectionLantikan.style.display = 'none';
            sectionKontrak.style.display = 'block';
        } else {
            sectionLantikan.style.display = 'block';
            sectionKontrak.style.display = 'none';
        }
    }

    kontrakCheck.addEventListener('change', toggleSections);
    toggleSections(); // run on load

    // Select2 JS
    $.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function() {

        $('#jawatan_select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Pilih Jawatan --',
            allowClear: true
        });

        $('#jawatan_select').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const gredsData = selectedOption.attr('data-greds');
            const badgesContainer = $('#gred_badges');

            badgesContainer.html('');

            if (!gredsData) {
                badgesContainer.html('<span class="text-muted fst-italic">-- Pilih Jawatan dahulu --</span>');
                return;
            }

            const currentGredId = $('#gred_selected_value').val();
            const greds = JSON.parse(gredsData);

            if (greds.length === 0) {
                badgesContainer.html('<span class="text-muted fst-italic">Tiada gred untuk jawatan ini</span>');
                return;
            }

            greds.forEach(function(gred) {
                const isSelected = currentGredId == gred.id;
                const badge = $('<span>')
                    .addClass('badge px-3 py-2 ' + (isSelected ? 'bg-primary' : 'bg-label-primary'))
                    .css('cursor', 'pointer')
                    .attr('data-id', gred.id)
                    .text(gred.kod)
                    .on('click', function() {
                        badgesContainer.find('.badge').removeClass('bg-primary').addClass('bg-label-primary');
                        $(this).removeClass('bg-label-primary').addClass('bg-primary');
                        $('#gred_selected_value').val(gred.id);
                    });

                badgesContainer.append(badge);
            });
        });

        // Trigger on load to show existing jawatan gred
        const existingJawatan = '{{ $pegawai->jawatan_gred->jawatan_id ?? "" }}';
        if (existingJawatan) {
            $('#jawatan_select').val(existingJawatan).trigger('change');
        }
    });
</script>
@endpush
