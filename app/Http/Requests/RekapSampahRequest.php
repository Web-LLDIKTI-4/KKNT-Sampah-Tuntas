<?php

namespace App\Http\Requests;

use App\Models\Kpisampah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RekapSampahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'regex:/^(semua|\d{4}-(0[1-9]|1[0-2]))$/'],
            'kecamatan' => ['nullable', 'uuid', 'exists:kecamatan,id_kecamatan'],
            'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            'klaster' => ['nullable', Rule::in(array_keys(Kpisampah::KLASTER))],
        ];
    }
}
