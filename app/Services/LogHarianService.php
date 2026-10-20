<?php

namespace App\Services;

use App\Http\Requests\Mahasiswa\LogkegiatanRequest;
use App\Http\Requests\Mahasiswa\PendataanPemilahanSampahRequest;
use App\Models\Logkegiatan;
use App\Models\PendataanPemilahanSampah;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class LogHarianService
{
    // Batas anti-spam: jumlah input log harian per mahasiswa per hari (created_at)
    public const MAX_LOG_PER_DAY = 20;

    // Plafon berat per jenis sampah per log (kg)
    public const MAX_BERAT_KG = 1000;

    // Simpan pendataan pemilahan + log kegiatan (opsional) dalam satu transaksi
    public function store(array $data, string $email): void
    {
        DB::transaction(function () use ($data, $email) {
            PendataanPemilahanSampah::create(['email' => $email] + Arr::only($data, PendataanPemilahanSampahRequest::FIELDS));

            if (filter_var($data['ada_kegiatan_lain'], FILTER_VALIDATE_BOOLEAN)) {
                Logkegiatan::create(['email' => $email] + Arr::only($data, LogkegiatanRequest::FIELDS));
            }
        });
    }
}
