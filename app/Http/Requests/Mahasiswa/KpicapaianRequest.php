<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Kpicapaian;
use App\Models\Pjdesa;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KpicapaianRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_capaian' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'id_kpi' => ['required', 'uuid', 'exists:kpi,id_kpi'],
            'id_target' => [
                'required', 'uuid',
                Rule::exists('kpi_target', 'id_target')->where('id_kpi', $this->input('id_kpi')),
            ],
            'realisasi' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'status_capaian' => ['required', Rule::in(array_keys(Kpicapaian::STATUS))],
            'tautan' => ['required', 'url:http,https', 'max:2000'],
            'permasalahan' => ['required', 'string', 'max:5000'],
            'solusi' => ['required', 'string', 'max:5000'],
            'kendala' => ['required', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $email = $this->user()->email;

            if (! Pjdesa::where('email', $email)->exists()) {
                $validator->errors()->add('kendala', 'Anda tidak memiliki akses untuk menyimpan data capaian KPI. Silakan hubungi administrator.');

                return;
            }

            $duplikat = Kpicapaian::where('id_kpi', $this->input('id_kpi'))
                ->where('id_target', $this->input('id_target'))
                ->where('email', $email)
                ->when($this->input('id_capaian'), fn ($q, $id) => $q->whereKeyNot($id))
                ->exists();
            if ($duplikat) {
                $validator->errors()->add('id_target', 'Data sudah ada!');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'id_kpi.required' => 'KPI harus dipilih.',
            'id_target.required' => 'Kegiatan harus dipilih.',
            'id_target.exists' => 'Kegiatan tidak sesuai dengan KPI yang dipilih.',
            'tautan.required' => 'Tautan harus isi.',
            'tautan.url' => 'Tautan harus berupa URL http/https yang valid.',
            'permasalahan.required' => 'Permasalahan harus isi.',
            'solusi.required' => 'Solusi harus isi.',
            'kendala.required' => 'Kendala harus isi.',
        ];
    }
}
