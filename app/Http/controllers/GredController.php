<?php

namespace App\Http\Controllers;

use App\Models\Gred;
use Illuminate\Http\Request;

class GredController extends Controller
{
    public function index()
    {
        $items = Gred::paginate(20);
        return view('gred.index', compact('items'));
    }

    public function create()
    {
        return view('gred.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Gred::create($data);
        return redirect()->route('gred.index')->with('success', 'Gred berjaya ditambah!');
    }

    public function show(Gred $gred)
    {
        return view('gred.show', compact('gred'));
    }

    public function edit(Gred $gred)
    {
        return view('gred.edit', compact('gred'));
    }

    public function update(Request $request, Gred $gred)
    {
        $data = $request->except(['_token', '_method']);
        $gred->update($data);
        return redirect()->route('gred.index')->with('success', 'Gred berjaya dikemaskini!');
    }

    public function destroy(Gred $gred)
    {
        $gred->delete();
        return redirect()->route('gred.index')->with('success', 'Gred berjaya dipadam!');
    }
}
