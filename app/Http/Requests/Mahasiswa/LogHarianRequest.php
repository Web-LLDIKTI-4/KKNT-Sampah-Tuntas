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
        // Field di luar mode terpilih dibuang dari validated(); tanggal dipakai kedua mode,
        // batas mulai pelaksanaan KKN hanya berlaku untuk pendataan
        $prefix = fn (string $mode) => fn (array $rules) => ['exclude_unless:mode,'.$mode, ...$rules];
        $dataRules = PendataanPemilahanSampahRequest::dataRules(PendataanPemilahanSampahRequest::minTanggal($this->user()));
        $logRules = parent::rules();
        $tanggal = $this->input('mode') === 'pendataan' ? $dataRules['tanggal'] : $logRules['tanggal'];

        return ['mode' => ['required', 'in:pendataan,kegiatan'], 'tanggal' => $tanggal]
            + array_map($prefix('pendataan'), Arr::except($dataRules, ['tanggal']))
            + array_map($prefix('kegiatan'), Arr::except($logRules, ['id_log', 'tanggal']));
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
