<?php

namespace App\Http\Controllers;

use App\Exports\LogBulananLengkapExport;
use App\Exports\LogKehadiranLengkapExport;
use App\Http\Requests\LogHarianExportRequest;

// Export keseluruhan log bulanan & kehadiran; scope data per role di class export
class LogExportController extends Controller
{
    public function logbulanan(LogHarianExportRequest $request)
    {
        $bulan = $request->validated('bulan');

        return (new LogBulananLengkapExport($request->user(), $bulan))
            ->download('log_bulanan_'.($bulan ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    public function logkehadiran(LogHarianExportRequest $request)
    {
        $bulan = $request->validated('bulan');

        return (new LogKehadiranLengkapExport($request->user(), $bulan))
            ->download('log_kehadiran_'.($bulan ?? 'semua').'_'.date('Y-m-d_H-i-s').'.xlsx');
    }
}
