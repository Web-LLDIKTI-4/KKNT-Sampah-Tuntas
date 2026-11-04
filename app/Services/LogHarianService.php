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
    // Plafon berat per jenis sampah per log (kg)
    public const MAX_BERAT_KG = 1000;

    // Simpan ke satu tabel sesuai mode (pendataan / kegiatan)
    public function store(array $data, string $email): void
    {
        DB::transaction(function () use ($data, $email) {
            if ($data['mode'] === 'pendataan') {
                PendataanPemilahanSampah::create(['email' => $email] + Arr::only($data, PendataanPemilahanSampahRequest::FIELDS));
            } else {
                Logkegiatan::create(['email' => $email] + Arr::only($data, LogkegiatanRequest::FIELDS));
            }
        });
    }
}
