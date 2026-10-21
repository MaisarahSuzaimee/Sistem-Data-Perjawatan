<?php

namespace App\Http\Controllers;

use App\Exports\L7Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L7ExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new L7Export,
            'L7.xlsx'
        );
    }
}
