<?php

namespace App\Http\Controllers;

use App\Models\Butiran;
use Illuminate\Http\Request;

class ButiranController extends Controller
{
    public function index()
    {
        $items = Butiran::paginate(20);
        return view('butiran.index', compact('items'));
    }

    public function create()
    {
        return view('butiran.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Butiran::create($data);
        return redirect()->route('butiran.index')->with('success', 'Butiran berjaya ditambah!');
    }

    public function show(Butiran $butiran)
    {
        return view('butiran.show', compact('butiran'));
    }

    public function edit(Butiran $butiran)
    {
        return view('butiran.edit', compact('butiran'));
    }

    public function update(Request $request, Butiran $butiran)
    {
        $data = $request->except(['_token', '_method']);
        $butiran->update($data);
        return redirect()->route('butiran.index')->with('success', 'Butiran berjaya dikemaskini!');
    }

    public function destroy(Butiran $butiran)
    {
        $butiran->delete();
        return redirect()->route('butiran.index')->with('success', 'Butiran berjaya dipadam!');
    }
}
