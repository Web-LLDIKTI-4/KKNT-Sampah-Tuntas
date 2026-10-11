<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Support\Arr;

/**
 * Log harian = pendataan pemilahan sampah (wajib) + log kegiatan (bila ada_kegiatan_lain=1).
 * Mewarisi $htmlFields, after() (status kehadiran), attributes & messages dari LogkegiatanRequest.
 */
class LogHarianRequest extends LogkegiatanRequest
{
    public function rules(): array
    {
        // Field log selain tanggal dibuang dari validated() bila tidak ada kegiatan lain
        $logRules = array_map(
            fn (array $rules) => ['exclude_unless:ada_kegiatan_lain,1', ...$rules],
            Arr::except(parent::rules(), ['id_log', 'tanggal']),
        );

        return PendataanPemilahanSampahRequest::dataRules()
            + ['ada_kegiatan_lain' => ['required', 'boolean']]
            + $logRules;
    }

    public function messages(): array
    {
        // Pesan spesifik log didahulukan sebelum wildcard (*.required dst) milik pendataan
        return parent::messages() + [
            'ada_kegiatan_lain.required' => 'Pilih apakah ada kegiatan lain.',
            'ada_kegiatan_lain.boolean' => 'Pilih Ya atau Tidak.',
        ] + (new PendataanPemilahanSampahRequest)->messages();
    }
}
