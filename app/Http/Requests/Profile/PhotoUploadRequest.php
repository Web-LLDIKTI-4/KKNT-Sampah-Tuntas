<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PhotoUploadRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'file_upload' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_upload.required' => 'File harus diunggah.',
            'file_upload.image' => 'File harus berupa gambar.',
            'file_upload.mimes' => 'Format file tidak valid. Hanya diperbolehkan: jpeg, png.',
            'file_upload.max' => 'Ukuran file tidak boleh lebih dari 2MB.',
            'file_upload.dimensions' => 'Dimensi gambar maksimal 4000x4000 piksel.',
        ];
    }

    // Frontend upload foto membaca key "error"
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => $validator->errors(),
            'message' => 'Foto gagal diunggah',
        ]));
    }
}
