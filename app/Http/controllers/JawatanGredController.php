<?php

namespace App\Http\Controllers;

use App\Models\Jawatan_Gred;
use App\Models\Jawatan;
use App\Models\Gred;
use Illuminate\Http\Request;

class JawatanGredController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->get('search');
        $greds = Gred::orderBy('kod_gred')->get(); // tambah ini

        $items = Jawatan::with('greds')
            ->when($search, function($q) use ($search) {
                $q->where('desc_jawatan', 'like', "%$search%")
                ->orWhere('kod_jawatan', $search)
                ->orWhereHas('greds', fn($q) => $q->where('kod_gred', 'like', "%$search%"));
            })
            ->paginate(20)
            ->withQueryString();

        return view('jawatan-gred.index', compact('items', 'search', 'greds')); // tambah greds
    }

    public function create()
    {
        return view('jawatan-gred.create');
    }

    public function edit(string $id)
    {
        $jawatanGred = Jawatan::with(['jawatan', 'gred'])->findOrFail($id);
        return view('jawatan.edit', compact('jawatanGred'));
    }

    // public function destroy(string $id)
    // {
    //     Jawatan_Gred::findOrFail($id)->delete();
    //     return redirect()->route('jawatan-gred.index')->with('success', 'Rekod berjaya dipadam!');
    // }

        public function destroy(string $id)
    {
        $jawatan = Jawatan::findOrFail($id);
        $jawatan->greds()->detach(); // remove from pivot table
        $jawatan->delete();
        return redirect()->route('jawatan-gred.index')->with('success', 'Rekod berjaya dipadam!');
    }

        public function store(Request $request)
    {
        $request->validate([
            'kod_jawatan' => 'required|string|max:10',
            'desc_jawatan' => 'required|string|max:255',
        ]);

        $jawatan = Jawatan::create([
            'kod_jawatan' => strtoupper($request->kod_jawatan),
            'desc_jawatan' => strtoupper($request->desc_jawatan),
            'skim' => $request->skim, // tambah ini
        ]);

        if ($request->gred_ids) {
            $jawatan->greds()->sync($request->gred_ids);
        }

        return redirect()->route('jawatan-gred.index')->with('success', 'Jawatan & Gred berjaya ditambah!');
    }
    public function update(Request $request, string $id) {}
    public function show(string $id) {}
}