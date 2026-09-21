<?php

use App\Exports\L3Export;
use Maatwebsite\Excel\Facades\Excel;

test('l3 export returns a blank excel workbook', function () {
    $export = new L3Export;

    expect($export->collection())->toBeEmpty();

    Excel::fake();

    Excel::download($export, 'L3.xlsx');

    Excel::assertDownloaded('L3.xlsx', function (L3Export $export) {
        return $export->collection()->isEmpty();
    });
});
