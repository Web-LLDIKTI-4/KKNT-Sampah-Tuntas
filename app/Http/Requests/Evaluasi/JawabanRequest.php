<?php

namespace App\Http\Requests\Evaluasi;

use App\Http\Requests\AjaxFormRequest;

class JawabanRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'jawaban' => ['required', 'array'],
            'jawaban.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'jawaban.required' => 'Jawaban evaluasi harus diisi.',
            'jawaban.*.max' => 'Jawaban maksimal 5000 karakter.',
        ];
    }
}
