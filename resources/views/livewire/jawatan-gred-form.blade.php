<?php

use Livewire\Component;
use App\Models\Jawatan_Gred;
use App\Models\Jawatan;
use App\Models\Gred;

new class extends Component
{
    // Field form
    public $jawatan_id = '';
    public $gred_id = '';

    // Untuk mode edit
    public $editId = null;

    // Data untuk dropdown
    public $jawatans = [];
    public $greds = [];

    // Mount: load data dropdown dan data edit jika ada
    public function mount($editId = null)
    {
        $this->jawatans = Jawatan::orderBy('desc_jawatan')->get();
        $this->greds = Gred::orderBy('kod_gred')->get();

        if ($editId) {
            $this->editId = $editId;
            $record = Jawatan_Gred::findOrFail($editId);
            $this->jawatan_id = $record->jawatan_id;
            $this->gred_id = $record->gred_id;
        }
    }

    // Rules validasi
    protected function rules()
    {
        return [
            'jawatan_id' => 'required|exists:jawatans,id',
            'gred_id'    => 'required|exists:greds,id',
        ];
    }

    // Simpan rekod baru
    public function save()
    {
        $this->validate();

        Jawatan_Gred::create([
            'jawatan_id' => $this->jawatan_id,
            'gred_id'    => $this->gred_id,
        ]);

        session()->flash('success', 'Jawatan & Gred berjaya ditambah!');
        return $this->redirect(route('jawatan-gred.index'), navigate: false);
    }

    // Kemaskini rekod sedia ada
    public function update()
    {
        $this->validate();

        Jawatan_Gred::findOrFail($this->editId)->update([
            'jawatan_id' => $this->jawatan_id,
            'gred_id'    => $this->gred_id,
        ]);

        session()->flash('success', 'Jawatan & Gred berjaya dikemaskini!');
        return $this->redirect(route('jawatan-gred.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.jawatan-gred-form');
    }
};
?>

<div>
    {{-- Papar mesej kejayaan --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Papar ralat validasi --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ $editId ? 'Edit' : 'Tambah' }} Jawatan & Gred</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Dropdown pilih Jawatan --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Jawatan <span class="text-danger">*</span></label>
                    <select wire:model="jawatan_id"
                        class="form-select @error('jawatan_id') is-invalid @enderror">
                        <option value="">-- Pilih Jawatan --</option>
                        @foreach($jawatans as $jawatan)
                        <option value="{{ $jawatan->id }}">
                            {{ $jawatan->desc_jawatan }} ({{ $jawatan->kod_jawatan }})
                        </option>
                        @endforeach
                    </select>
                    @error('jawatan_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Dropdown pilih Gred --}}
                <div class="col-md-6">
                    <label class="form-label fs-6">Gred <span class="text-danger">*</span></label>
                    <select wire:model="gred_id"
                        class="form-select @error('gred_id') is-invalid @enderror">
                        <option value="">-- Pilih Gred --</option>
                        @foreach($greds as $gred)
                        <option value="{{ $gred->id }}">{{ $gred->kod_gred }}</option>
                        @endforeach
                    </select>
                    @error('gred_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Butang simpan/kemaskini dan batal --}}
    <div class="d-flex gap-2">
        @if($editId)
            {{-- Mod edit: butang kemaskini --}}
            <button wire:click="update" wire:loading.attr="disabled" class="btn btn-primary">
                <span wire:loading wire:target="update"
                    class="spinner-border spinner-border-sm me-1"></span>
                <i wire:loading.remove wire:target="update" class="bx bx-save me-1"></i>
                Kemaskini
            </button>
        @else
            {{-- Mod tambah: butang simpan --}}
            <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                <span wire:loading wire:target="save"
                    class="spinner-border spinner-border-sm me-1"></span>
                <i wire:loading.remove wire:target="save" class="bx bx-save me-1"></i>
                Simpan
            </button>
        @endif
        <a href="{{ route('jawatan-gred.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div>