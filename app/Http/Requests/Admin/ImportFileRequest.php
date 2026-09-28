<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File import harus dipilih.',
            'file.mimes' => 'File harus berformat xlsx, xls, atau csv.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ];
    }
}
