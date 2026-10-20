<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class LokasiProgramRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:lokasi_program,id'],
            'nama_lokasi' => [
                'required', 'string', 'max:255',
                Rule::unique('lokasi_program', 'nama_lokasi')->ignore($this->input('id')),
            ],
            'koefisien_timbulan_sampah' => ['nullable', 'numeric', 'min:0', 'max:9999.9999', 'decimal:0,4'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lokasi.required' => 'Nama lokasi harus diisi.',
            'nama_lokasi.unique' => 'Data sudah ada!',
            'koefisien_timbulan_sampah.numeric' => 'Koefisien harus berupa angka.',
            'koefisien_timbulan_sampah.min' => 'Koefisien tidak boleh negatif.',
            'koefisien_timbulan_sampah.max' => 'Koefisien maksimal 9999.9999.',
            'koefisien_timbulan_sampah.decimal' => 'Maksimal 4 angka di belakang koma.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'gambar.max' => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
