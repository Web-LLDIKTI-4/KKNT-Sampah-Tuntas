<?php

namespace App\Http\Requests\Dpl;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi bersama untuk penilaian mahasiswa oleh DPL (konversi & freeform).
 */
abstract class NilaiRequest extends AjaxFormRequest
{
    // Nama primary key record nilai, mis. id_konversi
    abstract protected function keyName(): string;

    // Rules tambahan khusus modul
    abstract protected function extraRules(): array;

    public function rules(): array
    {
        return [
            $this->keyName() => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'id_mahasiswa' => ['required', 'uuid'],
            'nilai_dpl' => ['required', 'numeric', 'between:0,100'],
            'nilai_dpa' => ['nullable', 'numeric', 'between:0,100'],
        ] + $this->extraRules();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $email = Mahasiswa::whereKey($this->input('id_mahasiswa'))->value('email');
            if (! Dplmentoring::isMentor($this->user(), $email)) {
                $validator->errors()->add('id_mahasiswa', 'Mahasiswa bukan bimbingan Anda.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'id_mahasiswa.required' => 'Mahasiswa harus dipilih.',
            'nilai_dpl.required' => 'Nilai DPL harus di isi.',
            'nilai_dpl.numeric' => 'Nilai DPL harus angka.',
            'nilai_dpl.between' => 'Nilai DPL harus 0 - 100.',
            'nilai_dpa.numeric' => 'Nilai DPA, isian harus angka.',
            'nilai_dpa.between' => 'Nilai DPA harus 0 - 100.',
        ];
    }
}
