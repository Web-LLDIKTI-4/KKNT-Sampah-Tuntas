<?php

namespace App\Http\Requests\Evaluasi;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class PertanyaanRequest extends AjaxFormRequest
{
    protected array $htmlFields = ['pertanyaan'];

    public function rules(): array
    {
        return [
            'id_evaluasi' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:evaluasi_kegiatan,id_evaluasi'],
            'pertanyaan' => [
                'required', 'string', 'max:65000',
                Rule::unique('evaluasi_kegiatan', 'pertanyaan')->ignore($this->input('id_evaluasi'), 'id_evaluasi'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pertanyaan.required' => 'Pertanyaan harus diisi.',
            'pertanyaan.unique' => 'Data pertanyaan sudah ada!',
        ];
    }
}
