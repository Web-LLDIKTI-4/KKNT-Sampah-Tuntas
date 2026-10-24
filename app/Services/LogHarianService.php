<?php

namespace App\Services;

use App\Http\Requests\Mahasiswa\LogkegiatanRequest;
use App\Http\Requests\Mahasiswa\PendataanPemilahanSampahRequest;
use App\Models\Logkegiatan;
use App\Models\PendataanPemilahanSampah;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LogHarianService
{
    // Batas anti-spam: maks isian (pendataan + log kegiatan) per mahasiswa dalam rolling window
    public const MAX_LOG_PER_WINDOW = 5;

    public const LOG_WINDOW_MINUTES = 30;

    // Plafon berat per jenis sampah per log (kg)
    public const MAX_BERAT_KG = 1000;

    // Simpan ke satu tabel sesuai mode (pendataan / kegiatan)
    public function store(array $data, string $email): void
    {
        DB::transaction(function () use ($data, $email) {
            // Lock baris user agar request paralel antre, lalu cek ulang batas (anti race)
            User::where('email', $email)->lockForUpdate()->first();
            $minutes = $this->minutesUntilAvailable($email);
            if ($minutes > 0) {
                throw ValidationException::withMessages(['mode' => $this->limitMessage($minutes)]);
            }

            if ($data['mode'] === 'pendataan') {
                PendataanPemilahanSampah::create(['email' => $email] + Arr::only($data, PendataanPemilahanSampahRequest::FIELDS));
            } else {
                Logkegiatan::create(['email' => $email] + Arr::only($data, LogkegiatanRequest::FIELDS));
            }
        });
    }

    // Menit tersisa sampai boleh input lagi; 0 bila batas window belum penuh
    public function minutesUntilAvailable(string $email): int
    {
        $since = now()->subMinutes(self::LOG_WINDOW_MINUTES);

        $recent = PendataanPemilahanSampah::where('email', $email)->where('created_at', '>', $since)->select('created_at')
            ->toBase()
            ->unionAll(Logkegiatan::where('email', $email)->where('created_at', '>', $since)->select('created_at')->toBase())
            ->orderByDesc('created_at')
            ->limit(self::MAX_LOG_PER_WINDOW)
            ->pluck('created_at');

        if ($recent->count() < self::MAX_LOG_PER_WINDOW) {
            return 0;
        }

        // Slot terbuka saat record tertua dari N terbaru keluar dari window
        $availableAt = Carbon::parse($recent->last())->addMinutes(self::LOG_WINDOW_MINUTES);
        if ($availableAt->lessThanOrEqualTo(now())) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInSeconds($availableAt, true) / 60));
    }

    public function limitMessage(int $minutes): string
    {
        return 'Batas input tercapai (maksimal '.self::MAX_LOG_PER_WINDOW.' isian per '.self::LOG_WINDOW_MINUTES.' menit). Coba lagi sekitar '.$minutes.' menit lagi.';
    }
}
