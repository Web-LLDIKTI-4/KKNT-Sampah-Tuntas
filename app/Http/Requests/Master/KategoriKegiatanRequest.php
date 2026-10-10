<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class KategoriKegiatanRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_kategori' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:kategori_kegiatan,id_kategori'],
            'nama_kategori' => [
                'required', 'string', 'max:255',
                Rule::unique('kategori_kegiatan', 'nama_kategori')->ignore($this->input('id_kategori'), 'id_kategori'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_kategori' => 'Nama Aktivitas',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama aktivitas harus di isi',
            'nama_kategori.unique' => 'Nama aktivitas sudah ada!',
        ];
    }
}
