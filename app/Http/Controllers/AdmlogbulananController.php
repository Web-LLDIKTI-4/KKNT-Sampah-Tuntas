<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dpl\PenilaianLogbulananRequest;
use App\Models\Dplmentoring;
use App\Models\Logbulanan;
use App\Models\Mahasiswa;
use App\Support\ActionButtons;
use App\Support\HtmlSanitizer;
use App\Support\XlsxStreamer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $student = Mahasiswa::with('sp')->where('email', $email)->first();
        $rows = Logbulanan::where('email', $email)->orderBy('tahun')->orderBy('bulan')->orderBy('id_logbulanan')->lazy(1000);

        return (new XlsxStreamer('Log Bulanan Mahasiswa', [
            XlsxStreamer::column('NIM', fn () => $student?->nim, 15),
            XlsxStreamer::column('Nama Mahasiswa', fn () => $student?->nama, 30),
            XlsxStreamer::column('Nama Perguruan Tinggi', fn () => $student?->sp?->nm_lemb, 35),
            XlsxStreamer::column('Bulan', 'bulan', 8, 'number'),
            XlsxStreamer::column('Deskripsi', fn (Logbulanan $row) => HtmlSanitizer::plainText($row->deskripsi), 70),
            XlsxStreamer::column('Nilai', 'nilai', 8, 'number'),
        ]))
            ->info(['Mahasiswa' => $student?->nama, 'Email' => $email])
            ->download('logbulanan_mahasiswa_'.date('Y-m-d_H-i-s').'.xlsx', $rows);
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
