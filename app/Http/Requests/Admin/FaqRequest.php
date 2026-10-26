<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Faq;
use Illuminate\Validation\Rule;

class FaqRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->isUpdate() ? 'update' : 'create', Faq::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'integer', 'exists:faqs,id'],
            'pertanyaan' => ['required', 'string', 'max:255'],
            'jawaban' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Data FAQ tidak valid.',
            'id.integer' => 'Data FAQ tidak valid.',
            'id.exists' => 'Data FAQ tidak ditemukan.',
            'pertanyaan.required' => 'Pertanyaan harus diisi.',
            'pertanyaan.string' => 'Pertanyaan tidak valid.',
            'pertanyaan.max' => 'Pertanyaan maksimal 255 karakter.',
            'jawaban.required' => 'Jawaban harus diisi.',
            'jawaban.string' => 'Jawaban tidak valid.',
            'jawaban.max' => 'Jawaban maksimal 10000 karakter.',
        ];
    }
}
