<?php

namespace App\Http\Controllers;

use App\Models\Aktiviti;
use Illuminate\Http\Request;
use App\Models\Program;

class AktivitiController extends Controller
{
    // public function index()
    // {
    //     $items = Aktiviti::paginate(20);
    //     return view('aktiviti.index', compact('items'));
    // }

    public function index(Request $request)
{
    $search = $request->get('search');
    $program_id = $request->get('program_id');

    $programs = Program::orderBy('nama_program')->get(); // untuk dropdown filter

    $items = Aktiviti::with('program')
        ->when($search, function($q) use ($search) {
            $q->where('nama_aktiviti', 'like', "%$search%")
              ->orWhere('no_aktivit', 'like', "%$search%");
        })
        ->when($program_id, function($q) use ($program_id) {
            $q->where('program_id', $program_id);
        })
        ->paginate(20)
        ->withQueryString();

    return view('aktiviti.index', compact('items', 'search', 'programs', 'program_id'));
}


    // public function index(Request $request)
    // {
    //     $search = $request->get('search');
    //     $program_id = $request->get('program_id');
    //     $programs = Program::orderBy('nama_program')->get(); // ← this line
    //     return view('aktiviti.index', compact('items', 'search', 'programs', 'program_id')); // ← all 4 variables
    // }

    public function create()
    {
        $programs = Program::orderBy('nama_program')->get();
        return view('aktiviti.create', compact('programs'));
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
        $programs = Program::orderBy('nama_program')->get();
        return view('aktiviti.edit', compact('aktiviti', 'programs'));
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
