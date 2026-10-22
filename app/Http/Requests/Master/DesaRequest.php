<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class DesaRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_desa' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'string', 'max:36', 'exists:desa,id_desa'],
            'id_kecamatan' => ['required', 'string', 'max:36', 'exists:kecamatan,id_kecamatan'],
            'desa' => [
                'required', 'string', 'max:200',
                Rule::unique('desa', 'desa')
                    ->where('id_kecamatan', $this->input('id_kecamatan'))
                    ->ignore($this->input('id_desa'), 'id_desa'),
            ],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'satuan_baseline' => ['required', 'string', Rule::in(['%', 'kg'])],
            // Persentase dibatasi 100; kg mengikuti batas decimal(10,2)
            'baseline' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', $this->input('satuan_baseline') === '%' ? 'max:100' : 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kecamatan.required' => 'Nama kecamatan harus dipilih.',
            'id_kecamatan.exists' => 'Kecamatan tidak valid.',
            'desa.required' => 'Nama desa harus diisi.',
            'desa.unique' => 'Data sudah ada!',
            'latitude.required_with' => 'Latitude harus diisi jika longitude diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus di antara -90 dan 90.',
            'longitude.required_with' => 'Longitude harus diisi jika latitude diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus di antara -180 dan 180.',
            'satuan_baseline.required' => 'Satuan Baseline harus dipilih.',
            'satuan_baseline.in' => 'Satuan Baseline tidak valid.',
            'baseline.numeric' => 'Baseline harus berupa angka.',
            'baseline.min' => 'Baseline tidak boleh kurang dari 0.',
            'baseline.max' => 'Baseline tidak boleh lebih dari :max.',
            'baseline.decimal' => 'Baseline maksimal 2 angka desimal.',
        ];
    }
}
