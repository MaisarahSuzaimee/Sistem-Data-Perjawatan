<?php

namespace App\Http\Controllers;

use App\Models\OpsyenPencen;
use Illuminate\Http\Request;

class OpsyenPencenController extends Controller
{
    public function index()
    {
        $items = OpsyenPencen::paginate(20);
        return view('opsyen-pencen.index', compact('items'));
    }

    public function create()
    {
        return view('opsyen-pencen.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        OpsyenPencen::create($data);
        return redirect()->route('opsyen-pencen.index')->with('success', 'OpsyenPencen berjaya ditambah!');
    }

    public function show(OpsyenPencen $opsyen-pencen)
    {
        return view('opsyen-pencen.show', compact('opsyen-pencen'));
    }

    public function edit(OpsyenPencen $opsyen-pencen)
    {
        return view('opsyen-pencen.edit', compact('opsyen-pencen'));
    }

    public function update(Request $request, OpsyenPencen $opsyen-pencen)
    {
        $data = $request->except(['_token', '_method']);
        $opsyen-pencen->update($data);
        return redirect()->route('opsyen-pencen.index')->with('success', 'OpsyenPencen berjaya dikemaskini!');
    }

    public function destroy(OpsyenPencen $opsyen-pencen)
    {
        $opsyen-pencen->delete();
        return redirect()->route('opsyen-pencen.index')->with('success', 'OpsyenPencen berjaya dipadam!');
    }
}
