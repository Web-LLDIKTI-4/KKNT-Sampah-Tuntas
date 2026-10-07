<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class PendataanPemilahanSampahRequest extends AjaxFormRequest
{
    public const FIELDS = [
        'tanggal', 'nama_kepala_keluarga', 'alamat_rumah', 'rt', 'rw',
        'memilah', 'organik_kg', 'anorganik_kg', 'residu_kg',
    ];

    public function rules(): array
    {
        $berat = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'];

        return [
            'id_pendataan' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'nama_kepala_keluarga' => ['required', 'string', 'max:150', 'not_regex:/^[=+\-@]/'],
            'alamat_rumah' => ['required', 'string', 'max:255', 'not_regex:/^[=+\-@]/'],
            'rt' => ['required', 'string', 'max:5', 'regex:/^[0-9A-Za-z]+$/'],
            'rw' => ['required', 'string', 'max:5', 'regex:/^[0-9A-Za-z]+$/'],
            'memilah' => ['required', 'boolean'],
            'organik_kg' => $berat,
            'anorganik_kg' => $berat,
            'residu_kg' => $berat,
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Kolom ini harus diisi.',
            '*.numeric' => 'Nilai harus berupa angka.',
            '*.min' => 'Nilai tidak boleh negatif.',
            '*.max' => 'Nilai melebihi batas maksimum.',
            '*.decimal' => 'Maksimal 2 angka di belakang koma.',
            '*.not_regex' => 'Teks tidak boleh diawali tanda = + - @.',
            'rt.regex' => 'RT hanya boleh berisi huruf/angka.',
            'rw.regex' => 'RW hanya boleh berisi huruf/angka.',
            'memilah.boolean' => 'Pilih Ya atau Tidak.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
        ];
    }
}
