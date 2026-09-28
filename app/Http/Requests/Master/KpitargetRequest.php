<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class KpitargetRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_target' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:kpi_target,id_target'],
            'id_kpi' => ['required', 'uuid', 'exists:kpi,id_kpi'],
            'tahapan' => ['required', 'string', 'max:50'],
            'nama_kpitarget' => [
                'required', 'string', 'max:200',
                Rule::unique('kpi_target', 'nama_kpitarget')
                    ->where('id_kpi', $this->input('id_kpi'))
                    ->ignore($this->input('id_target'), 'id_target'),
            ],
            'target' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'satuan' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kpi.required' => 'KPI harus dipilih.',
            'nama_kpitarget.required' => 'Kegiatan harus diisi.',
            'nama_kpitarget.unique' => 'Kegiatan sudah ada!',
            'tahapan.required' => 'Tahapan KPI harus diisi.',
            'target.required' => 'Target harus diisi.',
            'target.numeric' => 'Target harus angka.',
            'target.gt' => 'Target harus lebih dari 0.',
            'satuan.required' => 'Satuan harus diisi.',
        ];
    }
}
