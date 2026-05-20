<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\WaranController;
use App\Http\Controllers\AktivitiController;
use App\Http\Controllers\ButiranController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\PtjController;
use App\Http\Controllers\BahagianController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SubunitController;
use App\Http\Controllers\GredController;
use App\Http\Controllers\JawatanController;
use App\Http\Controllers\OpsyenPencenController;
use App\Http\Controllers\ParlimenController;
use App\Http\Controllers\DunController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\JawatanGredController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Email atau kata laluan tidak betul.',
        ]);
    })->name('login.post');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    // Pengurusan
    Route::resource('pegawai', PegawaiController::class);
    Route::resource('pegawai-kontrak', App\Http\Controllers\PegawaiKontrakController::class);
    Route::resource('waran', WaranController::class);

    // Organisasi
    Route::resource('ptj', PtjController::class);
    Route::resource('bahagian', BahagianController::class);
    Route::resource('unit', UnitController::class);
    Route::resource('subunit', SubunitController::class);

    // Rujukan
    Route::resource('program', ProgramController::class);
    Route::resource('aktiviti', AktivitiController::class);
    Route::resource('butiran', ButiranController::class);
    Route::resource('gred', GredController::class);
    Route::resource('jawatan', JawatanController::class);
    Route::resource('opsyen-pencen', OpsyenPencenController::class);

    // Lokasi
    Route::resource('parlimen', ParlimenController::class);
    Route::resource('dun', DunController::class);

    // Sistem
    Route::resource('pengguna', PenggunaController::class);
    Route::resource('jawatan-gred', JawatanGredController::class);
});
