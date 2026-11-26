<?php

namespace App\Http\Controllers;

use App\Exports\L4Export;
use App\Models\Kumpulan;
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

        $kumpulanId = (int) $validated['kumpulan_id'];
        $kumpulanName = trim((string) Kumpulan::query()->whereKey($kumpulanId)->value('nama_kumpulan'));
        $safeKumpulanName = str_replace(['/', '\\'], '-', $kumpulanName);
        $filename = $safeKumpulanName !== ''
            ? "Data Keseluruhan Mengikut Jawatan ({$safeKumpulanName}).xlsx"
            : 'Data Keseluruhan Mengikut Jawatan.xlsx';

        return Excel::download(
            new L4Export($kumpulanId),
            $filename
        );
    }
}
