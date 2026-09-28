<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class DesaRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_desa' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:desa,id_desa'],
            'id_kecamatan' => ['required', 'uuid', 'exists:kecamatan,id_kecamatan'],
            'desa' => [
                'required', 'string', 'max:200',
                Rule::unique('desa', 'desa')
                    ->where('id_kecamatan', $this->input('id_kecamatan'))
                    ->ignore($this->input('id_desa'), 'id_desa'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kecamatan.required' => 'Nama kecamatan harus dipilih.',
            'id_kecamatan.exists' => 'Kecamatan tidak valid.',
            'desa.required' => 'Nama desa harus diisi.',
            'desa.unique' => 'Data sudah ada!',
        ];
    }
}
