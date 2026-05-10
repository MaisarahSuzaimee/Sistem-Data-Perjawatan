<?php

namespace App\Http\Controllers;

use App\Models\Dun;
use Illuminate\Http\Request;

class DunController extends Controller
{
    public function index()
    {
        $items = Dun::paginate(20);
        return view('dun.index', compact('items'));
    }

    public function create()
    {
        return view('dun.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Dun::create($data);
        return redirect()->route('dun.index')->with('success', 'Dun berjaya ditambah!');
    }

    public function show(Dun $dun)
    {
        return view('dun.show', compact('dun'));
    }

    public function edit(Dun $dun)
    {
        return view('dun.edit', compact('dun'));
    }

    public function update(Request $request, Dun $dun)
    {
        $data = $request->except(['_token', '_method']);
        $dun->update($data);
        return redirect()->route('dun.index')->with('success', 'Dun berjaya dikemaskini!');
    }

    public function destroy(Dun $dun)
    {
        $dun->delete();
        return redirect()->route('dun.index')->with('success', 'Dun berjaya dipadam!');
    }
}
