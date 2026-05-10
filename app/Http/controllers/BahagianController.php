<?php

namespace App\Http\Controllers;

use App\Models\Bahagian;
use Illuminate\Http\Request;

class BahagianController extends Controller
{
    public function index()
    {
        $items = Bahagian::paginate(20);
        return view('bahagian.index', compact('items'));
    }

    public function create()
    {
        return view('bahagian.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Bahagian::create($data);
        return redirect()->route('bahagian.index')->with('success', 'Bahagian berjaya ditambah!');
    }

    public function show(Bahagian $bahagian)
    {
        return view('bahagian.show', compact('bahagian'));
    }

    public function edit(Bahagian $bahagian)
    {
        return view('bahagian.edit', compact('bahagian'));
    }

    public function update(Request $request, Bahagian $bahagian)
    {
        $data = $request->except(['_token', '_method']);
        $bahagian->update($data);
        return redirect()->route('bahagian.index')->with('success', 'Bahagian berjaya dikemaskini!');
    }

    public function destroy(Bahagian $bahagian)
    {
        $bahagian->delete();
        return redirect()->route('bahagian.index')->with('success', 'Bahagian berjaya dipadam!');
    }
}
