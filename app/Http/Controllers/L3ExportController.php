<?php

namespace App\Http\Controllers;

use App\Exports\L3Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L3ExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new L3Export,
            'L3.xlsx'
        );
    }
}
