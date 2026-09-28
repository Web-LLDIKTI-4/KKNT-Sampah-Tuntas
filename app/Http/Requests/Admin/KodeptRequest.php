<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class KodeptRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'kodept' => ['required', 'string', 'regex:/^[0-9]{6}$/', 'unique:ref_satuanpendidikan,npsn'],
        ];
    }

    public function messages(): array
    {
        return [
            'kodept.required' => 'Kode perguruan tinggi harus di isi',
            'kodept.regex' => 'Kode perguruan tinggi harus 6 digit angka',
            'kodept.unique' => 'Kode perguruan tinggi sudah ada!',
        ];
    }

    // Halaman perguruan tinggi membaca key "messages"
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'messages' => 'Data gagal disimpan!',
            'errors' => $validator->errors(),
        ]));
    }
}
