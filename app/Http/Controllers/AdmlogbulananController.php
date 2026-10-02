<?php

namespace App\Http\Controllers;

use App\Exports\LogBulananByMhsExport;
use App\Http\Requests\Dpl\PenilaianLogbulananRequest;
use App\Models\Dplmentoring;
use App\Models\Logbulanan;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Facades\DataTables;

class AdmlogbulananController extends StudentLogReportController
{
    protected function viewPrefix(): string
    {
        return 'logbulanan.dpl';
    }

    protected function routePrefix(): string
    {
        return 'admlogbulanan';
    }

    protected function logRelation(): string
    {
        return 'logbulanan';
    }

    protected function detailTable(string $email): DataTableAbstract
    {
        $user = auth()->user();
        $canGrade = $user->role === 'dpl' && Dplmentoring::isMentor($user, $email);

        return DataTables::of(Logbulanan::where('email', $email)->with('mahasiswa.sp')->orderBy('tahun')->orderBy('bulan')->get())
            ->addIndexColumn()
            ->addColumn('nama_mahasiswa', fn ($row) => $row->mahasiswa->nama ?? 'Nama tidak tersedia')
            ->addColumn('nm_lemb', fn ($row) => $row->mahasiswa->sp->nm_lemb ?? 'Nama Perguruan Tinggi tidak tersedia')
            ->editColumn('deskripsi', fn ($row) => HtmlSanitizer::clean($row->deskripsi).'<br>'.HtmlSanitizer::link($row->tautan))
            ->addColumn('nama_bulan', fn ($row) => Carbon::create()->month((int) $row->bulan)->translatedFormat('F'))
            ->addColumn('action', fn ($row) => $canGrade && $row->nilai === null
                ? ActionButtons::modal(
                    url('admlogbulanan/formpenilaian/'.$row->id_logbulanan),
                    'Berikan Nilai',
                    'Penilaian Log Bulanan',
                )
                : e($row->nilai))
            ->rawColumns(['deskripsi', 'action']);
    }

    protected function exportFor(string $email)
    {
        return Excel::download(new LogBulananByMhsExport($email), 'logbulanan_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    public function formpenilaian(Request $request, string $id)
    {
        $logbulanan = Logbulanan::findOrFail($id);
        $this->authorizeStudent($request, $logbulanan->email);

        return view('logbulanan.dpl.penilaian', [
            'logbulanan' => $logbulanan,
            'anilai' => PenilaianLogbulananRequest::PILIHAN_NILAI,
        ]);
    }

    public function updatenilai(PenilaianLogbulananRequest $request)
    {
        $logbulanan = Logbulanan::find($request->validated('id_logbulanan'));
        if (! $logbulanan) {
            return $this->failed('Data tidak ditemukan', 404);
        }
        if (! Dplmentoring::isMentor($request->user(), $logbulanan->email)) {
            return $this->failed('Anda bukan DPL pembimbing mahasiswa ini.', 403);
        }

        $logbulanan->update([
            'nilai' => $request->validated('nilai'),
            'hasil_verifikasi' => $request->validated('hasil_verifikasi'),
            'verifikator' => $request->user()->email,
        ]);

        return $this->saved('Data berhasil diupdate');
    }
}
