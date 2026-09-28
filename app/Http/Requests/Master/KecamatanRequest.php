<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class KecamatanRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_kecamatan' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:kecamatan,id_kecamatan'],
            'kecamatan' => [
                'required', 'string', 'max:200',
                Rule::unique('kecamatan', 'kecamatan')->ignore($this->input('id_kecamatan'), 'id_kecamatan'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kecamatan.required' => 'Nama kecamatan harus diisi.',
            'kecamatan.unique' => 'Data sudah ada!',
        ];
    }
}
