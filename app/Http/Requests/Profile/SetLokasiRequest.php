<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\AjaxFormRequest;

class SetLokasiRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'mahasiswa' && $this->user()->mahasiswa !== null;
    }

    public function rules(): array
    {
        return [
            'id_desa' => ['required', 'string', 'max:36', 'exists:desa,id_desa'],
            'tahun' => ['required', 'integer', 'between:'.(date('Y') - 1).','.(date('Y') + 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'id_desa.required' => 'Desa harus dipilih.',
            'id_desa.exists' => 'Desa tidak valid.',
            'tahun.required' => 'Tahun harus dipilih.',
        ];
    }
}
