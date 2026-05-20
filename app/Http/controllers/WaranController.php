<?php

namespace App\Http\Controllers;

use App\Models\Waran;
use App\Models\Ptj;
use App\Models\Program;
use App\Models\Jawatan_Gred;
use App\Models\Pegawai;
use Illuminate\Http\Request;

class WaranController extends Controller
{
    public function index()
    {
        $items = Waran::with(['waranJawatan'])->paginate(20);
        return view('waran.index', compact('items'));
    }

    public function create()
    {
        $ptjs = Ptj::orderBy('nama_ptj')->get();
        $programs = Program::with('aktiviti')->orderBy('nama_program')->get();
        $jawatanGreds = Jawatan_Gred::with(['jawatan', 'gred'])->get();
        $pegawais = Pegawai::orderBy('nama')->get();
        return view('waran.create', compact('ptjs', 'programs', 'jawatanGreds', 'pegawais'));
    }

    public function store(Request $request)
    {
        $waran = Waran::create([
            'no_waran'   => $request->no_waran,
            'puncakuasa' => $request->puncakuasa,
            'jik'        => $request->jik,
            'catatan'    => $request->catatan,
        ]);

        if ($request->jawatan) {
            foreach ($request->jawatan as $j) {
                $waran->waranJawatan()->create([
                    'ptj_id'         => $j['ptj_id'] ?: null,
                    'aktiviti_id'    => $j['aktiviti_id'] ?: null,
                    'butiran'        => $j['butiran'] ?: null,
                    'jawatan_gred_id'=> $j['jawatan_gred_id'] ?: null,
                    'pegawai_id'     => $j['pegawai_id'] ?: null,
                ]);
            }
        }

        return redirect()->route('waran.index')->with('success', 'Waran berjaya ditambah!');
    }

    public function show(Waran $waran)
    {
        $waran->load(['waranJawatan.ptj', 'waranJawatan.aktiviti', 'waranJawatan.pegawai', 'waranJawatan.jawatanGred.jawatan', 'waranJawatan.jawatanGred.gred']);
        return view('waran.show', compact('waran'));
    }

    public function edit(Waran $waran)
    {
        $waran->load('waranJawatan');
        $ptjs = Ptj::orderBy('nama_ptj')->get();
        $programs = Program::with('aktiviti')->orderBy('nama_program')->get();
        $jawatanGreds = Jawatan_Gred::with(['jawatan', 'gred'])->get();
        $pegawais = Pegawai::orderBy('nama')->get();
        return view('waran.edit', compact('waran', 'ptjs', 'programs', 'jawatanGreds', 'pegawais'));
    }

    public function update(Request $request, Waran $waran)
    {
        $waran->update([
            'no_waran'   => $request->no_waran,
            'puncakuasa' => $request->puncakuasa,
            'jik'        => $request->jik,
            'catatan'    => $request->catatan,
        ]);

        $waran->waranJawatan()->delete();

        if ($request->jawatan) {
            foreach ($request->jawatan as $j) {
                $waran->waranJawatan()->create([
                    'ptj_id'         => $j['ptj_id'] ?: null,
                    'aktiviti_id'    => $j['aktiviti_id'] ?: null,
                    'butiran'        => $j['butiran'] ?: null,
                    'jawatan_gred_id'=> $j['jawatan_gred_id'] ?: null,
                    'pegawai_id'     => $j['pegawai_id'] ?: null,
                ]);
            }
        }

        return redirect()->route('waran.index')->with('success', 'Waran berjaya dikemaskini!');
    }

    public function destroy(Waran $waran)
    {
        $waran->waranJawatan()->delete();
        $waran->delete();
        return redirect()->route('waran.index')->with('success', 'Waran berjaya dipadam!');
    }
}