<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class DesaprofileRequest extends AjaxFormRequest
{
    protected array $htmlFields = ['potensi', 'masalah'];

    public function rules(): array
    {
        return [
            'id_profile' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:desa_profile,id_profile'],
            'id_desa' => ['required', 'uuid', 'exists:desa,id_desa'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'potensi' => [
                'required', 'string', 'max:65000',
                Rule::unique('desa_profile', 'potensi')
                    ->where('id_desa', $this->input('id_desa'))
                    ->where('tahun', $this->input('tahun'))
                    ->where('masalah', $this->input('masalah'))
                    ->ignore($this->input('id_profile'), 'id_profile'),
            ],
            'masalah' => ['required', 'string', 'max:65000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_desa.required' => 'Desa harus dipilih.',
            'potensi.required' => 'Potensi desa harus diisi.',
            'potensi.unique' => 'Data potensi dan masalah yang sama sudah ada!',
            'masalah.required' => 'Masalah desa harus diisi.',
        ];
    }
}
