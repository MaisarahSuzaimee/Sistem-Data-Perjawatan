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
    // Replace your index() method in WaranController with this:

    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        $query = Waran::with(['waranJawatan']);

        // Search by no_waran
        if ($search) {
            $query->where('no_waran', 'like', "%$search%");
        }

        $items = $query->paginate(20)->withQueryString();

        // Filter by status (after paginate to get accurate stats)
        $allWarans = Waran::with(['waranJawatan'])->get();
        $stats = [
            'lebih'    => $allWarans->filter(fn($w) => $w->status_jik === 'Lebih')->count(),
            'kurang'   => $allWarans->filter(fn($w) => $w->status_jik === 'Kurang')->count(),
            'seimbang' => $allWarans->filter(fn($w) => $w->status_jik === 'Seimbang')->count(),
        ];

        // Apply status filter after getting stats
        if ($status) {
            $filteredIds = $allWarans->filter(fn($w) => $w->status_jik === $status)->pluck('id');
            $query2 = Waran::with(['waranJawatan'])->whereIn('id', $filteredIds);
            if ($search) {
                $query2->where('no_waran', 'like', "%$search%");
            }
            $items = $query2->paginate(20)->withQueryString();
        }

        return view('waran.index', compact('items', 'stats', 'search', 'status'));
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