<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', 'in:mahasiswa,dpl,pt,kepala,pemda'],
            'kodept' => ['nullable', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            // Export only: same keyword as the table's global search
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    // AJAX callers (listdata, DataTables) always get JSON 422 instead of a redirect
    protected function failedValidation(Validator $validator): void
    {
        if (! $this->ajax()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
