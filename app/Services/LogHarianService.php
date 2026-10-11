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
