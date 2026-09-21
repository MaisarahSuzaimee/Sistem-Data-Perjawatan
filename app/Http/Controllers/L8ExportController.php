<?php

namespace App\Http\Controllers;

use App\Exports\L8Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L8ExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new L8Export,
            'L8.xlsx'
        );
    }
}
