<?php

namespace App\Http\Controllers;

use App\Models\Parlimen;
use Illuminate\Http\Request;

class ParlimenController extends Controller
{
    public function index()
    {
        $items = Parlimen::paginate(20);
        return view('parlimen.index', compact('items'));
    }

    public function create()
    {
        return view('parlimen.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Parlimen::create($data);
        return redirect()->route('parlimen.index')->with('success', 'Parlimen berjaya ditambah!');
    }

    public function show(Parlimen $parlimen)
    {
        return view('parlimen.show', compact('parlimen'));
    }

    public function edit(Parlimen $parlimen)
    {
        return view('parlimen.edit', compact('parlimen'));
    }

    public function update(Request $request, Parlimen $parlimen)
    {
        $data = $request->except(['_token', '_method']);
        $parlimen->update($data);
        return redirect()->route('parlimen.index')->with('success', 'Parlimen berjaya dikemaskini!');
    }

    public function destroy(Parlimen $parlimen)
    {
        $parlimen->delete();
        return redirect()->route('parlimen.index')->with('success', 'Parlimen berjaya dipadam!');
    }
}
