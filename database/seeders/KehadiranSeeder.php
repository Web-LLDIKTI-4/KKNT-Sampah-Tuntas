<?php

namespace Database\Seeders;

use App\Models\Kehadiran;
use App\Services\AttendanceService;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Kehadiran 14 hari kerja terakhir tiap mahasiswa kelompok: ±90% hadir, sisanya AttendanceService::IZIN_STATUSES.
 * Hari ini: anggota 1 izin, anggota 2 kuliah, ketua baru absen datang. Kunci: email + tanggal.
 * Jalankan: php artisan db:seed --class=KehadiranSeeder
 * Prasyarat: MahasiswaSeeder
 */
class KehadiranSeeder extends Seeder
{
    use SeedsDummyData;

    private const HARI = 14;

    public function run(): void
    {
        $mahasiswa = $this->mahasiswaKelompok();
        $this->seedRandom();

        foreach ($mahasiswa as $mhs) {
            foreach (range(self::HARI, 1) as $hari) {
                $tanggal = Carbon::today()->subDays($hari);
                if ($tanggal->isWeekend()) {
                    continue;
                }

                $status = mt_rand(1, 100) <= 10 ? fake()->randomElement(AttendanceService::IZIN_STATUSES) : 'hadir';
                $this->kehadiran($mhs->email, $tanggal, $status);
            }

            match (true) {
                str_starts_with($mhs->email, 'mhs1.') => $this->kehadiran($mhs->email, Carbon::today(), 'izin'),
                str_starts_with($mhs->email, 'mhs2.') => $this->kehadiran($mhs->email, Carbon::today(), 'kuliah'),
                str_starts_with($mhs->email, 'ketua.') => $this->kehadiran($mhs->email, Carbon::today(), 'hadir', pulang: false),
                default => null,
            };
        }
    }

    private function kehadiran(string $email, Carbon $tanggal, string $status, bool $pulang = true): void
    {
        $hadir = $status === 'hadir';
        // Nilai acak selalu dibangkitkan agar urutan random sama walau baris sudah ada
        $data = Kehadiran::factory()->raw([
            'tanggal' => $tanggal->toDateString(),
            'status_kehadiran' => $status,
            'waktu_masuk' => $hadir ? $tanggal->copy()->setTime(8, mt_rand(0, 30)) : null,
            'waktu_pulang' => $hadir && $pulang ? $tanggal->copy()->setTime(16, mt_rand(0, 30)) : null,
            'keterangan' => $hadir ? null : ucfirst($status).' – '.fake()->sentence(4),
        ]);

        Kehadiran::without(['mahasiswa', 'dplmentoring'])->firstOrCreate(['email' => $email, 'tanggal' => $tanggal->toDateString()], $data);
    }
}
