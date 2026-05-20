<?php

namespace App\Http\Controllers;

use App\Models\Bahagian;
use Illuminate\Http\Request;
use App\Models\Ptj;

class BahagianController extends Controller
{
    public function index()
    {
        $items = Bahagian::paginate(20);
        return view('bahagian.index', compact('items'));
    }

       public function create()
    {
        $ptjs = Ptj::all();
        return view('bahagian.create', compact('ptjs'));
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

    // public function edit(Bahagian $bahagian)
    // {
    //     return view('bahagian.edit', compact('bahagian'));
    // }

    public function edit(Bahagian $bahagian)
    {
        $ptjs = Ptj::all();
        return view('bahagian.edit', compact('bahagian', 'ptjs'));
        // compact tu sama ja cara tulis dia macam bawah ni cuma dia pendekkan 
        // return view('bahagian.edit', [
        //     'bahagian' => $bahagian,
        //     'ptjs' => $ptjs,
        // ]);
    }

    public function update(Request $request, Bahagian $bahagian)
    {
        $data = $request->except(['_token', '_method']);
        $bahagian->update($data);
        return redirect()->route('bahagian.index')->with('success', 'Bahagian berjaya dikemaskini!');

        {
            $search = $request->get('search');
            $items = Bahagian::with('ptj')
                ->when($search, function($q) use ($search) {
                    $q->where('nama_bahagian', 'like', "%$search%")
                    ->orWhereHas('ptj', fn($q) => $q->where('nama_ptj', 'like', "%$search%"));
                })
                ->paginate(20)
                ->withQueryString();

            return view('bahagian.index', compact('items', 'search'));
        }
    }

    public function destroy(Bahagian $bahagian)
    {
        $bahagian->delete();
        return redirect()->route('bahagian.index')->with('success', 'Bahagian berjaya dipadam!');
    }
    
}
