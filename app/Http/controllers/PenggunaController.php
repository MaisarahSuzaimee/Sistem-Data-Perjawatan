<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    public function index()
    {
        $items = User::paginate(20);
        return view('pengguna.index', compact('items'));
    }

    public function create()
    {
        return view('pengguna.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berjaya ditambah!');
    }

    public function show(User $pengguna)
    {
        return view('pengguna.show', compact('pengguna'));
    }

    public function edit(User $pengguna)
    {
        return view('pengguna.edit', compact('pengguna'));
    }

    public function update(Request $request, User $pengguna)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$pengguna->id,
        ]);
        $pengguna->update($request->only('name', 'email'));
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berjaya dikemaskini!');
    }

    public function destroy(User $pengguna)
    {
        $pengguna->delete();
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berjaya dipadam!');
    }
}
