<?php

namespace App\Http\Requests\Admin;

class BulkUpdateUserRequest extends BulkSelectionRequest
{
    protected ?string $errorMessage = 'Data gagal disimpan!';

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'location_program' => ['required', 'uuid', 'exists:lokasi_program,id'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'location_program.required' => 'Pilih minimal satu field untuk diubah.',
            'location_program.uuid' => 'Lokasi program tidak valid.',
            'location_program.exists' => 'Lokasi program tidak valid.',
        ];
    }
}
