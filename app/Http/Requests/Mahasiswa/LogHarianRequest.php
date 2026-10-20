<?php

namespace App\Http\Requests\Mahasiswa;

use App\Models\PendataanPemilahanSampah;
use App\Services\LogHarianService;
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

    // Status kehadiran (parent) + batas input log harian per hari
    public function after(): array
    {
        return [...parent::after(), function ($validator) {
            $count = PendataanPemilahanSampah::where('email', $this->user()->email)
                ->where('created_at', '>=', today())
                ->count();
            if ($count >= LogHarianService::MAX_LOG_PER_DAY) {
                $validator->errors()->add('tanggal', 'Batas input log harian hari ini sudah tercapai (maksimal '.LogHarianService::MAX_LOG_PER_DAY.' log per hari).');
            }
        }];
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
