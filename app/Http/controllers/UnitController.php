<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $items = Unit::paginate(20);
        return view('unit.index', compact('items'));
    }

    public function create()
    {
        return view('unit.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Unit::create($data);
        return redirect()->route('unit.index')->with('success', 'Unit berjaya ditambah!');
    }

    public function show(Unit $unit)
    {
        return view('unit.show', compact('unit'));
    }

    public function edit(Unit $unit)
    {
        return view('unit.edit', compact('unit'));
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
}
