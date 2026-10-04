<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class KpiRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_kpi' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:kpi,id_kpi'],
            'nama_kpi' => [
                'required', 'string', 'max:255',
                Rule::unique('kpi', 'nama_kpi')->ignore($this->input('id_kpi'), 'id_kpi'),
            ],
            'target' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'satuan' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kpi.required' => 'Key performance indicator harus di isi',
            'nama_kpi.unique' => 'Key performance indicator sudah ada!',
            'target.numeric' => 'Target harus berupa angka.',
            'target.min' => 'Target tidak boleh negatif.',
            'target.decimal' => 'Target maksimal 2 angka di belakang koma.',
            'target.max' => 'Target maksimal 99.999.999,99.',
            'satuan.max' => 'Satuan maksimal 50 karakter.',
        ];
    }
}
