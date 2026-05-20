<?php

namespace App\Http\Controllers;

use App\Models\Jawatan;
use Illuminate\Http\Request;

class JawatanController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $items = Jawatan::with('greds')
            ->when($search, function($q) use ($search) {
                $q->where('desc_jawatan', 'like', "%$search%")
                // ->orWhere('kod_jawatan', 'like', "%$search%")
                ->orWhere('kod_jawatan', $search)
                ->orWhereHas('greds', fn($q) => $q->where('kod_gred', 'like', "%$search%"));
            })
            ->paginate(20)
            ->withQueryString();

        return view('jawatan-gred.index', compact('items', 'search'));
    }

    public function create()
    {
        return view('jawatan.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Jawatan::create($data);
        return redirect()->route('jawatan.index')->with('success', 'Jawatan berjaya ditambah!');
    }

    public function show(Jawatan $jawatan)
    {
        return view('jawatan.show', compact('jawatan'));
    }

    public function edit(Jawatan $jawatan)
    {
        return view('jawatan.edit', compact('jawatan'));
    }

    public function update(Request $request, Jawatan $jawatan)
    {
        $data = $request->except(['_token', '_method']);
        $jawatan->update($data);
        return redirect()->route('jawatan.index')->with('success', 'Jawatan berjaya dikemaskini!');
    }

    public function destroy(Jawatan $jawatan)
    {
        $jawatan->delete();
        return redirect()->route('jawatan.index')->with('success', 'Jawatan berjaya dipadam!');
    }
}
