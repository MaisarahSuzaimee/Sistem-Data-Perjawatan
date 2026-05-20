<?php

namespace App\Http\Controllers;

use App\Models\PegawaiKontrak;
use Illuminate\Http\Request;

class PegawaiKontrakController extends Controller
{
        public function index()
    {
        $items = PegawaiKontrak::with('pegawai')->paginate(20);
        return view('pegawai-kontrak.index', compact('items'));
    }

    public function create()
    {
        return view('pegawai-kontrak.create');
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        PegawaiKontrak::create($data);
        return redirect()->route('pegawai-kontrak.index')->with('success', 'PegawaiKontrak berjaya ditambah!');
    }

    public function show(PegawaiKontrak $pegawaiKontrak)
    {
        return view('pegawai-kontrak.show', compact('pegawaiKontrak'));
    }

    public function edit(PegawaiKontrak $pegawaiKontrak)
    {
        return view('pegawai-kontrak.edit', compact('pegawaiKontrak'));
    }

    public function update(Request $request, PegawaiKontrak $pegawaiKontrak)
    {
        $data = $request->except(['_token', '_method']);
        $pegawaiKontrak->update($data);
        return redirect()->route('pegawai-kontrak.index')->with('success', 'PegawaiKontrak berjaya dikemaskini!');
    }

    public function destroy(PegawaiKontrak $pegawaiKontrak)
    {
        $pegawaiKontrak->delete();
        return redirect()->route('pegawai-kontrak.index')->with('success', 'PegawaiKontrak berjaya dipadam!');
    }
}
