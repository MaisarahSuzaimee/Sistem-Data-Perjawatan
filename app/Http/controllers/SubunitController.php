<?php

namespace App\Http\Controllers;

use App\Models\Subunit;
use Illuminate\Http\Request;

class SubunitController extends Controller
{
    public function index()
    {
        $items = Subunit::paginate(20);
        return view('subunit.index', compact('items'));
    }

    public function create()
    {
        return view('subunit.create');
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

    public function edit(Subunit $subunit)
    {
        return view('subunit.edit', compact('subunit'));
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
