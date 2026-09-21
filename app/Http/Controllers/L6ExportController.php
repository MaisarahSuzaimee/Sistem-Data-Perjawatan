<?php

namespace App\Http\Controllers;

use App\Exports\L6Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L6ExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new L6Export,
            'L6.xlsx'
        );
    }
}
