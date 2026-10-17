<?php

namespace App\Http\Requests\Dpl;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MentoringInsertRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dpl';
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Frontend sends comma-separated string; limit bounds memory
        $raw = $this->input('createuser');
        if (is_string($raw)) {
            $this->merge([
                'createuser' => array_values(array_unique(array_filter(
                    array_map('trim', explode(',', $raw, 1002)),
                    fn ($v) => $v !== ''
                ))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'createuser' => ['required', 'array', 'min:1', 'max:1000'],
            'createuser.*' => ['required', 'string', 'email', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'createuser.required' => 'user harus dipilih',
            'createuser.array' => 'user harus dipilih',
            'createuser.min' => 'user harus dipilih',
            'createuser.max' => 'maksimal 1000 mahasiswa per simpan',
            'createuser.*.required' => 'format email mahasiswa tidak valid',
            'createuser.*.string' => 'format email mahasiswa tidak valid',
            'createuser.*.email' => 'format email mahasiswa tidak valid',
            'createuser.*.max' => 'format email mahasiswa tidak valid',
        ];
    }

    // Modal JS reads data.error
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['error' => $validator->errors()->first()]));
    }
}
