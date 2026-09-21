<?php

use App\Http\Controllers\DataKeseluruhanExportController;
use App\Http\Controllers\DataKontrakExportController;
use App\Http\Controllers\JikByJawatanExportController;
use App\Http\Controllers\L3ExportController;
use App\Http\Controllers\L4ExportController;
use App\Http\Controllers\L6ExportController;
use App\Http\Controllers\L7ExportController;
use App\Http\Controllers\L8ExportController;
use App\Http\Controllers\LetakJawatanExportController;
use App\Http\Controllers\PenamatanPerkhidmatanExportController;
use App\Http\Controllers\UserExportController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/app');

Route::get('/export-users', [UserExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.users');

// Route::get('/export-letakJawatan', [LetakJawatanExportController::class, 'export'])
//     ->name('export.letakJawatan');

Route::get('/export-letak-jawatan', [LetakJawatanExportController::class, 'export'])
    ->name('export.letakJawatan');

Route::get('/export-penamatan-perkhidmatan', [PenamatanPerkhidmatanExportController::class, 'export'])
    ->name('export.penamatanPerkhidmatan');

// L1
Route::get('/export-data-keseluruhan', [DataKeseluruhanExportController::class, 'export'])
    ->name('export.dataKeseluruhan');

// L2
Route::get('/export-data-kontrak', [DataKontrakExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.dataKontrak');

// L3
Route::get('/export-l3', [L3ExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.l3');

//L4
Route::get('export-l4', [L4ExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.l4');

//L5
Route::get('/export-jik-by-jawatan', [JikByJawatanExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.jikByJawatan');

//L6
Route::get('export-l6', [L6ExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.l6');

//L7
Route::get('export-l7', [L7ExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.l7');

//L8
Route::get('export-l8', [L8ExportController::class, 'export'])
    ->middleware('auth')
    ->name('export.l8');
