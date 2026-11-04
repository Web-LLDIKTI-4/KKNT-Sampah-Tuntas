<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Support\Arr;

/**
 * Log harian = pendataan pemilahan sampah ATAU log kegiatan, dipilih lewat field mode.
 * Mewarisi $htmlFields, after() (status kehadiran), attributes & messages dari LogkegiatanRequest.
 */
class LogHarianRequest extends LogkegiatanRequest
{
    public function rules(): array
    {
        // Field di luar mode terpilih dibuang dari validated(); tanggal dipakai kedua mode
        $prefix = fn (string $mode) => fn (array $rules) => ['exclude_unless:mode,'.$mode, ...$rules];
        $dataRules = PendataanPemilahanSampahRequest::dataRules();

        return ['mode' => ['required', 'in:pendataan,kegiatan'], 'tanggal' => $dataRules['tanggal']]
            + array_map($prefix('pendataan'), Arr::except($dataRules, ['tanggal']))
            + array_map($prefix('kegiatan'), Arr::except(parent::rules(), ['id_log', 'tanggal']));
    }

    public function messages(): array
    {
        // Pesan spesifik log didahulukan sebelum wildcard (*.required dst) milik pendataan
        return parent::messages() + [
            'mode.required' => 'Pilih jenis isian: pendataan sampah atau log harian.',
            'mode.in' => 'Pilihan tidak valid.',
        ] + (new PendataanPemilahanSampahRequest)->messages();
    }
}
