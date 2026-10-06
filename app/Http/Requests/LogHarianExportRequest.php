<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogHarianExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
