<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Aktiviti;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * index() - Papar senarai semua Program
     * Dipanggil bila user pergi ke /program
     */
    // public function index()
    // {
    //     // Ambil semua program + aktiviti berkaitan, susun ikut nama, 10 per page
    //     $items = Program::with('aktiviti')->orderBy('nama_program', 'asc')->paginate(10);
    //     return view('program.index', compact('items'));
    // }
        public function index()
    {
        $items = Program::withCount('aktiviti')->orderBy('nama_program', 'asc')->paginate(10);
        return view('program.index', compact('items'));
    }

    /**
     * create() - Papar form tambah Program baru
     * Dipanggil bila user klik butang "+ Tambah Program"
     */
    public function create()
    {
        return view('program.create');
    }

    /**
     * store() - Simpan Program baru ke database
     * Dipanggil bila user submit form tambah
     */
    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'nama_program' => 'required|string|max:255|unique:programs',
        ]);

        // Cipta Program baru
        $program = Program::create([
            'nama_program' => strtoupper($request->nama_program),
            'desc_program' => strtoupper($request->desc_program),
        ]);

        // Simpan Aktiviti yang dimasukkan (jika ada)
        if ($request->aktiviti) {
            foreach ($request->aktiviti as $a) {
                if (!empty($a['no_aktivit'])) {
                    $program->aktiviti()->create([
                        'no_aktivit'    => strtoupper($a['no_aktivit']),
                        'nama_aktiviti' => strtoupper($a['nama_aktiviti'] ?? ''),
                    ]);
                }
            }
        }

        return redirect()->route('program.index')->with('success', 'Program berjaya ditambah!');
    }

    /**
     * show() - Papar detail satu Program (optional)
     * Dipanggil bila user klik nama program untuk tengok detail
     */
    public function show(Program $program)
    {
        $program->load('aktiviti');
        return view('program.show', compact('program'));
    }

    /**
     * edit() - Papar form edit Program sedia ada
     * Dipanggil bila user klik butang edit (pensil)
     */
    public function edit(Program $program)
    {
        // Load aktiviti berkaitan dengan program ini
        $program->load('aktiviti');
        return view('program.edit', compact('program'));
    }

    /**
     * update() - Kemaskini Program dalam database
     * Dipanggil bila user submit form edit
     */
    public function update(Request $request, Program $program)
    {
        // Validate input
        $request->validate([
            'nama_program' => 'required|string|max:255|unique:programs,nama_program,' . $program->id,
        ]);

        // Kemaskini data Program
        $program->update([
            'nama_program' => strtoupper($request->nama_program),
            'desc_program' => strtoupper($request->desc_program),
        ]);

        // Padam semua aktiviti lama, ganti dengan yang baru
        $program->aktiviti()->delete();

        if ($request->aktiviti) {
            foreach ($request->aktiviti as $a) {
                if (!empty($a['no_aktivit'])) {
                    $program->aktiviti()->create([
                        'no_aktivit'    => strtoupper($a['no_aktivit']),
                        'nama_aktiviti' => strtoupper($a['nama_aktiviti'] ?? ''),
                    ]);
                }
            }
        }

        return redirect()->route('program.index')->with('success', 'Program berjaya dikemaskini!');
    }

    /**
     * destroy() - Padam Program dari database
     * Dipanggil bila user klik butang padam (tong sampah)
     * Aktiviti berkaitan akan dipadam automatik (sebab ada cascade delete dalam model)
     */
    public function destroy(Program $program)
    {
        $program->delete();
        return redirect()->route('program.index')->with('success', 'Program berjaya dipadam!');
    }
}
