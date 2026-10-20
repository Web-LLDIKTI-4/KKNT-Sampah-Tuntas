<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Services\LogHarianService;
use Illuminate\Validation\Rule;

class PendataanPemilahanSampahRequest extends AjaxFormRequest
{
    public const FIELDS = [
        'tanggal', 'nama_kepala_keluarga', 'jumlah_anggota_keluarga', 'alamat_rumah', 'rt', 'rw',
        'memilah', 'organik_kg', 'anorganik_kg', 'residu_kg',
    ];

    protected array $dateFields = ['tanggal'];

    public function rules(): array
    {
        return [
            'id_pendataan' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
        ] + self::dataRules();
    }

    // Rules data pendataan (tanpa id), dipakai ulang oleh LogHarianRequest
    public static function dataRules(): array
    {
        $berat = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:'.LogHarianService::MAX_BERAT_KG];

        return [
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'nama_kepala_keluarga' => ['required', 'string', 'max:150', 'not_regex:/^[=+\-@]/'],
            // max hanya guard batas kolom unsigned int
            'jumlah_anggota_keluarga' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'alamat_rumah' => ['required', 'string', 'max:255', 'not_regex:/^[=+\-@]/'],
            'rt' => ['required', 'string', 'regex:/^\d{3}$/'],
            'rw' => ['required', 'string', 'regex:/^\d{3}$/'],
            'memilah' => ['required', 'boolean'],
            'organik_kg' => $berat,
            'anorganik_kg' => $berat,
            'residu_kg' => $berat,
        ];
    }

    public function messages(): array
    {
        return [
            // Key spesifik harus sebelum wildcard (dicocokkan sesuai urutan)
            'jumlah_anggota_keluarga.min' => 'Minimal 1 orang.',
            'organik_kg.max' => 'Berat maksimal '.LogHarianService::MAX_BERAT_KG.' kg.',
            'anorganik_kg.max' => 'Berat maksimal '.LogHarianService::MAX_BERAT_KG.' kg.',
            'residu_kg.max' => 'Berat maksimal '.LogHarianService::MAX_BERAT_KG.' kg.',
            '*.required' => 'Kolom ini harus diisi.',
            '*.numeric' => 'Nilai harus berupa angka.',
            '*.integer' => 'Nilai harus berupa bilangan bulat.',
            '*.min' => 'Nilai tidak boleh negatif.',
            '*.max' => 'Nilai melebihi batas maksimum.',
            '*.decimal' => 'Maksimal 2 angka di belakang koma.',
            '*.not_regex' => 'Teks tidak boleh diawali tanda = + - @.',
            'rt.regex' => 'RT harus 3 digit angka, contoh 001.',
            'rw.regex' => 'RW harus 3 digit angka, contoh 012.',
            'memilah.boolean' => 'Pilih Ya atau Tidak.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
        ];
    }
}
