<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Foundation\Http\FormRequest;

class PemilahanExportRequest extends FormRequest
{
    // Access is checked in the controller (404 for students outside the user's scope)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'string', 'regex:/^(19|20)\d{2}-(0[1-9]|1[0-2])$/'],
        ];
    }
}
