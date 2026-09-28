<?php

namespace App\Services;

use App\Models\Kpicapaian;
use App\Models\Kpitarget;

class KpiCapaianService
{
    /**
     * Nomor tahapan dari teks target, mis. "Tahap 2" => 2.
     */
    public function tahapanNumber(Kpitarget $target): int
    {
        return (int) preg_replace('/[^0-9]+/', '', (string) $target->tahapan);
    }

    /**
     * Pesan error bila tahapan sebelumnya belum diisi, null bila urutan valid.
     */
    public function sequenceError(string $email, string $idKpi, int $tahapan): ?string
    {
        if ($tahapan <= 1) {
            return null;
        }

        $terakhir = (int) Kpicapaian::where('id_kpi', $idKpi)->where('email', $email)->max('tahapan');

        return $terakhir + 1 === $tahapan
            ? null
            : 'Tahapan sebelumnya (Tahap '.($terakhir + 1).') harus diisi terlebih dahulu!';
    }
}
