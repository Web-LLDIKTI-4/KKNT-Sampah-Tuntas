<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class PjdesaRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_pjdesa' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:pj_desa,id_pjdesa'],
            'id_desa' => ['required', 'uuid', 'exists:desa,id_desa'],
            'email' => [
                'required', 'string', 'max:200',
                Rule::exists('users', 'email')->where('akses', 'pjdesa'),
                Rule::unique('pj_desa', 'email')
                    ->where('id_desa', $this->input('id_desa'))
                    ->ignore($this->input('id_pjdesa'), 'id_pjdesa'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_desa.required' => 'Desa harus dipilih.',
            'email.required' => 'Ketua Kelompok harus dipilih.',
            'email.exists' => 'Ketua Kelompok tidak valid.',
            'email.unique' => 'Data sudah ada!',
        ];
    }
}
