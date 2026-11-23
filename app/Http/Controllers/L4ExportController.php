<?php

namespace App\Http\Controllers;

use App\Exports\L4Export;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L4ExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'kumpulan_id' => ['required', 'integer', 'exists:kumpulans,id'],
        ]);

        return Excel::download(
            new L4Export((int) $validated['kumpulan_id']),
            'Data_Perjawatan_Mengikut_Kumpulan.xlsx'
        );
    }
}
