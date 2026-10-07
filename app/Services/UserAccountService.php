<?php

namespace App\Services;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Dplmentoring;
use App\Models\Kehadiran;
use App\Models\Kpicapaian;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\PendataanPemilahanSampah;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Pjdesa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAccountService
{
    /**
     * Buat akun login untuk mahasiswa yang belum punya akun.
     * Password awal = NIM (kebijakan lama, sebaiknya diganti saat login pertama).
     */
    public function createForMahasiswa(array $emails): int
    {
        $rows = Mahasiswa::whereIn('email', $emails)->whereDoesntHave('user')->get();

        return $this->createMany($rows, fn (Mahasiswa $m) => [
            'name' => $m->nama,
            'email' => $m->email,
            'location_program' => $m->location_program,
            'password' => Hash::make($m->nim),
            'role' => 'mahasiswa',
        ]);
    }

    /**
     * Buat akun login untuk DPL yang belum punya akun (password awal = NIDN).
     */
    public function createForDpl(array $emails): int
    {
        $rows = Dpl::whereIn('email', $emails)->whereDoesntHave('user')->get();

        return $this->createMany($rows, fn (Dpl $d) => [
            'name' => $d->nama,
            'email' => $d->email,
            'location_program' => $d->location_program,
            'password' => Hash::make($d->nidn),
            'role' => 'dpl',
        ]);
    }

    /**
     * Ganti email user beserta semua data yang terhubung lewat email.
     */
    public function changeEmail(User $user, string $newEmail): void
    {
        $old = $user->email;
        if ($old === $newEmail) {
            return;
        }

        DB::transaction(function () use ($user, $old, $newEmail) {
            if ($user->role === 'mahasiswa') {
                foreach ([Mahasiswa::class, Kehadiran::class, Logkegiatan::class, PendataanPemilahanSampah::class, Logbulanan::class, Pjdesa::class, Kpicapaian::class] as $model) {
                    $model::where('email', $old)->update(['email' => $newEmail]);
                }
                Dplmentoring::where('email_mahasiswa', $old)->update(['email_mahasiswa' => $newEmail]);
            } else {
                Dpl::where('email', $old)->update(['email' => $newEmail]);
                Dpllaporan::where('email', $old)->update(['email' => $newEmail]);
                Dplmentoring::where('email_dpl', $old)->update(['email_dpl' => $newEmail]);
                Nilaikonversi::where('email_dpl', $old)->update(['email_dpl' => $newEmail]);
            }
            $user->forceFill(['email' => $newEmail])->save();
        });
    }

    private function createMany($rows, callable $map): int
    {
        // forceFill: role sengaja tidak masuk $fillable User
        DB::transaction(fn () => $rows->each(fn ($row) => (new User)->forceFill($map($row))->save()));

        return $rows->count();
    }
}
