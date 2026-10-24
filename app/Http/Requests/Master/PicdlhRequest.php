<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Picdlh;
use Illuminate\Validation\Rule;

class PicdlhRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'id_pic_dlh' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:pic_dlh,id_pic_dlh'],
            'id_kecamatan' => ['required', 'string', 'max:36', 'exists:kecamatan,id_kecamatan'],
            'location_program' => ['nullable', 'uuid', 'exists:lokasi_program,id'],
            'nama' => ['required', 'string', 'max:200'],
            'kontak' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\ ()]+$/D',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && preg_match_all('/\d/', $value) < 8) {
                        $fail('Nomor kontak minimal berisi 8 digit angka.');
                    }
                }],
            'keterangan' => ['required', 'string', Rule::in(array_keys(Picdlh::KETERANGAN))],
        ];
    }

    public function messages(): array
    {
        return [
            'id_pic_dlh.required' => 'Data Kontak Pelaksana Program tidak valid.',
            'id_pic_dlh.uuid' => 'Data Kontak Pelaksana Program tidak valid.',
            'id_pic_dlh.exists' => 'Data Kontak Pelaksana Program tidak ditemukan.',
            'id_kecamatan.required' => 'Kecamatan harus dipilih.',
            'id_kecamatan.exists' => 'Kecamatan tidak valid.',
            'location_program.uuid' => 'Lokasi program tidak valid.',
            'location_program.exists' => 'Lokasi program tidak valid.',
            'nama.required' => 'Nama harus diisi.',
            'nama.max' => 'Nama maksimal 200 karakter.',
            'kontak.required' => 'Nomor kontak harus diisi.',
            'kontak.max' => 'Nomor kontak maksimal 50 karakter.',
            'kontak.regex' => 'Nomor kontak hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'keterangan.required' => 'Keterangan harus dipilih.',
            'keterangan.in' => 'Keterangan tidak valid.',
        ];
    }
}
