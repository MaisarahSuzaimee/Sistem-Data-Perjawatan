<?php

namespace App\Http\Controllers;

use App\Models\Ptj;
use Illuminate\Http\Request;

class PtjController extends Controller
{
    // public function index()
    // {
    //     $items = Ptj::paginate(20);
    //     return view('ptj.index', compact('items'));
    // }

    public function create()
    {
        return view('ptj.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Ptj::create($data);
        return redirect()->route('ptj.index')->with('success', 'Ptj berjaya ditambah!');
    }

    public function show(Ptj $ptj)
    {
        return view('ptj.show', compact('ptj'));
    }

    public function edit(Ptj $ptj)
    {
        return view('ptj.edit', compact('ptj'));
    }

    public function update(Request $request, Ptj $ptj)
    {
        $data = $request->except(['_token', '_method']);
        $ptj->update($data);
        return redirect()->route('ptj.index')->with('success', 'Ptj berjaya dikemaskini!');
    }

    public function destroy(Ptj $ptj)
    {
        $ptj->delete();
        return redirect()->route('ptj.index')->with('success', 'Ptj berjaya dipadam!');
    }
        public function index(Request $request)
    {
        $search = $request->get('search');
        $items = Ptj::when($search, function($q) use ($search) {
                $q->where('nama_ptj', 'like', "%$search%")
                ->orWhere('kod_ptj', 'like', "%$search%")
                ->orWhere('pengarah', 'like', "%$search%");
            })
            ->paginate(20)
            ->withQueryString();

        return view('ptj.index', compact('items', 'search'));
    }
}
