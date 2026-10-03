<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class PanduanRequest extends AjaxFormRequest
{
    public const MAX_KILOBYTES = 10240;

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    public function rules(): array
    {
        return [
            'id_panduan' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid', 'exists:panduan,id_panduan'],
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'is_aktif' => ['required', 'boolean'],
            'file' => [
                $this->isUpdate() ? 'nullable' : 'required',
                'file',
                'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
                'max:'.self::MAX_KILOBYTES,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_panduan.required' => 'Data panduan tidak valid.',
            'id_panduan.exists' => 'Data panduan tidak ditemukan.',
            'judul.required' => 'Judul panduan harus diisi.',
            'judul.max' => 'Judul maksimal 200 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 2000 karakter.',
            'is_aktif.required' => 'Status harus dipilih.',
            'is_aktif.boolean' => 'Status tidak valid.',
            'file.required' => 'File panduan harus diunggah.',
            'file.uploaded' => $this->describeFailedUpload(),
            'file.file' => 'File panduan gagal diunggah.',
            'file.mimes' => 'Format file harus pdf, doc, docx, xls, xlsx, ppt, atau pptx.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ];
    }

    // Pesan bawaan "failed to upload" tidak menjelaskan penyebab; kasus tersering adalah
    // file melebihi upload_max_filesize di php.ini (lebih kecil dari batas aplikasi 10MB)
    private function describeFailedUpload(): string
    {
        $uploadedFile = $this->files->get('file');
        $errorCode = $uploadedFile instanceof UploadedFile ? $uploadedFile->getError() : null;

        if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'Ukuran file melebihi batas upload server ('.ini_get('upload_max_filesize')
                .'). Kecilkan file atau minta admin server menaikkan batas upload.';
        }

        return 'File panduan gagal diunggah, silakan coba lagi.';
    }
}
