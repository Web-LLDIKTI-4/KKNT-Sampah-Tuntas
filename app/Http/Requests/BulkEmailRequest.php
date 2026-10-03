<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Pilihan bulk (checkbox createuser[]) berisi email; error dalam format {error} yang dibaca view bulk select.
 */
class BulkEmailRequest extends FormRequest
{
    protected int $maxItems = 1000;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'createuser' => ['required', 'array', 'max:'.$this->maxItems],
            'createuser.*' => ['required', 'string', 'email', 'max:255', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'createuser.required' => 'user harus dipilih',
            'createuser.max' => "maksimal {$this->maxItems} data per proses",
        ];
    }

    /** @return array<int, string> */
    public function emails(): array
    {
        return array_values($this->validated('createuser'));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['error' => $validator->errors()->first()]));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['error' => 'Anda tidak memiliki akses untuk data ini.'], 403));
    }
}
