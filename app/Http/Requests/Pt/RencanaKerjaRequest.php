<?php

namespace App\Http\Requests\Pt;

use App\Http\Requests\AjaxFormRequest;
use App\Models\RencanaKerja;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class RencanaKerjaRequest extends AjaxFormRequest
{
    public const MAX_KILOBYTES = 10240;

    // Hanya PDF agar semua dokumen bisa dilihat inline di browser
    public const ALLOWED_EXTENSIONS = ['pdf'];

    public const MIN_TAHUN = 2020;

    // Update diotorisasi di controller per record (Policy), insert dicek di sini
    public function authorize(): bool
    {
        return $this->isUpdate() || (bool) $this->user()?->can('create', RencanaKerja::class);
    }

    public function rules(): array
    {
        return [
            'id_rencana_kerja' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'string', 'uuid'],
            'judul' => ['required', 'string', 'max:200'],
            'tahun' => ['required', 'integer', 'between:'.self::MIN_TAHUN.','.(now()->year + 1)],
            'keterangan' => ['nullable', 'string', 'max:2000'],
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
            'id_rencana_kerja.required' => 'Data rencana kerja tidak valid.',
            'id_rencana_kerja.uuid' => 'Data rencana kerja tidak valid.',
            'judul.required' => 'Judul rencana kerja harus diisi.',
            'judul.max' => 'Judul maksimal 200 karakter.',
            'tahun.required' => 'Tahun harus diisi.',
            'tahun.integer' => 'Tahun tidak valid.',
            'tahun.between' => 'Tahun harus antara '.self::MIN_TAHUN.' dan '.(now()->year + 1).'.',
            'keterangan.max' => 'Keterangan maksimal 2000 karakter.',
            'file.required' => 'Dokumen rencana kerja harus diunggah.',
            'file.uploaded' => $this->describeFailedUpload(),
            'file.file' => 'Dokumen rencana kerja gagal diunggah.',
            'file.mimes' => 'Format file harus PDF.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ];
    }

    // Penyebab tersering: file melebihi upload_max_filesize php.ini (lebih kecil dari batas aplikasi)
    private function describeFailedUpload(): string
    {
        $uploadedFile = $this->files->get('file');
        $errorCode = $uploadedFile instanceof UploadedFile ? $uploadedFile->getError() : null;

        if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'Ukuran file melebihi batas upload server ('.ini_get('upload_max_filesize')
                .'). Kecilkan file atau minta admin server menaikkan batas upload.';
        }

        return 'Dokumen rencana kerja gagal diunggah, silakan coba lagi.';
    }
}
