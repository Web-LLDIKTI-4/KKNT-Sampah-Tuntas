<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaporanPublikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'date_format:Y-m'],
            'kecamatan' => ['nullable', 'uuid', 'exists:kecamatan,id_kecamatan'],
            'desa' => [
                'nullable', 'uuid',
                Rule::exists('desa', 'id_desa')->when($this->input('kecamatan'), fn ($rule, $id) => $rule->where('id_kecamatan', $id)),
            ],
        ];
    }

    public function filter(): array
    {
        return [
            'bulan' => $this->validated('bulan'),
            'id_kecamatan' => $this->validated('kecamatan'),
            'id_desa' => $this->validated('desa'),
        ];
    }
}
