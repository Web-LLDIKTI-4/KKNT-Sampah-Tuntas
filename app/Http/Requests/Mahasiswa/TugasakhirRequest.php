<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Tugasakhir;
use Illuminate\Validation\Rule;

class TugasakhirRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_tugasakhir' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'tautan' => ['required', 'url:http,https', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            // Satu mahasiswa hanya punya satu tugas akhir
            if (! $this->isUpdate() && Tugasakhir::ownedBy($this->user())->exists()) {
                $validator->errors()->add('tautan', 'Tugas akhir sudah ada!');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'tautan.required' => 'tautan harus di isi.',
            'tautan.url' => 'Tautan harus berupa URL http/https yang valid.',
        ];
    }
}
