<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $items = Program::paginate(20);
        return view('program.index', compact('items'));
    }

    public function create()
    {
        return view('program.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        Program::create($data);
        return redirect()->route('program.index')->with('success', 'Program berjaya ditambah!');
    }

    public function show(Program $program)
    {
        return view('program.show', compact('program'));
    }

    public function edit(Program $program)
    {
        return view('program.edit', compact('program'));
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->except(['_token', '_method']);
        $program->update($data);
        return redirect()->route('program.index')->with('success', 'Program berjaya dikemaskini!');
    }

    public function destroy(Program $program)
    {
        $program->delete();
        return redirect()->route('program.index')->with('success', 'Program berjaya dipadam!');
    }
}
