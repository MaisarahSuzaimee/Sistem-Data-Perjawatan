<?php

namespace App\Http\Controllers;

use App\Models\Waran;
use Illuminate\Http\Request;

class WaranController extends Controller
{
    public function index()
    {
        $items = Waran::paginate(20);
        return view('waran.index', compact('items'));
    }

    public function create()
    {
        return view('waran.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Waran::create($data);
        return redirect()->route('waran.index')->with('success', 'Waran berjaya ditambah!');
    }

    public function show(Waran $waran)
    {
        return view('waran.show', compact('waran'));
    }

    public function edit(Waran $waran)
    {
        return view('waran.edit', compact('waran'));
    }

    public function update(Request $request, Waran $waran)
    {
        $data = $request->except(['_token', '_method']);
        $waran->update($data);
        return redirect()->route('waran.index')->with('success', 'Waran berjaya dikemaskini!');
    }

    public function destroy(Waran $waran)
    {
        $waran->delete();
        return redirect()->route('waran.index')->with('success', 'Waran berjaya dipadam!');
    }
}
