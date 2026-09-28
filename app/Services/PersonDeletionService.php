<?php

namespace App\Services;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Kehadiran;
use App\Models\Kpicapaian;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Nilaikonversi;
use App\Models\Pjdesa;
use App\Models\Tugasakhir;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hapus data master beserta data turunannya dalam satu transaksi.
 */
class PersonDeletionService
{
    public function deleteMahasiswa(Mahasiswa $mahasiswa): void
    {
        DB::transaction(function () use ($mahasiswa) {
            $email = $mahasiswa->email;
            foreach ([Kehadiran::class, Logkegiatan::class, Logbulanan::class, Pjdesa::class, Kpicapaian::class, Tugasakhir::class] as $model) {
                $model::where('email', $email)->delete();
            }
            foreach ([Nilaikonversi::class, Freeform::class, Mahasiswa_lokasi::class] as $model) {
                $model::where('id_mahasiswa', $mahasiswa->id_mahasiswa)->delete();
            }
            Dplmentoring::where('email_mahasiswa', $email)->delete();
            User::where('email', $email)->where('role', 'mahasiswa')->delete();
            Mahasiswa::where('email', $email)->delete();
        });
    }

    public function deleteDpl(Dpl $dpl): void
    {
        DB::transaction(function () use ($dpl) {
            $email = $dpl->email;
            Dplmentoring::where('email_dpl', $email)->delete();
            Dpllaporan::where('email', $email)->delete();
            Nilaikonversi::where('email_dpl', $email)->delete();
            Freeform::where('email_dpl', $email)->delete();
            // Tugas akhir milik mahasiswa: hanya lepas kaitan DPL, jangan dihapus
            Tugasakhir::where('email_dpl', $email)->update(['email_dpl' => null]);
            User::where('email', $email)->where('role', 'dpl')->delete();
            $dpl->delete();
        });
    }
}
