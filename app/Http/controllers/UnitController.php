<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Bahagian;
use Illuminate\Http\Request;

class UnitController extends Controller
{
        public function index(Request $request)
    {
        $search = $request->get('search');
        $items = Unit::with('bahagian')
            ->when($search, function($q) use ($search) {
                $q->where('nama_unit', 'like', "%$search%")
                ->orWhereHas('bahagian', fn($q) => $q->where('nama_bahagian', 'like', "%$search%"));
            })
            ->paginate(20)
            ->withQueryString();

        return view('unit.index', compact('items', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Unit::create($data);
        return redirect()->route('unit.index')->with('success', 'Unit berjaya ditambah!');

        $search = $request->get('search');
        $items = Unit::with('bahagian')
            ->when($search, function($q) use ($search) {
                $q->where('nama_unit', 'like', "%$search%")
                ->orWhereHas('bahagian', fn($q) => $q->where('nama_bahagian', 'like', "%$search%"));
            })
            ->paginate(20)
            ->withQueryString();

        return view('unit.index', compact('items', 'search'));
    }

    public function show(Unit $unit)
    {
        return view('unit.show', compact('unit'));
    }

    // public function edit(Unit $unit)
    // {
    //     return view('unit.edit', compact('unit'));
    // }
    public function edit(Unit $unit)
    {
        $bahagians = Bahagian::all();
        return view('unit.edit', compact('unit', 'bahagians'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->except(['_token', '_method']);
        $unit->update($data);
        return redirect()->route('unit.index')->with('success', 'Unit berjaya dikemaskini!');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->route('unit.index')->with('success', 'Unit berjaya dipadam!');
    }
    

    public function create()
    {
        $bahagians = Bahagian::all();
        return view('unit.create', compact('bahagians'));
    }

    
}
