<?php

namespace App\Http\Controllers;

use App\Exports\LogHarianLengkapExport;
use App\Http\Requests\LogHarianExportRequest;
use Maatwebsite\Excel\Facades\Excel;

// Export log harian lengkap semua role; scope data di LogHarianLengkapExport
class LogharianExportController extends Controller
{
    public function __invoke(LogHarianExportRequest $request)
    {
        $bulan = $request->validated('bulan');

        return Excel::download(
            new LogHarianLengkapExport($request->user(), $bulan),
            'log_aktivitas_'.($bulan ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx'
        );
    }
}
