<?php

namespace App\Services;

use App\Models\CapaianKegiatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tunjuk / cabut ketua kelompok. Return pesan penolakan, null = sukses.
 */
class KetuaKelompokService
{
    // Desa lokasi KKN terbaru (sama dengan UserController::updateuser)
    public function lokasiDesa(Mahasiswa $mahasiswa): ?string
    {
        return Mahasiswa_lokasi::where('id_mahasiswa', $mahasiswa->id_mahasiswa)->orderByDesc('tahun')->value('id_desa');
    }

    public function assign(Mahasiswa $mahasiswa): ?string
    {
        $user = filled($mahasiswa->email)
            ? User::where('email', $mahasiswa->email)->where('role', 'mahasiswa')->first()
            : null;
        if (! $user) {
            return 'Mahasiswa belum memiliki akun login, tidak dapat ditetapkan sebagai ketua kelompok.';
        }

        $idDesa = $this->lokasiDesa($mahasiswa);
        if (! $idDesa) {
            return 'Tidak dapat set sebagai ketua kelompok, mahasiswa harus set lokasi kegiatan KKN terlebih dahulu!';
        }

        return DB::transaction(function () use ($user, $idDesa) {
            // Lock user row: serializes concurrent assigns for the same email
            $user = User::whereKey($user->getKey())->lockForUpdate()->first();
            if (Pjdesa::where('email', $user->email)->exists()) {
                return 'Mahasiswa sudah menjadi ketua kelompok.';
            }

            Pjdesa::create(['email' => $user->email, 'id_desa' => $idDesa]);
            $user->forceFill(['akses' => 'pjdesa'])->save();

            return null;
        });
    }

    public function revoke(Pjdesa $pjdesa): ?string
    {
        if (CapaianKegiatan::where('email', $pjdesa->email)->whereYear('bulan', now()->year)->exists()) {
            return 'Tidak dapat dihapus karena ketua kelompok memiliki capaian KPI tahun ini';
        }

        DB::transaction(function () use ($pjdesa) {
            $pjdesa->delete();
            // Revoke only when no other assignment remains for this email
            if (! Pjdesa::where('email', $pjdesa->email)->exists()) {
                User::where('email', $pjdesa->email)->where('akses', 'pjdesa')->update(['akses' => null]);
            }
        });

        return null;
    }
}
