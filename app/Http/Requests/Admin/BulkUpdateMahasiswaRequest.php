<?php

namespace App\Http\Requests\Admin;

use App\Models\Mahasiswa;

class BulkUpdateMahasiswaRequest extends BulkUpdatePersonRequest
{
    protected function model(): string
    {
        return Mahasiswa::class;
    }

    protected function identityColumn(): string
    {
        return 'nim';
    }

    protected function identityLabel(): string
    {
        return 'NIM';
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'tahun_masuk' => ['nullable', 'integer', 'between:1990,'.(date('Y') + 1)],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'tahun_masuk.integer' => 'Tahun masuk harus berupa angka.',
            'tahun_masuk.between' => 'Tahun masuk tidak valid.',
        ];
    }
}
