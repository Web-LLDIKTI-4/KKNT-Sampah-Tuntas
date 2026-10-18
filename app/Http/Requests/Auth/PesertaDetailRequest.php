<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PesertaDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kodept' => ['required', 'string', 'regex:/^[0-9A-Za-z]{1,10}$/'],
            'jenis' => ['required', 'in:mahasiswa,dpl,pic,ketua'],
        ];
    }
}
