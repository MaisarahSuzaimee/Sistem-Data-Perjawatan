<?php

namespace App\Http\Controllers;

use App\Models\Aktiviti;
use Illuminate\Http\Request;

class AktivitiController extends Controller
{
    public function index()
    {
        $items = Aktiviti::paginate(20);
        return view('aktiviti.index', compact('items'));
    }

    public function create()
    {
        return view('aktiviti.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Aktiviti::create($data);
        return redirect()->route('aktiviti.index')->with('success', 'Aktiviti berjaya ditambah!');
    }

    public function show(Aktiviti $aktiviti)
    {
        return view('aktiviti.show', compact('aktiviti'));
    }

    public function edit(Aktiviti $aktiviti)
    {
        return view('aktiviti.edit', compact('aktiviti'));
    }

    public function update(Request $request, Aktiviti $aktiviti)
    {
        $data = $request->except(['_token', '_method']);
        $aktiviti->update($data);
        return redirect()->route('aktiviti.index')->with('success', 'Aktiviti berjaya dikemaskini!');
    }

    public function destroy(Aktiviti $aktiviti)
    {
        $aktiviti->delete();
        return redirect()->route('aktiviti.index')->with('success', 'Aktiviti berjaya dipadam!');
    }
}
