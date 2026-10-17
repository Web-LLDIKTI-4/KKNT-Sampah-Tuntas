<?php

namespace App\Services;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Freeform;
use App\Models\Kehadiran;
use App\Models\CapaianKegiatan;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\PendataanPemilahanSampah;
use App\Models\PenguranganSampah;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Nilaikonversi;
use App\Models\Pjdesa;
use App\Models\RencanaKerja;
use App\Models\Tugasakhir;
use App\Models\User;
use App\Observers\LoginStatistikCacheObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus data master beserta data turunannya dalam satu transaksi.
 * File & cache dibersihkan setelah commit agar tidak hilang bila transaksi gagal.
 */
class PersonDeletionService
{
    public function __construct(private ProfilePhotoService $photos) {}

    public function deleteMahasiswa(Mahasiswa $mahasiswa): void
    {
        $user = User::where('email', $mahasiswa->email)->where('role', 'mahasiswa')->first();

        DB::transaction(function () use ($mahasiswa, $user) {
            $this->deleteMahasiswaData($mahasiswa->email, [$mahasiswa->id_mahasiswa]);
            if ($user) {
                $this->deleteAccount($user);
            }
        });

        $this->afterCommit($user);
    }

    public function deleteDpl(Dpl $dpl): void
    {
        $user = User::where('email', $dpl->email)->where('role', 'dpl')->first();

        DB::transaction(function () use ($dpl, $user) {
            $this->deleteDplData($dpl->email);
            if ($user) {
                $this->deleteAccount($user);
            }
            $dpl->delete();
        });

        $this->afterCommit($user);
    }

    public function deleteUser(User $user): void
    {
        // Kumpulkan path sebelum baris dihapus
        $files = $user->role === 'pt'
            ? RencanaKerja::where('kodept', $user->email)->pluck('file_path')->filter()->all()
            : [];

        DB::transaction(function () use ($user) {
            match ($user->role) {
                'mahasiswa' => $this->deleteMahasiswaData($user->email, []),
                'dpl' => $this->deleteDplData($user->email),
                'pt' => $this->deletePtData($user->email),
                default => null,
            };
            $this->deleteAccount($user);
        });

        $this->afterCommit($user, $files);
    }

    // Email null dilewati: where('email', null) = whereNull, bisa menghapus data orang lain
    private function deleteMahasiswaData(?string $email, array $ids): void
    {
        if ($email !== null) {
            foreach ([Kehadiran::class, Logkegiatan::class, PendataanPemilahanSampah::class, Logbulanan::class, Pjdesa::class, CapaianKegiatan::class, PenguranganSampah::class, Tugasakhir::class] as $model) {
                $model::where('email', $email)->delete();
            }
            Dplmentoring::where('email_mahasiswa', $email)->delete();
            // Tetap jalan walau baris master mahasiswa tidak ada
            $ids = array_unique([...$ids, ...Mahasiswa::where('email', $email)->pluck('id_mahasiswa')->all()]);
        }
        if ($ids) {
            foreach ([Nilaikonversi::class, Freeform::class, Mahasiswa_lokasi::class] as $model) {
                $model::whereIn('id_mahasiswa', $ids)->delete();
            }
            Mahasiswa::whereIn('id_mahasiswa', $ids)->delete();
        }
    }

    private function deleteDplData(?string $email): void
    {
        if ($email === null) {
            return;
        }
        Dplmentoring::where('email_dpl', $email)->delete();
        Dpllaporan::where('email', $email)->delete();
        // Nilai & tugas akhir milik mahasiswa: hanya lepas kaitan DPL, jangan dihapus
        foreach ([Nilaikonversi::class, Freeform::class, Tugasakhir::class] as $model) {
            $model::where('email_dpl', $email)->update(['email_dpl' => null]);
        }
        Dpl::where('email', $email)->delete();
    }

    private function deletePtData(string $kodept): void
    {
        // Mass delete: hook deleting model tidak jalan, file dihapus setelah commit
        RencanaKerja::where('kodept', $kodept)->delete();
        Evaluasikegiatanjawaban::where('user', $kodept)->delete();
    }

    private function deleteAccount(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_resets')->where('email', $user->email)->delete();
        User::whereKey($user->id)->delete();
    }

    private function afterCommit(?User $user, array $files = []): void
    {
        if ($user) {
            $this->photos->delete($user);
        }
        if ($files) {
            Storage::disk(RencanaKerja::DISK)->delete($files);
        }
        PenguranganSampah::forgetPublicCache();
        PetaSebaranService::flushCache();
        LoginStatistikCacheObserver::flush();
    }
}
