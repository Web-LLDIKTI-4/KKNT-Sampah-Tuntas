<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;

class ImportFileRequest extends AjaxFormRequest
{
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
