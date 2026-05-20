<?php

namespace App\Http\Controllers;

use App\Models\Subunit;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Dun;

class SubunitController extends Controller
{
    // public function index()
    // {
    //     $items = Subunit::paginate(20);
    //     return view('subunit.index', compact('items'));
    // }
        public function index(Request $request)
    {
        $search = $request->get('search');
        $items = Subunit::with(['unit', 'dun'])
            ->when($search, function($q) use ($search) {
                $q->where('nama_subunit', 'like', "%$search%")
                ->orWhereHas('unit', fn($q) => $q->where('nama_unit', 'like', "%$search%"));
            })
            ->paginate(20)
            ->withQueryString();

        return view('subunit.index', compact('items', 'search'));
    }

    public function create()
    {
        $units = Unit::all();
        $duns = Dun::all();
        return view('subunit.create', compact('units', 'duns'));
    }

    public function edit(Subunit $subunit)
    {
        $units = Unit::all();
        $duns = Dun::all();
        return view('subunit.edit', compact('subunit', 'units', 'duns'));
    }

    

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Subunit::create($data);
        return redirect()->route('subunit.index')->with('success', 'Subunit berjaya ditambah!');
    }

    public function show(Subunit $subunit)
    {
        return view('subunit.show', compact('subunit'));
    }

    public function update(Request $request, Subunit $subunit)
    {
        $data = $request->except(['_token', '_method']);
        $subunit->update($data);
        return redirect()->route('subunit.index')->with('success', 'Subunit berjaya dikemaskini!');
    }

    public function destroy(Subunit $subunit)
    {
        $subunit->delete();
        return redirect()->route('subunit.index')->with('success', 'Subunit berjaya dipadam!');
    }
}
