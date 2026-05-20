<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\Bahagian;
use App\Models\Unit;
use App\Models\Subunit;
use App\Models\Jawatan_Gred;
use App\Models\Jawatan;
use App\Models\OpsyenPencen;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index()
    {
        $items = Pegawai::with(['ptj', 'bahagian', 'unit', 'jawatan_gred'])->paginate(20);
        return view('pegawai.index', compact('items'));
    }

    public function create()
    {
        $ptjs = Ptj::all();
        $bahagians = Bahagian::all();
        $units = Unit::all();
        $subunits = Subunit::all();
        // $jawatan_greds = Jawatan_Gred::with(['jawatan', 'gred'])->get();
        $jawatans = Jawatan::with('greds')
                            ->orderBy('desc_jawatan')
                            ->get();
        $opsyen_pencens = OpsyenPencen::all();
        return view('pegawai.create', compact('ptjs','bahagians','units','subunits','jawatans','opsyen_pencens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nokp' => 'required|string|max:20',
            'jantina' => 'required|in:L,P',
            'ptj_id' => 'required|exists:ptjs,id',
        ]);
        Pegawai::create($request->all());
        return redirect()->route('pegawai.index')->with('success', 'Pegawai berjaya ditambah!');
    }

    public function show(Pegawai $pegawai)
    {
        $pegawai->load(['ptj', 'bahagian', 'unit', 'subunit', 'jawatan_gred']);
        return view('pegawai.show', compact('pegawai'));
    }

    public function edit(Pegawai $pegawai)
    {
        $ptjs = Ptj::all();
        $bahagians = Bahagian::all();
        $units = Unit::all();
        $subunits = Subunit::all();
        // $jawatan_greds = Jawatan_Gred::with(['jawatan', 'gred'])->get();
        $jawatans = Jawatan::with('greds')->orderBy('desc_jawatan')->get();
        $opsyen_pencens = OpsyenPencen::all();
        return view('pegawai.edit', compact('pegawai','ptjs','bahagians','units','subunits','jawatans','opsyen_pencens'));
    }

    public function update(Request $request, Pegawai $pegawai)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nokp' => 'required|string|max:20',
            'jantina' => 'required|in:L,P',
            'ptj_id' => 'required|exists:ptjs,id',
        ]);
        $pegawai->update($request->all());
        return redirect()->route('pegawai.index')->with('success', 'Maklumat pegawai berjaya dikemaskini!');
    }

    public function destroy(Pegawai $pegawai)
    {
        $pegawai->delete();
        return redirect()->route('pegawai.index')->with('success', 'Pegawai berjaya dipadam!');
    }
}
