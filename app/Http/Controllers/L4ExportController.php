<?php

namespace App\Http\Controllers;

use App\Exports\L4Export;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class L4ExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new L4Export,
            'L4.xlsx'
        );
    }
}
