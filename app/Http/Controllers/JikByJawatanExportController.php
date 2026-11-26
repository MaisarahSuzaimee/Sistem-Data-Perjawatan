<?php

namespace App\Http\Controllers;

use App\Exports\JikByJawatanExport;
use App\Models\Jawatan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JikByJawatanExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'jawatan_id' => ['required', 'integer', 'exists:jawatans,id'],
        ]);

        $jawatanId = (int) $validated['jawatan_id'];
        $jawatanName = trim((string) Jawatan::query()->whereKey($jawatanId)->value('desc_jawatan'));
        $safeJawatanName = str_replace(['/', '\\'], '-', $jawatanName);
        $filename = $safeJawatanName !== ''
            ? "Laporan JIK Mengikut Jawatan ({$safeJawatanName}).xlsx"
            : 'Laporan JIK Mengikut Jawatan.xlsx';

        return Excel::download(
            new JikByJawatanExport($jawatanId),
            $filename
        );
    }
}
