<?php

namespace App\Http\Controllers;

use App\Exports\FasilitiByParlimenExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FasilitiByParlimenExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new FasilitiByParlimenExport,
            'senarai_fasiliti_mengikut_parlimen_dan_dun.xlsx'
        );
    }
}
